<?php

namespace App\Controller;

use App\Entity\Subject;
use App\Form\SubjectType;
use App\Repository\SubjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/subject')]
final class SubjectController extends AbstractController
{
    #[Route(name: 'app_subject_index', methods: ['GET'])]
    public function index(Request $request, SubjectRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('s');

        if ($q !== '') {
            $qb->andWhere('s.title LIKE :q OR s.code LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== '') {
            $qb->andWhere('s.is_active = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('s.title', 'ASC');
        $subjects = $qb->getQuery()->getResult();

        return $this->render('subject/index.html.twig', [
            'subjects' => $subjects,
            'q' => $q,
            'status' => $status,
        ]);
    }

    #[Route('/export', name: 'app_subject_export', methods: ['GET'])]
    public function export(Request $request, SubjectRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('s');

        if ($q !== '') {
            $qb->andWhere('s.title LIKE :q OR s.code LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== '') {
            $qb->andWhere('s.is_active = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('s.title', 'ASC');
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Title', 'Code', 'Units', 'Active']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row->getId(),
                $row->getName(),
                $row->getCode(),
                $row->getUnits() ?? '',
                $row->isActive() ? 'Yes' : 'No',
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="subjects.csv"');

        return $response;
    }

    #[Route('/new', name: 'app_subject_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $subject = new Subject();
        $form = $this->createForm(SubjectType::class, $subject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($subject);
            $em->flush();

            return $this->redirectToRoute('app_subject_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('subject/new.html.twig', [
            'subject' => $subject,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_subject_show', methods: ['GET'])]
    public function show(Subject $subject): Response
    {
        return $this->render('subject/show.html.twig', ['subject' => $subject]);
    }

    #[Route('/{id}/edit', name: 'app_subject_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Subject $subject, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SubjectType::class, $subject);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_subject_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('subject/edit.html.twig', [
            'subject' => $subject,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_subject_delete', methods: ['POST'])]
    public function delete(Request $request, Subject $subject, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$subject->getId(), $request->request->get('_token'))) {
            $em->remove($subject);
            $em->flush();
        }

        return $this->redirectToRoute('app_subject_index', [], Response::HTTP_SEE_OTHER);
    }
}
