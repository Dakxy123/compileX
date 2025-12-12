<?php

namespace App\Controller;

use App\Entity\StudentProfile;
use App\Form\StudentProfileType;
use App\Repository\StudentProfileRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[Route('/student/profile')]
final class StudentProfileController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route(name: 'app_student_profile_index', methods: ['GET'])]
    public function index(
        Request $request,
        StudentProfileRepository $studentProfileRepository,
        CourseRepository $courseRepository
    ): Response {
        // ✅ Allow ADMIN or INSTRUCTOR (read-only access for instructors)
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_INSTRUCTOR')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'student_profile.index',
            'student_profile',
            'Viewed student profiles list.'
        );

        $q         = trim((string) $request->query->get('q', ''));
        $courseId  = $request->query->get('courseId');
        $yearLevel = $request->query->get('yearLevel');
        $status    = $request->query->get('status');

        $qb = $studentProfileRepository->createQueryBuilder('sp')
            ->join('sp.user', 'u')->addSelect('u')
            ->join('sp.course', 'c')->addSelect('c');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($courseId !== null && $courseId !== '') {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', (int) $courseId);
        }

        if ($yearLevel !== null && $yearLevel !== '') {
            $qb->andWhere('sp.yearLevel = :yearLevel')
               ->setParameter('yearLevel', (int) $yearLevel);
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('sp.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('c.name', 'ASC')
           ->addOrderBy('sp.yearLevel', 'ASC')
           ->addOrderBy('u.email', 'ASC');

        $studentProfiles = $qb->getQuery()->getResult();
        $courses = $courseRepository->findBy([], ['name' => 'ASC']);

        return $this->render('student_profile/index.html.twig', [
            'studentProfiles' => $studentProfiles,
            'courses'         => $courses,
            'q'               => $q,
            'courseId'        => $courseId,
            'yearLevel'       => $yearLevel,
            'status'          => $status,
        ]);
    }

    #[Route('/export', name: 'app_student_profile_export', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function export(
        Request $request,
        StudentProfileRepository $studentProfileRepository
    ): Response {
        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'student_profile.export',
            'student_profile',
            'Exported student profiles CSV.'
        );

        $q         = trim((string) $request->query->get('q', ''));
        $courseId  = $request->query->get('courseId');
        $yearLevel = $request->query->get('yearLevel');
        $status    = $request->query->get('status');

        $qb = $studentProfileRepository->createQueryBuilder('sp')
            ->join('sp.user', 'u')->addSelect('u')
            ->join('sp.course', 'c')->addSelect('c');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($courseId !== null && $courseId !== '') {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', (int) $courseId);
        }

        if ($yearLevel !== null && $yearLevel !== '') {
            $qb->andWhere('sp.yearLevel = :yearLevel')
               ->setParameter('yearLevel', (int) $yearLevel);
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('sp.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('c.name', 'ASC')
           ->addOrderBy('sp.yearLevel', 'ASC')
           ->addOrderBy('u.email', 'ASC');

        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'User Email', 'Course', 'Year Level', 'Status', 'Created At', 'Updated At']);

        /** @var StudentProfile $sp */
        foreach ($rows as $sp) {
            $year = $sp->getYearLevel();
            $yearLabel = $year < 6 ? 'Year '.$year : 'Year 6+';

            fputcsv($out, [
                $sp->getId(),
                $sp->getUser() ? $sp->getUser()->getEmail() : '',
                $sp->getCourse() ? $sp->getCourse()->getName() : '',
                $yearLabel,
                $sp->getStatus(),
                $sp->getCreatedAt() ? $sp->getCreatedAt()->format('Y-m-d H:i:s') : '',
                $sp->getUpdatedAt() ? $sp->getUpdatedAt()->format('Y-m-d H:i:s') : '',
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
                'Content-Disposition' => 'attachment; filename="student_profiles.csv"',
            ]
        );
    }

    #[Route('/new', name: 'app_student_profile_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $studentProfile = new StudentProfile();
        $form = $this->createForm(StudentProfileType::class, $studentProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $studentProfile->touch();
            $entityManager->persist($studentProfile);
            $entityManager->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'student_profile.created',
                'student_profile',
                'Created student profile ID '.$studentProfile->getId().'.'
            );

            $this->addFlash('success', 'Student profile created successfully.');
            return $this->redirectToRoute('app_student_profile_index');
        }

        return $this->render('student_profile/new.html.twig', [
            'studentProfile' => $studentProfile,
            'form'           => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_profile_show', methods: ['GET'])]
    public function show(StudentProfile $studentProfile): Response
    {
        // ✅ Allow ADMIN or INSTRUCTOR (read-only access for instructors)
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_INSTRUCTOR')) {
            throw $this->createAccessDeniedException('Access Denied.');
        }

        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'student_profile.show',
            'student_profile',
            'Viewed student profile ID '.$studentProfile->getId().'.'
        );

        return $this->render('student_profile/show.html.twig', [
            'studentProfile' => $studentProfile,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_student_profile_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        StudentProfile $studentProfile,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(StudentProfileType::class, $studentProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $studentProfile->touch();
            $entityManager->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'student_profile.updated',
                'student_profile',
                'Updated student profile ID '.$studentProfile->getId().'.'
            );

            $this->addFlash('success', 'Student profile updated successfully.');
            return $this->redirectToRoute('app_student_profile_index');
        }

        return $this->render('student_profile/edit.html.twig', [
            'studentProfile' => $studentProfile,
            'form'           => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_profile_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        StudentProfile $studentProfile,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$studentProfile->getId(), $request->request->get('_token'))) {

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'student_profile.deleted',
                'student_profile',
                'Deleted student profile ID '.$studentProfile->getId().'.'
            );

            $entityManager->remove($studentProfile);
            $entityManager->flush();

            $this->addFlash('success', 'Student profile deleted.');
        }

        return $this->redirectToRoute('app_student_profile_index');
    }
}
