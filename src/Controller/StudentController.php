<?php

namespace App\Controller;

use App\Entity\Student;
use App\Form\StudentType;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/student')]
final class StudentController extends AbstractController
{
    #[Route(name: 'app_student_index', methods: ['GET'])]
    public function index(Request $request, StudentRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('s');

        if ($q !== '') {
            $qb->andWhere('s.fname LIKE :q OR s.lname LIKE :q OR s.email LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        if ($status !== '') {
            $qb->andWhere('s.isActive = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('s.lname', 'ASC')->addOrderBy('s.fname', 'ASC');
        $students = $qb->getQuery()->getResult();

        return $this->render('student/index.html.twig', [
            'students' => $students,
            'q' => $q,
            'status' => $status,
        ]);
    }

    #[Route('/export', name: 'app_student_export', methods: ['GET'])]
    public function export(Request $request, StudentRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('s');

        if ($q !== '') {
            $qb->andWhere('s.fname LIKE :q OR s.lname LIKE :q OR s.email LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        if ($status !== '') {
            $qb->andWhere('s.isActive = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('s.lname', 'ASC')->addOrderBy('s.fname', 'ASC');
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID','Student ID','Full Name','Email','Active']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row->getId(),
                $row->getStudentId(),
                trim($row->getFname() . ' ' . ($row->getMname() ?? '') . ' ' . $row->getLname()),
                $row->getEmail(),
                $row->isActive() ? 'Yes' : 'No',
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="students.csv"');

        return $response;
    }

    #[Route('/new', name: 'app_student_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $student = new Student();
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($student);
            $em->flush();

            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/new.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_show', methods: ['GET'])]
    public function show(Student $student): Response
    {
        return $this->render('student/show.html.twig', ['student' => $student]);
    }

    #[Route('/{id}/edit', name: 'app_student_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Student $student, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/edit.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_delete', methods: ['POST'])]
    public function delete(Request $request, Student $student, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$student->getId(), $request->request->get('_token'))) {
            $em->remove($student);
            $em->flush();
        }

        return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
    }
}
