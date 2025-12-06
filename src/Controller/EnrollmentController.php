<?php

namespace App\Controller;

use App\Entity\Enrollment;
use App\Form\EnrollmentType;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/enrollment')]
#[IsGranted('ROLE_ADMIN')]
final class EnrollmentController extends AbstractController
{
    #[Route('/', name: 'app_enrollment_index', methods: ['GET'])]
    public function index(Request $request, EnrollmentRepository $enrollmentRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status');

        $qb = $enrollmentRepository->createQueryBuilder('e')
            ->leftJoin('e.studentProfile', 'sp')->addSelect('sp')
            ->leftJoin('sp.user', 'u')->addSelect('u')
            ->leftJoin('e.subject', 's')->addSelect('s')
            ->leftJoin('s.course', 'c')->addSelect('c')
            ->orderBy('e.id', 'DESC'); // avoid createdAt error

        if ($search !== '') {
            $qb
                ->andWhere('u.email LIKE :search OR s.name LIKE :search OR s.code LIKE :search OR c.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $qb
                ->andWhere('e.status = :status')
                ->setParameter('status', $status);
        }

        $enrollments = $qb->getQuery()->getResult();

        $statuses = ['Enrolled', 'Ongoing', 'Completed', 'Dropped'];

        return $this->render('enrollment/index.html.twig', [
            'enrollments' => $enrollments,
            'statuses'    => $statuses,
        ]);
    }

    #[Route('/new', name: 'app_enrollment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $enrollment = new Enrollment();

        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($enrollment);
            $em->flush();

            $this->addFlash('success', 'Enrollment created successfully.');

            return $this->redirectToRoute('app_enrollment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enrollment/new.html.twig', [
            'enrollment' => $enrollment,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enrollment_show', methods: ['GET'])]
    public function show(Enrollment $enrollment): Response
    {
        return $this->render('enrollment/show.html.twig', [
            'enrollment' => $enrollment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_enrollment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enrollment $enrollment, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Enrollment updated successfully.');

            return $this->redirectToRoute('app_enrollment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enrollment/edit.html.twig', [
            'enrollment' => $enrollment,
            'form'       => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enrollment_delete', methods: ['POST'])]
    public function delete(Request $request, Enrollment $enrollment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $enrollment->getId(), $request->request->get('_token'))) {
            $em->remove($enrollment);
            $em->flush();
            $this->addFlash('success', 'Enrollment deleted successfully.');
        }

        return $this->redirectToRoute('app_enrollment_index', [], Response::HTTP_SEE_OTHER);
    }
}
