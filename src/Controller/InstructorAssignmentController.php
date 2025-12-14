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
use App\Service\ActivityLogger;

#[Route('/admin/instructor-assignment')]
#[IsGranted('ROLE_ADMIN')]
final class InstructorAssignmentController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/', name: 'app_instructor_assignment_index', methods: ['GET'])]
    public function index(Request $request, InstructorAssignmentRepository $repository): Response
    {
        $this->activityLogger->log(
            'instructor_assignment.index',
            'instructor_assignment',
            'Viewed instructor assignment list.'
        );

        $search  = trim((string) $request->query->get('q', ''));
        $primary = $request->query->get('primary'); 

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
            'assignments'   => $assignments,
            'primaryFilter' => $primary,
        ]);
    }

    #[Route('/export', name: 'app_instructor_assignment_export', methods: ['GET'])]
    public function export(Request $request, InstructorAssignmentRepository $repository): Response
    {
        $this->activityLogger->log(
            'instructor_assignment.export',
            'instructor_assignment',
            'Exported instructor assignment list to CSV.'
        );

        $search  = trim((string) $request->query->get('q', ''));
        $primary = $request->query->get('primary'); 

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

        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, [
            'Instructor Email',
            'Subject Code',
            'Subject Name',
            'Course',
            'Primary',
            'Assigned At',
        ]);

        foreach ($rows as $assignment) {
            /** @var InstructorAssignment $assignment */
            $instructor = $assignment->getInstructor();
            $subject    = $assignment->getSubject();
            $course     = $subject?->getCourse();

            fputcsv($out, [
                $instructor ? $instructor->getEmail() : '',
                $subject ? $subject->getCode() : '',
                $subject ? $subject->getName() : '',
                $course ? $course->getName() : '',
                $assignment->isPrimary() ? 'Yes' : 'No',
                $assignment->getCreatedAt() ? $assignment->getCreatedAt()->format('Y-m-d H:i:s') : '',
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
                'Content-Disposition' => 'attachment; filename="instructor_assignments.csv"',
            ]
        );
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

            $this->activityLogger->log(
                'instructor_assignment.created',
                'instructor_assignment',
                sprintf(
                    'Created instructor assignment #%d (%s → %s).',
                    (int) $assignment->getId(),
                    $assignment->getInstructor()?->getEmail() ?? 'unknown',
                    $assignment->getSubject()?->getCode() ?? 'unknown'
                )
            );

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
        $this->activityLogger->log(
            'instructor_assignment.show',
            'instructor_assignment',
            sprintf('Viewed instructor assignment #%d.', (int) $assignment->getId())
        );

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

            $this->activityLogger->log(
                'instructor_assignment.updated',
                'instructor_assignment',
                sprintf(
                    'Updated instructor assignment #%d (%s → %s).',
                    (int) $assignment->getId(),
                    $assignment->getInstructor()?->getEmail() ?? 'unknown',
                    $assignment->getSubject()?->getCode() ?? 'unknown'
                )
            );

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
            $id = (int) $assignment->getId();
            $instEmail = $assignment->getInstructor()?->getEmail() ?? 'unknown';
            $subjCode  = $assignment->getSubject()?->getCode() ?? 'unknown';

            $em->remove($assignment);
            $em->flush();

            $this->activityLogger->log(
                'instructor_assignment.deleted',
                'instructor_assignment',
                sprintf('Deleted instructor assignment #%d (%s → %s).', $id, $instEmail, $subjCode)
            );

            $this->addFlash('success', 'Instructor assignment deleted successfully.');
        }

        return $this->redirectToRoute('app_instructor_assignment_index', [], Response::HTTP_SEE_OTHER);
    }
}
