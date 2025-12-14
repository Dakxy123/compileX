<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\CourseRepository;
use App\Repository\SubjectRepository;
use App\Repository\StudentProfileRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\ModuleRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\InstructorAssignmentRepository;
use App\Repository\ActivityLogRepository;
use App\Repository\InstructorApplicationRepository;
use App\Service\ActivityLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('', name: 'app_admin', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        CourseRepository $courseRepository,
        SubjectRepository $subjectRepository,
        StudentProfileRepository $studentProfileRepository,
        ContactMessageRepository $contactMessageRepository,
        ModuleRepository $moduleRepository,
        EnrollmentRepository $enrollmentRepository,
        InstructorAssignmentRepository $instructorAssignmentRepository,
        ActivityLogRepository $activityLogRepository,
        InstructorApplicationRepository $instructorApplicationRepository,
    ): Response {

        $this->activityLogger->log(
            'admin.dashboard.view',
            'admin',
            'Viewed admin dashboard'
        );

        $totalUsers = $userRepository->count([]);

        $totalAdmins = (int) $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_ADMIN"%')
            ->getQuery()
            ->getSingleScalarResult();

        $totalInstructors = (int) $userRepository->createQueryBuilder('u2')
            ->select('COUNT(u2.id)')
            ->andWhere('u2.roles LIKE :role')
            ->setParameter('role', '%"ROLE_INSTRUCTOR"%')
            ->getQuery()
            ->getSingleScalarResult();

        $totalStudentProfiles = $studentProfileRepository->count([]);
        $totalCourses = $courseRepository->count([]);

        $activeCourses = (int) $courseRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.IsActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $inactiveCourses = $totalCourses - $activeCourses;

        $totalSubjects = $subjectRepository->count([]);

        $ongoingStudents = (int) $studentProfileRepository->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->andWhere('sp.status = :status')
            ->setParameter('status', 'Ongoing')
            ->getQuery()
            ->getSingleScalarResult();

        $completedStudents = (int) $studentProfileRepository->createQueryBuilder('sp2')
            ->select('COUNT(sp2.id)')
            ->andWhere('sp2.status = :status')
            ->setParameter('status', 'Completed')
            ->getQuery()
            ->getSingleScalarResult();

        $totalContactMessages = $contactMessageRepository->count([]);

        $latestContactMessages = $contactMessageRepository->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $totalModules = $moduleRepository->count([]);
        $totalEnrollments = $enrollmentRepository->count([]);
        $totalInstructorAssignments = $instructorAssignmentRepository->count([]);
        $totalActivityLogs = $activityLogRepository->count([]);

        $latestUsers = $userRepository->findBy([], ['id' => 'DESC'], 5);
        $latestCourses = $courseRepository->findBy([], ['id' => 'DESC'], 5);
        $latestSubjects = $subjectRepository->findBy([], ['id' => 'DESC'], 5);
        $latestModules = $moduleRepository->findBy([], ['id' => 'DESC'], 5);
        $latestStudentProfiles = $studentProfileRepository->findBy([], ['id' => 'DESC'], 5);

        $latestEnrollments = $enrollmentRepository->createQueryBuilder('e')
            ->leftJoin('e.studentProfile', 'sp')->addSelect('sp')
            ->leftJoin('sp.user', 'u')->addSelect('u')
            ->leftJoin('e.subject', 's')->addSelect('s')
            ->leftJoin('s.course', 'c')->addSelect('c')
            ->orderBy('e.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestInstructorAssignments = $instructorAssignmentRepository->createQueryBuilder('ia')
            ->leftJoin('ia.instructor', 'instr')->addSelect('instr')
            ->leftJoin('ia.subject', 'subj')->addSelect('subj')
            ->leftJoin('subj.course', 'course')->addSelect('course')
            ->orderBy('ia.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestActivityLogs = $activityLogRepository->createQueryBuilder('al')
            ->leftJoin('al.user', 'alu')->addSelect('alu')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $pendingInstructorApplications = (int) $instructorApplicationRepository->createQueryBuilder('ia2')
            ->select('COUNT(ia2.id)')
            ->andWhere('ia2.status = :status')
            ->setParameter('status', 'PENDING')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/index.html.twig', [
            'totalUsers'                    => $totalUsers,
            'totalAdmins'                   => $totalAdmins,
            'totalInstructors'              => $totalInstructors,
            'totalStudentProfiles'          => $totalStudentProfiles,
            'totalCourses'                  => $totalCourses,
            'activeCourses'                 => $activeCourses,
            'inactiveCourses'               => $inactiveCourses,
            'totalSubjects'                 => $totalSubjects,
            'ongoingStudents'               => $ongoingStudents,
            'completedStudents'             => $completedStudents,
            'totalContactMessages'          => $totalContactMessages,
            'latestContactMessages'         => $latestContactMessages,
            'totalModules'                  => $totalModules,
            'totalEnrollments'              => $totalEnrollments,
            'totalInstructorAssignments'    => $totalInstructorAssignments,
            'totalActivityLogs'             => $totalActivityLogs,
            'latestUsers'                   => $latestUsers,
            'latestCourses'                 => $latestCourses,
            'latestSubjects'                => $latestSubjects,
            'latestStudentProfiles'         => $latestStudentProfiles,
            'latestModules'                 => $latestModules,
            'latestEnrollments'             => $latestEnrollments,
            'latestInstructorAssignments'   => $latestInstructorAssignments,
            'latestActivityLogs'            => $latestActivityLogs,
            'pendingInstructorApplications' => $pendingInstructorApplications,
        ]);
    }
}
