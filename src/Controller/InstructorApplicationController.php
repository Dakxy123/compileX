<?php

namespace App\Controller;

use App\Entity\InstructorApplication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Service\ActivityLogger;

#[Route('/student')]
#[IsGranted('ROLE_USER')]
final class InstructorApplicationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
        private ActivityLogger $activityLogger
    ) {}

    #[Route('/instructor-application/new', name: 'app_instructor_application_new', methods: ['POST'])]
    public function new(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // 1) Read form data
        $reason = trim((string) $request->request->get('reason'));
        $file   = $request->files->get('portfolio');

        // 2) Basic validation
        if ($reason === '') {
            $this->addFlash('error', 'Please explain your reason for applying.');

            return $this->redirectToRoute('app_student_dashboard', [
                '_fragment' => 'instructor-application',
            ]);
        }

        if (!$file) {
            $this->addFlash(
                'error',
                'Please attach your portfolio (PDF, DOC, DOCX, PPT, PPTX or ZIP).'
            );

            return $this->redirectToRoute('app_student_dashboard', [
                '_fragment' => 'instructor-application',
            ]);
        }

        // 3) Validate extension
        $allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'zip'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $allowedExtensions, true)) {
            $this->addFlash(
                'error',
                'Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, ZIP.'
            );

            return $this->redirectToRoute('app_student_dashboard', [
                '_fragment' => 'instructor-application',
            ]);
        }

        // 4) Prepare upload folder
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/portfolios';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                $this->addFlash('error', 'Server error: upload directory is not writable.');

                return $this->redirectToRoute('app_student_dashboard', [
                    '_fragment' => 'instructor-application',
                ]);
            }
        }

        // 5) Generate safe filename
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename);
        $timestamp        = (new \DateTimeImmutable())->format('YmdHis');
        $newFilename      = sprintf('%s_%s.%s', $safeFilename, $timestamp, $extension);

        // 6) Move file
        try {
            $file->move($uploadDir, $newFilename);
        } catch (FileException) {
            $this->addFlash('error', 'There was a problem saving your file. Please try again.');

            return $this->redirectToRoute('app_student_dashboard', [
                '_fragment' => 'instructor-application',
            ]);
        }

        // 7) Create InstructorApplication entity
        $application = new InstructorApplication();
        $application->setApplicant($user);
        $application->setReason($reason);
        $application->setPortfolioFilename($newFilename);
        $application->setStatus('PENDING');
        $application->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($application);
        $this->em->flush();

        // ✅ Activity log
        $this->activityLogger->log(
            'instructor_application.submitted',
            'instructor_application',
            sprintf('User %s submitted an instructor application.', $user->getEmail())
        );

        // 8) Flash + redirect
        $this->addFlash('success', 'Your instructor application has been submitted.');

        return $this->redirectToRoute('app_student_dashboard', [
            '_fragment' => 'instructor-application',
        ]);
    }
}
