<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/instructor')]
#[IsGranted('ROLE_INSTRUCTOR')]
final class InstructorDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_instructor_dashboard')]
    public function index(): Response
    {
        return $this->render('instructor_dashboard/index.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}
