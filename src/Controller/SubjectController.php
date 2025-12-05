<?php

namespace App\Controller;

use App\Entity\Subject;
use App\Form\SubjectType;
use App\Repository\SubjectRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/subject')]
#[IsGranted('ROLE_ADMIN')]
final class SubjectController extends AbstractController
{
    #[Route('/', name: 'app_subject_index', methods: ['GET'])]
    public function index(
        Request $request,
        SubjectRepository $subjectRepository,
        CourseRepository $courseRepository
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $courseId = $request->query->get('courseId');
        $yearLevel = $request->query->get('yearLevel');
        $semester = $request->query->get('semester');
        $courseStatus = $request->query->get('courseStatus', 'all'); // all|active|inactive

        $qb = $subjectRepository->createQueryBuilder('s')
            ->leftJoin('s.course', 'c')
            ->addSelect('c');

        if ($q !== '') {
            $qb->andWhere('s.name LIKE :q OR s.code LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($courseId !== null && $courseId !== '') {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', (int) $courseId);
        }

        if ($yearLevel !== null && $yearLevel !== '') {
            $qb->andWhere('s.yearLevel = :yearLevel')
               ->setParameter('yearLevel', (int) $yearLevel);
        }

        if ($semester !== null && $semester !== '') {
            $qb->andWhere('s.semester = :semester')
               ->setParameter('semester', (int) $semester);
        }

        if ($courseStatus === 'active') {
            $qb->andWhere('c.IsActive = :active')
               ->setParameter('active', true);
        } elseif ($courseStatus === 'inactive') {
            $qb->andWhere('c.IsActive = :active')
               ->setParameter('active', false);
        }

        $qb->orderBy('c.name', 'ASC')
           ->addOrderBy('s.yearLevel', 'ASC')
           ->addOrderBy('s.semester', 'ASC')
           ->addOrderBy('s.code', 'ASC');

        $subjects = $qb->getQuery()->getResult();
        $courses = $courseRepository->findBy([], ['name' => 'ASC']);

        return $this->render('subject/index.html.twig', [
            'subjects' => $subjects,
            'courses' => $courses,
            'q' => $q,
            'courseId' => $courseId,
            'yearLevel' => $yearLevel,
            'semester' => $semester,
            'courseStatus' => $courseStatus,
        ]);
    }

    #[Route('/new', name: 'app_subject_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $subject = new Subject();
        $form = $this->createForm(SubjectType::class, $subject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($subject);
            $entityManager->flush();

            return $this->redirectToRoute('app_subject_index');
        }

        return $this->render('subject/new.html.twig', [
            'subject' => $subject,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_subject_show', methods: ['GET'])]
    public function show(Subject $subject): Response
    {
        return $this->render('subject/show.html.twig', [
            'subject' => $subject,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_subject_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Subject $subject,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(SubjectType::class, $subject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_subject_index');
        }

        return $this->render('subject/edit.html.twig', [
            'subject' => $subject,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_subject_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Subject $subject,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$subject->getId(), $request->request->get('_token'))) {
            $entityManager->remove($subject);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_subject_index');
    }
}
