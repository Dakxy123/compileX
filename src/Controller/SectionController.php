<?php

namespace App\Controller;

use App\Entity\Section;
use App\Form\SectionType;
use App\Repository\SectionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/section')]
final class SectionController extends AbstractController
{
    #[Route(name: 'app_section_index', methods: ['GET'])]
    public function index(Request $request, SectionRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('s');

        if ($q !== '') {
            $qb->andWhere('s.section_code LIKE :q OR s.course_program LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== '') {
            $qb->andWhere('s.isActive = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('s.year_level', 'ASC')->addOrderBy('s.section_code', 'ASC');
        $sections = $qb->getQuery()->getResult();

        return $this->render('section/index.html.twig', [
            'sections' => $sections,
            'q' => $q,
            'status' => $status,
        ]);
    }

    #[Route('/export', name: 'app_section_export', methods: ['GET'])]
    public function export(Request $request, SectionRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('s');

        if ($q !== '') {
            $qb->andWhere('s.section_code LIKE :q OR s.course_program LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($status !== '') {
            $qb->andWhere('s.isActive = :s')->setParameter('s', $status === '1');
        }

        $qb->orderBy('s.year_level', 'ASC')->addOrderBy('s.section_code', 'ASC');
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Section Code', 'Year Level', 'Course', 'Active']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row->getId(),
                $row->getSectionCode(),
                $row->getYearLevel(),
                $row->getCourseProgram()?->getName() ?? '',
                $row->isActive() ? 'Yes' : 'No',
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="sections.csv"');

        return $response;
    }

    #[Route('/new', name: 'app_section_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $section = new Section();
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($section);
            $em->flush();

            return $this->redirectToRoute('app_section_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('section/new.html.twig', [
            'section' => $section,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_section_show', methods: ['GET'])]
    public function show(Section $section): Response
    {
        return $this->render('section/show.html.twig', ['section' => $section]);
    }

    #[Route('/{id}/edit', name: 'app_section_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Section $section, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(SectionType::class, $section);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_section_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('section/edit.html.twig', [
            'section' => $section,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_section_delete', methods: ['POST'])]
    public function delete(Request $request, Section $section, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$section->getId(), $request->request->get('_token'))) {
            $em->remove($section);
            $em->flush();
        }

        return $this->redirectToRoute('app_section_index', [], Response::HTTP_SEE_OTHER);
    }
}
