<?php

namespace App\Controller;

use App\Entity\InstructorApplication;
use App\Repository\InstructorApplicationRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/instructor-applications')]
#[IsGranted('ROLE_ADMIN')]
final class AdminInstructorApplicationController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route('', name: 'app_instructor_application_admin_index', methods: ['GET'])]
    public function index(
        InstructorApplicationRepository $applicationRepository,
        Request $request
    ): Response {
        $status = $request->query->get('status');

        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'instructor_application.index',
            'instructor_application',
            'Viewed instructor applications list' . ($status ? ' (status: '.$status.')' : '')
        );

        $qb = $applicationRepository->createQueryBuilder('a')
            ->leftJoin('a.applicant', 'u')->addSelect('u')
            ->orderBy('a.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        $applications = $qb->getQuery()->getResult();

        return $this->render('instructor_application_admin/index.html.twig', [
            'applications' => $applications,
            'statusFilter' => $status,
        ]);
    }

    #[Route('/{id}', name: 'app_instructor_application_admin_show', methods: ['GET'])]
    public function show(InstructorApplication $application): Response
    {
        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'instructor_application.show',
            'instructor_application',
            'Viewed instructor application #'.$application->getId()
        );

        return $this->render('instructor_application_admin/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_instructor_application_admin_approve', methods: ['POST'])]
    public function approve(
        InstructorApplication $application,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('approve' . $application->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_instructor_application_admin_show', [
                'id' => $application->getId(),
            ]);
        }

        if ($application->getStatus() !== 'PENDING') {
            $this->addFlash('error', 'This application is no longer pending.');
            return $this->redirectToRoute('app_instructor_application_admin_show', [
                'id' => $application->getId(),
            ]);
        }

        $application->setStatus('APPROVED');
        $application->setReviewedAt(new \DateTimeImmutable());
        $application->setReviewedBy($this->getUser());

        // Auto-promote applicant to ROLE_INSTRUCTOR (keep existing roles)
        $user = $application->getApplicant();
        if ($user) {
            $roles = $user->getRoles();
            if (!in_array('ROLE_INSTRUCTOR', $roles, true)) {
                $roles[] = 'ROLE_INSTRUCTOR';
                $user->setRoles($roles);
            }
        }

        // ✅ ACTIVITY LOG (before/after flush ok; either works)
        $this->activityLogger->log(
            'instructor_application.approve',
            'instructor_application',
            'Approved instructor application #'.$application->getId()
        );

        $em->flush();

        $this->addFlash('success', 'Application approved and user promoted to instructor.');

        return $this->redirectToRoute('app_instructor_application_admin_show', [
            'id' => $application->getId(),
        ]);
    }

    #[Route('/{id}/reject', name: 'app_instructor_application_admin_reject', methods: ['POST'])]
    public function reject(
        InstructorApplication $application,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('reject' . $application->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_instructor_application_admin_show', [
                'id' => $application->getId(),
            ]);
        }

        if ($application->getStatus() !== 'PENDING') {
            $this->addFlash('error', 'This application is no longer pending.');
            return $this->redirectToRoute('app_instructor_application_admin_show', [
                'id' => $application->getId(),
            ]);
        }

        $application->setStatus('REJECTED');
        $application->setReviewedAt(new \DateTimeImmutable());
        $application->setReviewedBy($this->getUser());

        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'instructor_application.reject',
            'instructor_application',
            'Rejected instructor application #'.$application->getId()
        );

        $em->flush();

        $this->addFlash('success', 'Application rejected.');

        return $this->redirectToRoute('app_instructor_application_admin_show', [
            'id' => $application->getId(),
        ]);
    }
}
