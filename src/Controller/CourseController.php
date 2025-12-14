<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Repository\CourseRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/course')]
#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_INSTRUCTOR")'))]
final class CourseController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'app_course_index', methods: ['GET'])]
    public function index(Request $request, CourseRepository $courseRepository): Response
    {
        $this->activityLogger->log(
            'course.viewed',
            'course',
            'Viewed course list.'
        );

        $q = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', 'all');
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
            'q'       => $q,
            'status'  => $status,
        ]);
    }

    #[Route('/export', name: 'app_course_export', methods: ['GET'])]
    public function export(Request $request, CourseRepository $courseRepository): Response
    {
        $this->activityLogger->log(
            'course.exported',
            'course',
            'Exported course list to CSV.'
        );

        $q = trim((string) $request->query->get('q', ''));
        $status = $request->query->get('status', 'all');

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
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Name', 'Description', 'Status']);

        /** @var Course $course */
        foreach ($rows as $course) {
            fputcsv($out, [
                $course->getId(),
                (string) $course->getName(),
                (string) ($course->getDescription() ?? ''),
                $course->isActive() ? 'Active' : 'Inactive',
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response(
            $csv,
            200,
            [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="courses.csv"',
            ]
        );
    }

    #[Route('/new', name: 'app_course_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            $this->activityLogger->log(
                'course.created',
                'course',
                'Created course ID '.$course->getId().'.'
            );

            $this->addFlash('success', 'Course created successfully.');
            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        $this->activityLogger->log(
            'course.viewed_one',
            'course',
            'Viewed course ID '.$course->getId().'.'
        );

        return $this->render('course/show.html.twig', [
            'course' => $course,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->activityLogger->log(
                'course.updated',
                'course',
                'Updated course ID '.$course->getId().'.'
            );

            $this->addFlash('success', 'Course updated successfully.');
            return $this->redirectToRoute('app_course_index');
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Course $course,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$course->getId(), (string) $request->request->get('_token'))) {

            $id = $course->getId();

            $entityManager->remove($course);
            $entityManager->flush();

            $this->activityLogger->log(
                'course.deleted',
                'course',
                'Deleted course ID '.$id.'.'
            );

            $this->addFlash('success', 'Course deleted.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }

        return $this->redirectToRoute('app_course_index');
    }
}
