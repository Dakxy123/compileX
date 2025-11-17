<?php

namespace App\Controller;

use App\Repository\InstructorsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[isGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    public function index(InstructorsRepository $instructorsRepository): Response
    {
        $instructorCount        = $instructorsRepository->count([]);
        $activeInstructorCount  = $instructorsRepository->count(['isActive' => true]);
        $inactiveInstructorCount= $instructorsRepository->count(['isActive' => false]);

        $recentInstructors = $instructorsRepository->createQueryBuilder('i')
            ->orderBy('i.id', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/index.html.twig', [
            'instructorCount'         => $instructorCount,
            'activeInstructorCount'   => $activeInstructorCount,
            'inactiveInstructorCount' => $inactiveInstructorCount,
            'recentInstructors'       => $recentInstructors,
        ]);
    }
}