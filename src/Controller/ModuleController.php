<?php

namespace App\Controller;

use App\Entity\Module;
use App\Form\ModuleType;
use App\Repository\ModuleRepository;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/module')]
#[IsGranted('ROLE_ADMIN')]
final class ModuleController extends AbstractController
{
    #[Route(name: 'app_module_index', methods: ['GET'])]
    public function index(
        Request $request,
        ModuleRepository $moduleRepository,
        CourseRepository $courseRepository,
        UserRepository $userRepository
    ): Response {
        $q            = trim((string) $request->query->get('q', ''));
        $courseId     = $request->query->get('courseId');
        $yearLevel    = $request->query->get('yearLevel');
        $semester     = $request->query->get('semester');
        $status       = $request->query->get('status');
        $instructorId = $request->query->get('instructorId');

        $qb = $moduleRepository->createQueryBuilder('m')
            ->leftJoin('m.course', 'c')->addSelect('c')
            ->leftJoin('m.instructor', 'i')->addSelect('i');

        if ($q !== '') {
            $qb->andWhere('m.name LIKE :q OR m.code LIKE :q')
               ->setParameter('q', '%' . $q . '%');
        }

        if ($courseId) {
            $qb->andWhere('c.id = :cid')
               ->setParameter('cid', (int) $courseId);
        }

        if ($yearLevel !== null && $yearLevel !== '') {
            $qb->andWhere('m.yearLevel = :yl')
               ->setParameter('yl', (int) $yearLevel);
        }

        if ($semester !== null && $semester !== '') {
            $qb->andWhere('m.semester = :sem')
               ->setParameter('sem', (int) $semester);
        }

        if ($status && $status !== 'all') {
            $qb->andWhere('m.status = :st')
               ->setParameter('st', $status);
        }

        if ($instructorId) {
            $qb->andWhere('i.id = :iid')
               ->setParameter('iid', (int) $instructorId);
        }

        $qb->orderBy('m.id', 'DESC');

        $modules     = $qb->getQuery()->getResult();
        $courses     = $courseRepository->findBy([], ['name' => 'ASC']);

        $instructors = $userRepository->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_INSTRUCTOR"%')
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('module/index.html.twig', [
            'modules'      => $modules,
            'courses'      => $courses,
            'instructors'  => $instructors,
            'q'            => $q,
            'courseId'     => $courseId,
            'yearLevel'    => $yearLevel,
            'semester'     => $semester,
            'status'       => $status,
            'instructorId' => $instructorId,
        ]);
    }

    #[Route('/new', name: 'app_module_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $module = new Module();
        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($module);
            $em->flush();

            $this->addFlash('success', 'Module created successfully.');

            return $this->redirectToRoute('app_module_index');
        }

        return $this->render('module/new.html.twig', [
            'module' => $module,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_module_show', methods: ['GET'])]
    public function show(Module $module): Response
    {
        return $this->render('module/show.html.twig', [
            'module' => $module,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_module_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Module $module,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Module updated successfully.');

            return $this->redirectToRoute('app_module_index');
        }

        return $this->render('module/edit.html.twig', [
            'module' => $module,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_module_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Module $module,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $module->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($module);
            $em->flush();
            $this->addFlash('success', 'Module deleted.');
        }

        return $this->redirectToRoute('app_module_index');
    }
}
