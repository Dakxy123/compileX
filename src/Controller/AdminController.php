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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
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
    ): Response {
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

        $latestModules = $moduleRepository->createQueryBuilder('m2')
            ->orderBy('m2.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $totalEnrollments = $enrollmentRepository->count([]);

        $latestEnrollments = $enrollmentRepository->createQueryBuilder('e')
            ->leftJoin('e.studentProfile', 'sp')->addSelect('sp')
            ->leftJoin('sp.user', 'u')->addSelect('u')
            ->leftJoin('e.subject', 's')->addSelect('s')
            ->leftJoin('s.course', 'c')->addSelect('c')
            ->orderBy('e.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $totalInstructorAssignments = $instructorAssignmentRepository->count([]);

        $latestInstructorAssignments = $instructorAssignmentRepository->createQueryBuilder('ia')
            ->leftJoin('ia.instructor', 'instr')->addSelect('instr')
            ->leftJoin('ia.subject', 'subj')->addSelect('subj')
            ->leftJoin('subj.course', 'course')->addSelect('course')
            ->orderBy('ia.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $totalActivityLogs = $activityLogRepository->count([]);

        $latestActivityLogs = $activityLogRepository->createQueryBuilder('al')
            ->leftJoin('al.user', 'alu')->addSelect('alu')
            ->orderBy('al.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestUsers = $userRepository->createQueryBuilder('u3')
            ->orderBy('u3.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestCourses = $courseRepository->createQueryBuilder('c2')
            ->orderBy('c2.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestSubjects = $subjectRepository->createQueryBuilder('s2')
            ->orderBy('s2.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestStudentProfiles = $studentProfileRepository->createQueryBuilder('sp3')
            ->orderBy('sp3.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'totalUsers'                  => $totalUsers,
            'totalAdmins'                 => $totalAdmins,
            'totalInstructors'            => $totalInstructors,
            'totalStudentProfiles'        => $totalStudentProfiles,
            'totalCourses'                => $totalCourses,
            'activeCourses'               => $activeCourses,
            'inactiveCourses'             => $inactiveCourses,
            'totalSubjects'               => $totalSubjects,
            'ongoingStudents'             => $ongoingStudents,
            'completedStudents'           => $completedStudents,
            'totalContactMessages'        => $totalContactMessages,
            'totalModules'                => $totalModules,
            'totalEnrollments'            => $totalEnrollments,
            'totalInstructorAssignments'  => $totalInstructorAssignments,
            'totalActivityLogs'           => $totalActivityLogs,
            'latestUsers'                 => $latestUsers,
            'latestCourses'               => $latestCourses,
            'latestSubjects'              => $latestSubjects,
            'latestStudentProfiles'       => $latestStudentProfiles,
            'latestContactMessages'       => $latestContactMessages,
            'latestModules'               => $latestModules,
            'latestEnrollments'           => $latestEnrollments,
            'latestInstructorAssignments' => $latestInstructorAssignments,
            'latestActivityLogs'          => $latestActivityLogs,
        ]);
    }
}
