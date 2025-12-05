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
    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(Request $request, CourseRepository $courseRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', 'all'); // all|active|inactive

        $qb = $courseRepository->createQueryBuilder('c');

        if ($q !== '') {
            $qb->andWhere('c.name LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status === 'active') {
            $qb->andWhere('c.IsActive = :active')
               ->setParameter('active', true);
        } elseif ($status === 'inactive') {
            $qb->andWhere('c.IsActive = :active')
               ->setParameter('active', false);
        }

        $qb->orderBy('c.name', 'ASC');

        $courses = $qb->getQuery()->getResult();

        return $this->render('course/index.html.twig', [
            'courses' => $courses,
            'q' => $q,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        return $this->render('course/show.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), $request->request->get('_token'))) {
            $entityManager->remove($course);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_course_index');
    }
}
