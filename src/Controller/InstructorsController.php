<?php

namespace App\Controller;

use App\Entity\Instructors;
use App\Form\InstructorsType;
use App\Repository\InstructorsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/instructors')]
final class InstructorsController extends AbstractController
{
    #[Route(name: 'app_instructors_index', methods: ['GET'])]
    public function index(InstructorsRepository $instructorsRepository): Response
    {
        return $this->render('instructors/index.html.twig', [
            'instructors' => $instructorsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_instructors_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $instructor = new Instructors();
        $form = $this->createForm(InstructorsType::class, $instructor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($instructor);
            $entityManager->flush();

            return $this->redirectToRoute('app_instructors_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('instructors/new.html.twig', [
            'instructor' => $instructor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_instructors_show', methods: ['GET'])]
    public function show(Instructors $instructor): Response
    {
        return $this->render('instructors/show.html.twig', [
            'instructor' => $instructor,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_instructors_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Instructors $instructor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InstructorsType::class, $instructor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_instructors_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('instructors/edit.html.twig', [
            'instructor' => $instructor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_instructors_delete', methods: ['POST'])]
    public function delete(Request $request, Instructors $instructor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$instructor->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($instructor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_instructors_index', [], Response::HTTP_SEE_OTHER);
    }
}
