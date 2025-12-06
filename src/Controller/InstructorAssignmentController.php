<?php

namespace App\Controller;

use App\Entity\InstructorAssignment;
use App\Form\InstructorAssignmentType;
use App\Repository\InstructorAssignmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/instructor-assignment')]
#[IsGranted('ROLE_ADMIN')]
final class InstructorAssignmentController extends AbstractController
{
    #[Route('/', name: 'app_instructor_assignment_index', methods: ['GET'])]
    public function index(Request $request, InstructorAssignmentRepository $repository): Response
    {
        $search    = trim((string) $request->query->get('q', ''));
        $primary   = $request->query->get('primary'); // '', '1', '0'

        $qb = $repository->createQueryBuilder('ia')
            ->leftJoin('ia.instructor', 'i')->addSelect('i')
            ->leftJoin('ia.subject', 's')->addSelect('s')
            ->leftJoin('s.course', 'c')->addSelect('c')
            ->orderBy('ia.createdAt', 'DESC');

        if ($search !== '') {
            $qb
                ->andWhere(
                    'i.email LIKE :search 
                     OR s.name LIKE :search 
                     OR s.code LIKE :search 
                     OR c.name LIKE :search'
                )
                ->setParameter('search', '%' . $search . '%');
        }

        if ($primary !== null && $primary !== '') {
            $qb
                ->andWhere('ia.isPrimary = :primary')
                ->setParameter('primary', $primary === '1');
        }

        $assignments = $qb->getQuery()->getResult();

        return $this->render('instructor_assignment/index.html.twig', [
            'assignments'      => $assignments,
            'primaryFilter'    => $primary,
        ]);
    }

    #[Route('/new', name: 'app_instructor_assignment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $assignment = new InstructorAssignment();

        $form = $this->createForm(InstructorAssignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($assignment);
            $em->flush();

            $this->addFlash('success', 'Instructor assignment created successfully.');

            return $this->redirectToRoute('app_instructor_assignment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('instructor_assignment/new.html.twig', [
            'assignment' => $assignment,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_instructor_assignment_show', methods: ['GET'])]
    public function show(InstructorAssignment $assignment): Response
    {
        return $this->render('instructor_assignment/show.html.twig', [
            'assignment' => $assignment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_instructor_assignment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, InstructorAssignment $assignment, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(InstructorAssignmentType::class, $assignment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Instructor assignment updated successfully.');

            return $this->redirectToRoute('app_instructor_assignment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('instructor_assignment/edit.html.twig', [
            'assignment' => $assignment,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_instructor_assignment_delete', methods: ['POST'])]
    public function delete(Request $request, InstructorAssignment $assignment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $assignment->getId(), $request->request->get('_token'))) {
            $em->remove($assignment);
            $em->flush();
            $this->addFlash('success', 'Instructor assignment deleted successfully.');
        }

        return $this->redirectToRoute('app_instructor_assignment_index', [], Response::HTTP_SEE_OTHER);
    }
}
