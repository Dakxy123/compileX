<?php

namespace App\Controller;

use App\Entity\Instructors;
use App\Form\InstructorsType;
use App\Repository\InstructorsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/instructors')]
final class InstructorsController extends AbstractController
{
    #[Route(name: 'app_instructors_index', methods: ['GET'])]
    public function index(Request $request, InstructorsRepository $instructorsRepository): Response # CHANGED
    {
        $q = trim((string) $request->query->get('q', '')); # ADDED
        $status = (string) $request->query->get('status', ''); # ADDED
        $qb = $instructorsRepository->createQueryBuilder('i'); # ADDED
        if ($q !== '') { # ADDED
            $qb->andWhere('i.first_name LIKE :q OR i.last_name LIKE :q OR i.email LIKE :q') # ADDED
               ->setParameter('q', '%'.$q.'%'); # ADDED
        } # ADDED
        if ($status !== '') { # ADDED
            $qb->andWhere('i.isActive = :s')->setParameter('s', $status === '1'); # ADDED
        } # ADDED
        $qb->orderBy('i.last_name', 'ASC')->addOrderBy('i.first_name', 'ASC'); # ADDED
        $instructors = $qb->getQuery()->getResult(); # ADDED

        return $this->render('instructors/index.html.twig', [
            'instructors' => $instructors, # CHANGED
            'q' => $q, # ADDED
            'status' => $status, # ADDED
        ]);
    }

    #[Route('/export', name: 'app_instructors_export', methods: ['GET'])] # ADDED
    public function export(Request $request, InstructorsRepository $instructorsRepository): Response # ADDED
    { # ADDED
        $q = trim((string) $request->query->get('q', '')); # ADDED
        $status = (string) $request->query->get('status', ''); # ADDED
        $qb = $instructorsRepository->createQueryBuilder('i'); # ADDED
        if ($q !== '') { # ADDED
            $qb->andWhere('i.first_name LIKE :q OR i.last_name LIKE :q OR i.email LIKE :q') # ADDED
               ->setParameter('q', '%'.$q.'%'); # ADDED
        } # ADDED
        if ($status !== '') { # ADDED
            $qb->andWhere('i.isActive = :s')->setParameter('s', $status === '1'); # ADDED
        } # ADDED
        $qb->orderBy('i.last_name', 'ASC')->addOrderBy('i.first_name', 'ASC'); # ADDED
        $rows = $qb->getQuery()->getResult(); # ADDED

        $out = fopen('php://temp', 'r+'); # ADDED
        fputcsv($out, ['ID','First name','Middle name','Last name','Email','Active']); # ADDED
        /** @var \App\Entity\Instructors $row */ # ADDED
        foreach ($rows as $row) { # ADDED
            fputcsv($out, [ # ADDED
                $row->getId(), # ADDED
                $row->getFirstName(), # ADDED
                $row->getMiddleName(), # ADDED
                $row->getLastName(), # ADDED
                $row->getEmail(), # ADDED
                $row->isActive() ? 'Yes' : 'No', # ADDED
            ]); # ADDED
        } # ADDED
        rewind($out); # ADDED
        $csv = stream_get_contents($out); # ADDED
        fclose($out); # ADDED

        $response = new Response($csv); # ADDED
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8'); # ADDED
        $response->headers->set('Content-Disposition', 'attachment; filename="instructors.csv"'); # ADDED
        return $response; # ADDED
    } # ADDED

    #[Route('/new', name: 'app_instructors_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $instructor = new Instructors();
        $instructor->setIsActive(true);
        $form = $this->createForm(InstructorsType::class, $instructor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $raw = (string) $form->get('password')->getData();
            if ($raw !== '') {
                $instructor->setPassword($hasher->hashPassword($instructor, $raw));
            }
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