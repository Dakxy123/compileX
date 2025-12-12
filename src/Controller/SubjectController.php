<?php

namespace App\Controller;

use App\Entity\Subject;
use App\Form\SubjectType;
use App\Repository\SubjectRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[Route('/subject')]
#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_INSTRUCTOR")'))]
final class SubjectController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'app_subject_index', methods: ['GET'])]
    public function index(
        Request $request,
        SubjectRepository $subjectRepository,
        CourseRepository $courseRepository
    ): Response {
        // ✅ ACTIVITY LOG
        $this->activityLogger->log('subject.index', 'subject', 'Viewed subject list.');

        $q            = trim((string) $request->query->get('q', ''));
        $courseId     = $request->query->get('courseId');
        $yearLevel    = $request->query->get('yearLevel');
        $semester     = $request->query->get('semester');
        $courseStatus = $request->query->get('courseStatus', 'all'); // all|active|inactive

        $qb = $subjectRepository->createQueryBuilder('s')
            ->leftJoin('s.course', 'c')
            ->addSelect('c');

        if ($q !== '') {
            $qb->andWhere('s.name LIKE :q OR s.code LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($courseId !== null && $courseId !== '') {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', (int) $courseId);
        }

        if ($yearLevel !== null && $yearLevel !== '') {
            $qb->andWhere('s.yearLevel = :yearLevel')
               ->setParameter('yearLevel', (int) $yearLevel);
        }

        if ($semester !== null && $semester !== '') {
            $qb->andWhere('s.semester = :semester')
               ->setParameter('semester', (int) $semester);
        }

        if ($courseStatus === 'active') {
            $qb->andWhere('c.IsActive = :active')
               ->setParameter('active', true);
        } elseif ($courseStatus === 'inactive') {
            $qb->andWhere('c.IsActive = :active')
               ->setParameter('active', false);
        }

        $qb->orderBy('c.name', 'ASC')
           ->addOrderBy('s.yearLevel', 'ASC')
           ->addOrderBy('s.semester', 'ASC')
           ->addOrderBy('s.code', 'ASC');

        $subjects = $qb->getQuery()->getResult();
        $courses  = $courseRepository->findBy([], ['name' => 'ASC']);

        return $this->render('subject/index.html.twig', [
            'subjects'     => $subjects,
            'courses'      => $courses,
            'q'            => $q,
            'courseId'     => $courseId,
            'yearLevel'    => $yearLevel,
            'semester'     => $semester,
            'courseStatus' => $courseStatus,
        ]);
    }

    #[Route('/export', name: 'app_subject_export', methods: ['GET'])]
    public function export(
        Request $request,
        SubjectRepository $subjectRepository
    ): Response {
        // ✅ ACTIVITY LOG
        $this->activityLogger->log('subject.export', 'subject', 'Exported subject list to CSV.');

        $q            = trim((string) $request->query->get('q', ''));
        $courseId     = $request->query->get('courseId');
        $yearLevel    = $request->query->get('yearLevel');
        $semester     = $request->query->get('semester');
        $courseStatus = $request->query->get('courseStatus', 'all');

        $qb = $subjectRepository->createQueryBuilder('s')
            ->leftJoin('s.course', 'c')
            ->addSelect('c');

        if ($q !== '') {
            $qb->andWhere('s.name LIKE :q OR s.code LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($courseId !== null && $courseId !== '') {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', (int) $courseId);
        }

        if ($yearLevel !== null && $yearLevel !== '') {
            $qb->andWhere('s.yearLevel = :yearLevel')
               ->setParameter('yearLevel', (int) $yearLevel);
        }

        if ($semester !== null && $semester !== '') {
            $qb->andWhere('s.semester = :semester')
               ->setParameter('semester', (int) $semester);
        }

        if ($courseStatus === 'active') {
            $qb->andWhere('c.IsActive = :active')
               ->setParameter('active', true);
        } elseif ($courseStatus === 'inactive') {
            $qb->andWhere('c.IsActive = :active')
               ->setParameter('active', false);
        }

        $qb->orderBy('c.name', 'ASC')
           ->addOrderBy('s.yearLevel', 'ASC')
           ->addOrderBy('s.semester', 'ASC')
           ->addOrderBy('s.code', 'ASC');

        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, [
            'ID',
            'Code',
            'Name',
            'Year Level',
            'Semester',
            'Course',
            'Course Status',
            'Description',
        ]);

        /** @var Subject $subject */
        foreach ($rows as $subject) {
            $course      = $subject->getCourse();
            $courseName  = $course ? $course->getName() : '';
            $courseState = $course ? ($course->isActive() ? 'Active' : 'Inactive') : '';

            fputcsv($out, [
                $subject->getId(),
                $subject->getCode(),
                $subject->getName(),
                $subject->getYearLevel(),
                $subject->getSemester(),
                $courseName,
                $courseState,
                $subject->getDescription(),
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response(
            $csv,
            200,
            [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="subjects.csv"',
            ]
        );
    }

    #[Route('/new', name: 'app_subject_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $subject = new Subject();
        $form = $this->createForm(SubjectType::class, $subject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($subject);
            $entityManager->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'subject.created',
                'subject',
                'Created subject ID '.$subject->getId().'.'
            );

            $this->addFlash('success', 'Subject created successfully.');
            return $this->redirectToRoute('app_subject_index');
        }

        return $this->render('subject/new.html.twig', [
            'subject' => $subject,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_subject_show', methods: ['GET'])]
    public function show(Subject $subject): Response
    {
        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'subject.show',
            'subject',
            'Viewed subject ID '.$subject->getId().'.'
        );

        return $this->render('subject/show.html.twig', [
            'subject' => $subject,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_subject_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Subject $subject,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(SubjectType::class, $subject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'subject.updated',
                'subject',
                'Updated subject ID '.$subject->getId().'.'
            );

            $this->addFlash('success', 'Subject updated successfully.');
            return $this->redirectToRoute('app_subject_index');
        }

        return $this->render('subject/edit.html.twig', [
            'subject' => $subject,
            'form'    => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_subject_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Subject $subject,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$subject->getId(), $request->request->get('_token'))) {

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'subject.deleted',
                'subject',
                'Deleted subject ID '.$subject->getId().'.'
            );

            $entityManager->remove($subject);
            $entityManager->flush();

            $this->addFlash('success', 'Subject deleted successfully.');
        }

        return $this->redirectToRoute('app_subject_index');
    }
}
