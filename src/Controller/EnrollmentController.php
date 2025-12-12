<?php

namespace App\Controller;

use App\Entity\Enrollment;
use App\Form\EnrollmentType;
use App\Repository\EnrollmentRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/enrollment')]
#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_INSTRUCTOR")'))]
final class EnrollmentController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'app_enrollment_index', methods: ['GET'])]
    public function index(Request $request, EnrollmentRepository $enrollmentRepository): Response
    {
        $this->activityLogger->log(
            'enrollment.viewed',
            'enrollment',
            'Viewed enrollment list.'
        );

        $q      = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', 'all');

        $qb = $enrollmentRepository->createQueryBuilder('e')
            ->leftJoin('e.subject', 's')->addSelect('s')
            ->leftJoin('s.course', 'c')->addSelect('c')
            ->leftJoin('e.studentProfile', 'sp')->addSelect('sp')
            ->leftJoin('sp.user', 'u')->addSelect('u');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q OR s.name LIKE :q OR c.name LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== 'all' && $status !== null && $status !== '') {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('e.id', 'DESC');

        $enrollments = $qb->getQuery()->getResult();

        return $this->render('enrollment/index.html.twig', [
            'enrollments' => $enrollments,
            'q'           => $q,
            'status'      => $status,
        ]);
    }

    #[Route('/export', name: 'app_enrollment_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function export(Request $request, EnrollmentRepository $enrollmentRepository): Response
    {
        $this->activityLogger->log(
            'enrollment.exported',
            'enrollment',
            'Exported enrollment list to CSV.'
        );

        $q      = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', 'all');

        $qb = $enrollmentRepository->createQueryBuilder('e')
            ->leftJoin('e.subject', 's')->addSelect('s')
            ->leftJoin('s.course', 'c')->addSelect('c')
            ->leftJoin('e.studentProfile', 'sp')->addSelect('sp')
            ->leftJoin('sp.user', 'u')->addSelect('u');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q OR s.name LIKE :q OR c.name LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== 'all' && $status !== null && $status !== '') {
            $qb->andWhere('e.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('e.id', 'DESC');
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Student Email', 'Course', 'Subject', 'Status']);

        /** @var Enrollment $enrollment */
        foreach ($rows as $enrollment) {
            $studentUser = $enrollment->getStudentProfile()?->getUser();
            $subject     = $enrollment->getSubject();
            $course      = $subject?->getCourse();

            fputcsv($out, [
                $enrollment->getId(),
                $studentUser ? $studentUser->getEmail() : '',
                $course ? $course->getName() : '',
                $subject ? $subject->getName() : '',
                $enrollment->getStatus(),
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
                'Content-Disposition' => 'attachment; filename="enrollments.csv"',
            ]
        );
    }

    #[Route('/new', name: 'app_enrollment_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $enrollment = new Enrollment();
        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($enrollment);
            $em->flush();

            $this->activityLogger->log(
                'enrollment.created',
                'enrollment',
                'Created enrollment ID '.$enrollment->getId().'.'
            );

            $this->addFlash('success', 'Enrollment created successfully.');
            return $this->redirectToRoute('app_enrollment_index');
        }

        return $this->render('enrollment/new.html.twig', [
            'enrollment' => $enrollment,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enrollment_show', methods: ['GET'])]
    public function show(Enrollment $enrollment): Response
    {
        $this->activityLogger->log(
            'enrollment.viewed_one',
            'enrollment',
            'Viewed enrollment ID '.$enrollment->getId().'.'
        );

        return $this->render('enrollment/show.html.twig', [
            'enrollment' => $enrollment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enrollment_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Enrollment $enrollment, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->activityLogger->log(
                'enrollment.updated',
                'enrollment',
                'Updated enrollment ID '.$enrollment->getId().'.'
            );

            $this->addFlash('success', 'Enrollment updated successfully.');
            return $this->redirectToRoute('app_enrollment_index');
        }

        return $this->render('enrollment/edit.html.twig', [
            'enrollment' => $enrollment,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enrollment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Enrollment $enrollment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$enrollment->getId(), (string) $request->request->get('_token'))) {

            $id = $enrollment->getId();

            $em->remove($enrollment);
            $em->flush();

            $this->activityLogger->log(
                'enrollment.deleted',
                'enrollment',
                'Deleted enrollment ID '.$id.'.'
            );

            $this->addFlash('success', 'Enrollment deleted.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_enrollment_index');
    }
}
