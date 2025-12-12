<?php

namespace App\Controller;

use App\Repository\StudentProfileRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\ModuleRepository;
use App\Repository\InstructorApplicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[Route('/student')]
#[IsGranted(<?php

namespace App\Controller;

use App\Repository\StudentProfileRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\ModuleRepository;
use App\Repository\InstructorApplicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[Route('/student')]
#[IsGranted('ROLE_USER')]
final class StudentDashboardController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('', name: 'app_student_dashboard', methods: ['GET'])]
    public function index(
        StudentProfileRepository $studentProfileRepository,
        EnrollmentRepository $enrollmentRepository,
        ModuleRepository $moduleRepository,
        InstructorApplicationRepository $applicationRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'student.dashboard.view',
            'student_dashboard',
            'Viewed student dashboard.'
        );

        // 1) Student profile (if naa)
        $studentProfile = $studentProfileRepository->findOneBy(['user' => $user]);

        // 2) Enrollments for this student
        $enrollments = [];
        if ($studentProfile) {
            $enrollments = $enrollmentRepository->createQueryBuilder('e')
                ->leftJoin('e.subject', 's')->addSelect('s')
                ->leftJoin('s.course', 'c')->addSelect('c')
                ->andWhere('e.studentProfile = :sp')
                ->setParameter('sp', $studentProfile)
                ->orderBy('s.code', 'ASC')
                ->getQuery()
                ->getResult();
        }

        // 3) Modules related to enrolled subjects
        $modules = [];
        if (!empty($enrollments)) {
            $subjectIds = [];
            foreach ($enrollments as $enrollment) {
                $subject = $enrollment->getSubject();
                if ($subject && $subject->getId()) {
                    $subjectIds[] = $subject->getId();
                }
            }
            $subjectIds = array_unique($subjectIds);

            if (!empty($subjectIds)) {
                $modules = $moduleRepository->createQueryBuilder('m')
                    ->leftJoin('m.subject', 'ms')->addSelect('ms')
                    ->andWhere('ms.id IN (:ids)')
                    ->setParameter('ids', $subjectIds)
                    ->orderBy('ms.code', 'ASC')
                    ->addOrderBy('m.id', 'ASC')
                    ->getQuery()
                    ->getResult();
            }
        }

        // 4) Latest Instructor Application Status (for UX)
        $existingApplication = $applicationRepository->findOneBy(
            ['applicant' => $user],
            ['id' => 'DESC']
        );

        // 5) If user already has ROLE_INSTRUCTOR, show banner + quick link
        $isInstructor = in_array('ROLE_INSTRUCTOR', $user->getRoles(), true);

        return $this->render('student_dashboard/index.html.twig', [
            'studentProfile'    => $studentProfile,
            'enrollments'       => $enrollments,
            'modules'           => $modules,
            'hasApplication'    => $existingApplication !== null,
            'application'       => $existingApplication,
            'applicationStatus' => $existingApplication?->getStatus(),
            'isInstructor'      => $isInstructor,
        ]);
    }
}
'ROLE_USER')]
final class StudentDashboardController extends AbstractController
{
    #[Route('', name: 'app_student_dashboard', methods: ['GET'])]
    public function index(
        StudentProfileRepository $studentProfileRepository,
        EnrollmentRepository $enrollmentRepository,
        ModuleRepository $moduleRepository,
        InstructorApplicationRepository $applicationRepository,
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // 🔔 ACTIVITY LOG (placeholder - enable later)
        // $this->activityLog->log('student.dashboard.view', $user);

        // 1) Student profile (if naa)
        $studentProfile = $studentProfileRepository->findOneBy(['user' => $user]);

        // 2) Enrollments for this student
        $enrollments = [];
        if ($studentProfile) {
            $enrollments = $enrollmentRepository->createQueryBuilder('e')
                ->leftJoin('e.subject', 's')->addSelect('s')
                ->leftJoin('s.course', 'c')->addSelect('c')
                ->andWhere('e.studentProfile = :sp')
                ->setParameter('sp', $studentProfile)
                ->orderBy('s.code', 'ASC')
                ->getQuery()
                ->getResult();
        }

        // 3) Modules related to enrolled subjects
        $modules = [];
        if (!empty($enrollments)) {
            $subjectIds = [];
            foreach ($enrollments as $enrollment) {
                $subject = $enrollment->getSubject();
                if ($subject && $subject->getId()) {
                    $subjectIds[] = $subject->getId();
                }
            }
            $subjectIds = array_unique($subjectIds);

            if (!empty($subjectIds)) {
                $modules = $moduleRepository->createQueryBuilder('m')
                    ->leftJoin('m.subject', 'ms')->addSelect('ms')
                    ->andWhere('ms.id IN (:ids)')
                    ->setParameter('ids', $subjectIds)
                    ->orderBy('ms.code', 'ASC')
                    ->addOrderBy('m.id', 'ASC')
                    ->getQuery()
                    ->getResult();
            }
        }

        // 4) Latest Instructor Application Status (for UX)
        $existingApplication = $applicationRepository->findOneBy(
            ['applicant' => $user],
            ['id' => 'DESC']
        );

        // 5) If user already has ROLE_INSTRUCTOR, show banner + quick link
        $isInstructor = in_array('ROLE_INSTRUCTOR', $user->getRoles(), true);

        return $this->render('student_dashboard/index.html.twig', [
            'studentProfile'      => $studentProfile,
            'enrollments'         => $enrollments,
            'modules'             => $modules,
            'hasApplication'      => $existingApplication !== null,
            'application'         => $existingApplication,
            'applicationStatus'   => $existingApplication?->getStatus(),
            'isInstructor'        => $isInstructor,
        ]);
    }
}
