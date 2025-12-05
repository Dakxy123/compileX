<?php

namespace App\Controller;

use App\Entity\StudentProfile;
use App\Form\StudentProfileType;
use App\Repository\StudentProfileRepository;
use App\Repository\CourseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/student/profile')]
#[IsGranted('ROLE_ADMIN')]
final class StudentProfileController extends AbstractController
{
    #[Route(name: 'app_student_profile_index', methods: ['GET'])]
    public function index(
        Request $request,
        StudentProfileRepository $studentProfileRepository,
        CourseRepository $courseRepository
    ): Response {
        $q = trim((string) $request->query->get('q', ''));
        $courseId = $request->query->get('courseId');
        $yearLevel = $request->query->get('yearLevel');
        $status = $request->query->get('status');

        $qb = $studentProfileRepository->createQueryBuilder('sp')
            ->join('sp.user', 'u')
            ->addSelect('u')
            ->join('sp.course', 'c')
            ->addSelect('c');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($courseId !== null && $courseId !== '') {
            $qb->andWhere('c.id = :courseId')
               ->setParameter('courseId', (int) $courseId);
        }

        if ($yearLevel !== null && $yearLevel !== '') {
            $qb->andWhere('sp.yearLevel = :yearLevel')
               ->setParameter('yearLevel', (int) $yearLevel);
        }

        if ($status !== null && $status !== '') {
            $qb->andWhere('sp.status = :status')
               ->setParameter('status', $status);
        }

        $qb->orderBy('c.name', 'ASC')
           ->addOrderBy('sp.yearLevel', 'ASC')
           ->addOrderBy('u.email', 'ASC');

        $studentProfiles = $qb->getQuery()->getResult();
        $courses = $courseRepository->findBy([], ['name' => 'ASC']);

        return $this->render('student_profile/index.html.twig', [
            'studentProfiles' => $studentProfiles,
            'courses' => $courses,
            'q' => $q,
            'courseId' => $courseId,
            'yearLevel' => $yearLevel,
            'status' => $status,
        ]);
    }

    #[Route('/new', name: 'app_student_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $studentProfile = new StudentProfile();
        $form = $this->createForm(StudentProfileType::class, $studentProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $studentProfile->touch();
            $entityManager->persist($studentProfile);
            $entityManager->flush();

            return $this->redirectToRoute('app_student_profile_index');
        }

        return $this->render('student_profile/new.html.twig', [
            'studentProfile' => $studentProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_profile_show', methods: ['GET'])]
    public function show(StudentProfile $studentProfile): Response
    {
        return $this->render('student_profile/show.html.twig', [
            'studentProfile' => $studentProfile,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_student_profile_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        StudentProfile $studentProfile,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(StudentProfileType::class, $studentProfile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $studentProfile->touch();
            $entityManager->flush();

            return $this->redirectToRoute('app_student_profile_index');
        }

        return $this->render('student_profile/edit.html.twig', [
            'studentProfile' => $studentProfile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        StudentProfile $studentProfile,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$studentProfile->getId(), $request->request->get('_token'))) {
            $entityManager->remove($studentProfile);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_student_profile_index');
    }
}
