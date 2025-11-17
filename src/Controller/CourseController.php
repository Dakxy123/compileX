<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/course')]
#[IsGranted('ROLE_ADMIN')]
final class CourseController extends AbstractController
{
    #[Route(name: 'app_course_index', methods: ['GET'])]
    public function index(Request $request, CourseRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('c');

        if ($q !== '') {
            $qb->andWhere('c.name LIKE :q OR c.code LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== '') {
            $qb->andWhere('c.isActive = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('c.name', 'ASC');
        $courses = $qb->getQuery()->getResult();

        return $this->render('course/index.html.twig', [
            'courses' => $courses,
            'q' => $q,
            'status' => $status,
        ]);
    }

    #[Route('/export', name: 'app_course_export', methods: ['GET'])]
    public function export(Request $request, CourseRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('c');

        if ($q !== '') {
            $qb->andWhere('c.name LIKE :q OR c.code LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== '') {
            $qb->andWhere('c.isActive = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('c.name', 'ASC');
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Name', 'Code', 'Active']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row->getId(),
                $row->getName(),
                $row->getCode(),
                $row->isActive() ? 'Yes' : 'No',
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="courses.csv"');

        return $response;
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($course);
            $em->flush();

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        return $this->render('course/show.html.twig', ['course' => $course]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            $em->remove($course);
            $em->flush();
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }
}
