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

#[Route('/enrollment')]
final class EnrollmentController extends AbstractController
{
    #[Route(name: 'app_enrollment_index', methods: ['GET'])]
    public function index(Request $request, EnrollmentRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('e.student LIKE :q OR e.courseOffering LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== '') {
            $qb->andWhere('e.isActive = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('e.id', 'DESC');
        $enrollments = $qb->getQuery()->getResult();

        return $this->render('enrollment/index.html.twig', [
            'enrollments' => $enrollments,
            'q' => $q,
            'status' => $status,
        ]);
    }

    #[Route('/export', name: 'app_enrollment_export', methods: ['GET'])]
    public function export(Request $request, EnrollmentRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('e');

        if ($q !== '') {
            $qb->andWhere('e.student LIKE :q OR e.courseOffering LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== '') {
            $qb->andWhere('e.isActive = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('e.id', 'DESC');
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Student', 'Course Offering', 'Active']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row->getId(),
                $row->getStudent()->getFname() . ' ' . $row->getStudent()->getLname(),
                $row->getCourseOffering()->getCourse()->getName() . ' - ' . $row->getCourseOffering()->getSection()->getName(),
                $row->isActive() ? 'Yes' : 'No',
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="enrollments.csv"');

        return $response;
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

            return $this->redirectToRoute('app_enrollment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enrollment/new.html.twig', [
            'enrollment' => $enrollment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enrollment_show', methods: ['GET'])]
    public function show(Enrollment $enrollment): Response
    {
        return $this->render('enrollment/show.html.twig', ['enrollment' => $enrollment]);
    }

    #[Route('/{id}/edit', name: 'app_enrollment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enrollment $enrollment, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EnrollmentType::class, $enrollment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_enrollment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('enrollment/edit.html.twig', [
            'enrollment' => $enrollment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_enrollment_delete', methods: ['POST'])]
    public function delete(Request $request, Enrollment $enrollment, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$enrollment->getId(), $request->request->get('_token'))) {
            $em->remove($enrollment);
            $em->flush();
        }

        return $this->redirectToRoute('app_enrollment_index', [], Response::HTTP_SEE_OTHER);
    }
}
