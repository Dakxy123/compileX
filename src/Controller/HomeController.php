<?php

namespace App\Controller;

use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ActivityLogger;

final class HomeController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'home')]
    public function index(
        CourseRepository $courseRepository,
        UserRepository $userRepository
    ): Response {
        $this->activityLogger->log(
            'home.viewed',
            'public',
            'Visited home page.'
        );

        $courses = $courseRepository->createQueryBuilder('c')
            ->select('c.name AS name')
            ->andWhere('c.IsActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $instructors = $userRepository->createQueryBuilder('u')
            ->select('u.id AS id, u.email AS email')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_INSTRUCTOR"%')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults(9)
            ->getQuery()
            ->getArrayResult();

        return $this->render('home/index.html.twig', [
            'courses'     => $courses,
            'instructors' => $instructors,
        ]);
    }
}
