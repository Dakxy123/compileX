<?php

namespace App\Controller;

use App\Repository\InstructorsRepository;
use App\Repository\CourseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        InstructorsRepository $instructorsRepository,
        CourseRepository $courseRepository
    ): Response {
        // Instructor stats
        $instructorCount         = $instructorsRepository->count([]);
        $activeInstructorCount   = $instructorsRepository->count(['isActive' => true]);
        $inactiveInstructorCount = $instructorsRepository->count(['isActive' => false]);

        // Recent instructors (last 5)
        $recentInstructors = $instructorsRepository->createQueryBuilder('i')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Recent courses (last 5)
        $recentCourses = $courseRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'instructorCount'         => $instructorCount,
            'activeInstructorCount'   => $activeInstructorCount,
            'inactiveInstructorCount' => $inactiveInstructorCount,
            'recentInstructors'       => $recentInstructors,
            'recentCourses'           => $recentCourses,
        ]);
    }
}
