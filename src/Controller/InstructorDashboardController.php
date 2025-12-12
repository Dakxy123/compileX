<?php

namespace App\Controller;

use App\Repository\EnrollmentRepository;
use App\Repository\InstructorAssignmentRepository;
use App\Repository\ModuleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[Route('/instructor')]
#[IsGranted('ROLE_INSTRUCTOR')]
final class InstructorDashboardController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/dashboard', name: 'app_instructor_dashboard', methods: ['GET'])]
    public function index(
        InstructorAssignmentRepository $instructorAssignmentRepository,
        EnrollmentRepository $enrollmentRepository,
        ModuleRepository $moduleRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'instructor.dashboard.view',
            'instructor_dashboard',
            'Viewed instructor dashboard.'
        );

        // 1) Total assigned subjects for this instructor
        $totalAssignedSubjects = (int) $instructorAssignmentRepository->createQueryBuilder('ia')
            ->select('COUNT(DISTINCT subj.id)')
            ->leftJoin('ia.subject', 'subj')
            ->andWhere('ia.instructor = :instr')
            ->setParameter('instr', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // 2) Distinct courses covered by those subjects
        $totalAssignedCourses = (int) $instructorAssignmentRepository->createQueryBuilder('ia2')
            ->select('COUNT(DISTINCT c.id)')
            ->leftJoin('ia2.subject', 'subj2')
            ->leftJoin('subj2.course', 'c')
            ->andWhere('ia2.instructor = :instr')
            ->setParameter('instr', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // 3) Modules under the subjects handled by this instructor
        $totalAssignedModules = (int) $moduleRepository->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.id)')
            ->leftJoin('m.subject', 's')
            ->leftJoin(
                'App\Entity\InstructorAssignment',
                'ia3',
                'WITH',
                'ia3.subject = s AND ia3.instructor = :instr'
            )
            ->setParameter('instr', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // 4) Enrollments in the subjects handled by this instructor
        $totalInstructorEnrollments = (int) $enrollmentRepository->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->leftJoin('e.subject', 's2')
            ->leftJoin(
                'App\Entity\InstructorAssignment',
                'ia4',
                'WITH',
                'ia4.subject = s2 AND ia4.instructor = :instr'
            )
            ->setParameter('instr', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // 5) Latest subject assignments for this instructor
        $latestAssignments = $instructorAssignmentRepository->createQueryBuilder('ia5')
            ->leftJoin('ia5.subject', 'subj5')->addSelect('subj5')
            ->leftJoin('subj5.course', 'course5')->addSelect('course5')
            ->andWhere('ia5.instructor = :instr')
            ->setParameter('instr', $user)
            ->orderBy('ia5.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // 6) Latest enrollments in the instructor's subjects
        $latestEnrollments = $enrollmentRepository->createQueryBuilder('e2')
            ->leftJoin('e2.subject', 's3')->addSelect('s3')
            ->leftJoin('s3.course', 'c3')->addSelect('c3')
            ->leftJoin('e2.studentProfile', 'sp')->addSelect('sp')
            ->leftJoin('sp.user', 'stu')->addSelect('stu')
            ->leftJoin(
                'App\Entity\InstructorAssignment',
                'ia6',
                'WITH',
                'ia6.subject = s3 AND ia6.instructor = :instr'
            )
            ->setParameter('instr', $user)
            ->orderBy('e2.id', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('instructor_dashboard/index.html.twig', [
            'user'                       => $user,
            'totalAssignedSubjects'      => $totalAssignedSubjects,
            'totalAssignedCourses'       => $totalAssignedCourses,
            'totalAssignedModules'       => $totalAssignedModules,
            'totalInstructorEnrollments' => $totalInstructorEnrollments,
            'latestAssignments'          => $latestAssignments,
            'latestEnrollments'          => $latestEnrollments,
        ]);
    }
}
