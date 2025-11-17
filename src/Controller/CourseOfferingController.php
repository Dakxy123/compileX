<?php

namespace App\Controller;

use App\Entity\CourseOffering;
use App\Form\CourseOfferingType;
use App\Repository\CourseOfferingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/course/offering')]
final class CourseOfferingController extends AbstractController
{
    #[Route(name: 'app_course_offering_index', methods: ['GET'])]
    public function index(Request $request, CourseOfferingRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('c');

        if ($q !== '') {
            $qb->andWhere('c.status LIKE :q OR c.academic_year LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        if ($status !== '') {
            $qb->andWhere('c.status = :s')->setParameter('s', $status);
        }

        $qb->orderBy('c.academic_year', 'DESC')->addOrderBy('c.term', 'ASC');
        $courseOfferings = $qb->getQuery()->getResult();

        return $this->render('course_offering/index.html.twig', [
            'course_offerings' => $courseOfferings,
            'q' => $q,
            'status' => $status,
        ]);
    }

    #[Route('/export', name: 'app_course_offering_export', methods: ['GET'])]
    public function export(Request $request, CourseOfferingRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $status = (string) $request->query->get('status', '');
        $qb = $repo->createQueryBuilder('c');

        if ($q !== '') {
            $qb->andWhere('c.status LIKE :q OR c.academic_year LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }
        if ($status !== '') {
            $qb->andWhere('c.status = :s')->setParameter('s', $status);
        }

        $qb->orderBy('c.academic_year', 'DESC')->addOrderBy('c.term', 'ASC');
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID','Term','Status','Academic Year','Section','Subject','Instructor']);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row->getId(),
                $row->getTerm(),
                $row->getStatus(),
                $row->getAcademicYear(),
                $row->getSection()?->getSectionName() ?? 'N/A',
                $row->getSubject()?->getName() ?? 'N/A',
                $row->getInstructor()?->getFullName() ?? 'N/A',
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="course_offerings.csv"');

        return $response;
    }

    #[Route('/new', name: 'app_course_offering_new', methods: ['GET','POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $courseOffering = new CourseOffering();
        $form = $this->createForm(CourseOfferingType::class, $courseOffering);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($courseOffering);
            $em->flush();

            return $this->redirectToRoute('app_course_offering_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course_offering/new.html.twig', [
            'course_offering' => $courseOffering,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_offering_show', methods: ['GET'])]
    public function show(CourseOffering $courseOffering): Response
    {
        return $this->render('course_offering/show.html.twig', [
            'course_offering' => $courseOffering,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_course_offering_edit', methods: ['GET','POST'])]
    public function edit(Request $request, CourseOffering $courseOffering, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(CourseOfferingType::class, $courseOffering);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_course_offering_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('course_offering/edit.html.twig', [
            'course_offering' => $courseOffering,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_course_offering_delete', methods: ['POST'])]
    public function delete(Request $request, CourseOffering $courseOffering, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$courseOffering->getId(), $request->request->get('_token'))) {
            $em->remove($courseOffering);
            $em->flush();
        }

        return $this->redirectToRoute('app_course_offering_index', [], Response::HTTP_SEE_OTHER);
    }
}
