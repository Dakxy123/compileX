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
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/instructors')]
#[isGranted('ROLE_ADMIN')]
final class InstructorsController extends AbstractController
{
    #[Route(name: 'app_instructors_index', methods: ['GET'])]
    public function index(Request $request, InstructorsRepository $instructorsRepository): Response 
    {
        $q = trim((string) $request->query->get('q', '')); 
        $status = (string) $request->query->get('status', ''); 
        $qb = $instructorsRepository->createQueryBuilder('i'); 
        if ($q !== '') { 
            $qb->andWhere('i.first_name LIKE :q OR i.last_name LIKE :q OR i.email LIKE :q') 
               ->setParameter('q', '%'.$q.'%'); 
        } 
        if ($status !== '') { 
            $qb->andWhere('i.isActive = :s')->setParameter('s', $status === '1'); 
        } 
        $qb->orderBy('i.last_name', 'ASC')->addOrderBy('i.first_name', 'ASC'); 
        $instructors = $qb->getQuery()->getResult(); 

        return $this->render('instructors/index.html.twig', [
            'instructors' => $instructors, 
            'q' => $q, 
            'status' => $status, 
        ]);
    }

    #[Route('/export', name: 'app_instructors_export', methods: ['GET'])] 
    public function export(Request $request, InstructorsRepository $instructorsRepository): Response 
    { 
        $q = trim((string) $request->query->get('q', '')); 
        $status = (string) $request->query->get('status', ''); 
        $qb = $instructorsRepository->createQueryBuilder('i'); 
        if ($q !== '') { 
            $qb->andWhere('i.first_name LIKE :q OR i.last_name LIKE :q OR i.email LIKE :q') 
               ->setParameter('q', '%'.$q.'%'); 
        } 
        if ($status !== '') { 
            $qb->andWhere('i.isActive = :s')->setParameter('s', $status === '1'); 
        } 
        $qb->orderBy('i.last_name', 'ASC')->addOrderBy('i.first_name', 'ASC'); 
        $rows = $qb->getQuery()->getResult(); 

        $out = fopen('php://temp', 'r+'); 
        fputcsv($out, ['ID','First name','Middle name','Last name','Email','Active']); 
        /** @var \App\Entity\Instructors $row */ 
        foreach ($rows as $row) { 
            fputcsv($out, [ 
                $row->getId(), 
                $row->getFirstName(), 
                $row->getMiddleName(), 
                $row->getLastName(), 
                $row->getEmail(), 
                $row->isActive() ? 'Yes' : 'No', 
            ]); 
        } 
        rewind($out); 
        $csv = stream_get_contents($out); 
        fclose($out); 

        $response = new Response($csv); 
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8'); 
        $response->headers->set('Content-Disposition', 'attachment; filename="instructors.csv"'); 
        return $response; 
    } 

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