<?php

namespace App\Controller;

use App\Entity\ActivityLog;
use App\Form\ActivityLogType;
use App\Repository\ActivityLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/activity-log')]
#[IsGranted('ROLE_ADMIN')]
final class ActivityLogController extends AbstractController
{
    #[Route('/', name: 'app_activity_log_index', methods: ['GET'])]
    public function index(Request $request, ActivityLogRepository $activityLogRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $actionFilter = $request->query->get('action');

        $qb = $activityLogRepository->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')->addSelect('u')
            ->orderBy('l.createdAt', 'DESC');

        if ($search !== '') {
            $qb
                ->andWhere('u.email LIKE :search OR l.action LIKE :search OR l.context LIKE :search OR l.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($actionFilter) {
            $qb
                ->andWhere('l.action = :actionFilter')
                ->setParameter('actionFilter', $actionFilter);
        }

        $logs = $qb->getQuery()->getResult();

        $availableActions = [
            'course.created',
            'course.updated',
            'course.deleted',
            'subject.created',
            'subject.updated',
            'subject.deleted',
            'module.created',
            'module.updated',
            'module.deleted',
            'enrollment.created',
            'enrollment.updated',
            'enrollment.deleted',
            'instructor_assignment.created',
            'instructor_assignment.updated',
            'instructor_assignment.deleted',
        ];

        return $this->render('activity_log/index.html.twig', [
            'logs'             => $logs,
            'availableActions' => $availableActions,
        ]);
    }

    #[Route('/new', name: 'app_activity_log_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $log = new ActivityLog();

        $form = $this->createForm(ActivityLogType::class, $log);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($log);
            $em->flush();

            $this->addFlash('success', 'Activity log entry created.');

            return $this->redirectToRoute('app_activity_log_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity_log/new.html.twig', [
            'activity_log' => $log,
            'form'         => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_log_show', methods: ['GET'])]
    public function show(ActivityLog $activityLog): Response
    {
        return $this->render('activity_log/show.html.twig', [
            'activity_log' => $activityLog,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_activity_log_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ActivityLog $activityLog, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ActivityLogType::class, $activityLog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Activity log entry updated.');

            return $this->redirectToRoute('app_activity_log_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('activity_log/edit.html.twig', [
            'activity_log' => $activityLog,
            'form'         => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_activity_log_delete', methods: ['POST'])]
    public function delete(Request $request, ActivityLog $activityLog, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activityLog->getId(), $request->request->get('_token'))) {
            $em->remove($activityLog);
            $em->flush();
            $this->addFlash('success', 'Activity log entry deleted.');
        }

        return $this->redirectToRoute('app_activity_log_index', [], Response::HTTP_SEE_OTHER);
    }
}
