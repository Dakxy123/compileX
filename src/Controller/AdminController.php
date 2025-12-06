<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\CourseRepository;
use App\Repository\SubjectRepository;
use App\Repository\StudentProfileRepository;
use App\Repository\ContactMessageRepository;
use App\Repository\ModuleRepository; // 👈 ADD THIS
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
        ModuleRepository $moduleRepository, // 👈 ADD THIS
    ): Response {
        // --- User stats ---
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

        // Students based on StudentProfile count
        $totalStudentProfiles = $studentProfileRepository->count([]);

        // --- Course stats ---
        $totalCourses = $courseRepository->count([]);

        $activeCourses = (int) $courseRepository->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.IsActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $inactiveCourses = $totalCourses - $activeCourses;

        // --- Subject stats ---
        $totalSubjects = $subjectRepository->count([]);

        // --- Student status stats ---
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

        // --- Contact messages stats ---
        $totalContactMessages = $contactMessageRepository->count([]);

        $latestContactMessages = $contactMessageRepository->createQueryBuilder('m')
            ->orderBy('m.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // --- Module stats ---
        $totalModules = $moduleRepository->count([]);

        $latestModules = $moduleRepository->createQueryBuilder('m2')
            ->orderBy('m2.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // --- Latest items for preview tables ---
        $latestUsers = $userRepository->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestCourses = $courseRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestSubjects = $subjectRepository->createQueryBuilder('s')
            ->orderBy('s.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $latestStudentProfiles = $studentProfileRepository->createQueryBuilder('sp3')
            ->orderBy('sp3.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'totalUsers'             => $totalUsers,
            'totalAdmins'            => $totalAdmins,
            'totalInstructors'       => $totalInstructors,
            'totalStudentProfiles'   => $totalStudentProfiles,
            'totalCourses'           => $totalCourses,
            'activeCourses'          => $activeCourses,
            'inactiveCourses'        => $inactiveCourses,
            'totalSubjects'          => $totalSubjects,
            'ongoingStudents'        => $ongoingStudents,
            'completedStudents'      => $completedStudents,
            'totalContactMessages'   => $totalContactMessages,
            'totalModules'           => $totalModules,        
            'latestUsers'            => $latestUsers,
            'latestCourses'          => $latestCourses,
            'latestSubjects'         => $latestSubjects,
            'latestStudentProfiles'  => $latestStudentProfiles,
            'latestContactMessages'  => $latestContactMessages,
            'latestModules'          => $latestModules,       
        ]);
    }
}
