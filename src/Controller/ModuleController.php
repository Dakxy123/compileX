<?php

namespace App\Controller;

use App\Entity\Module;
use App\Form\ModuleType;
use App\Repository\ModuleRepository;
use App\Repository\CourseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[Route('/module')]
#[IsGranted(new Expression('is_granted("ROLE_ADMIN") or is_granted("ROLE_INSTRUCTOR")'))]
final class ModuleController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route(name: 'app_module_index', methods: ['GET'])]
    public function index(
        Request $request,
        ModuleRepository $moduleRepository,
        CourseRepository $courseRepository,
        UserRepository $userRepository
    ): Response {
        // ✅ ACTIVITY LOG
        $this->activityLogger->log('module.index.view', 'module', 'Viewed module list.');

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin      = $this->isGranted('ROLE_ADMIN');
        $isInstructor = $this->isGranted('ROLE_INSTRUCTOR');

        $q            = trim((string) $request->query->get('q', ''));
        $courseId     = $request->query->get('courseId');
        $yearLevel    = $request->query->get('yearLevel');
        $semester     = $request->query->get('semester');
        $status       = $request->query->get('status');
        $instructorId = $request->query->get('instructorId');

        if ($isInstructor && !$isAdmin) {
            $instructorId = (string) $user->getId();
        }

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

        $modules = $qb->getQuery()->getResult();
        $courses = $courseRepository->findBy([], ['name' => 'ASC']);

        if ($isAdmin) {
            $instructors = $userRepository->createQueryBuilder('u')
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"ROLE_INSTRUCTOR"%')
                ->orderBy('u.email', 'ASC')
                ->getQuery()
                ->getResult();
        } else {
            $instructors = [$user];
        }

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

    #[Route('/export', name: 'app_module_export', methods: ['GET'])]
    public function export(
        Request $request,
        ModuleRepository $moduleRepository
    ): Response {
        // ✅ ACTIVITY LOG
        $this->activityLogger->log('module.export.csv', 'module', 'Exported module list to CSV.');

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin      = $this->isGranted('ROLE_ADMIN');
        $isInstructor = $this->isGranted('ROLE_INSTRUCTOR');

        $q            = trim((string) $request->query->get('q', ''));
        $courseId     = $request->query->get('courseId');
        $yearLevel    = $request->query->get('yearLevel');
        $semester     = $request->query->get('semester');
        $status       = $request->query->get('status');
        $instructorId = $request->query->get('instructorId');

        if ($isInstructor && !$isAdmin) {
            $instructorId = (string) $user->getId();
        }

        $qb = $moduleRepository->createQueryBuilder('m')
            ->leftJoin('m.course', 'c')->addSelect('c')
            ->leftJoin('m.subject', 's')->addSelect('s')
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
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, [
            'ID',
            'Code',
            'Name',
            'Year Level',
            'Semester',
            'Course',
            'Subject',
            'Instructor Email',
            'Status',
            'Schedule',
        ]);

        /** @var Module $module */
        foreach ($rows as $module) {
            $course     = $module->getCourse();
            $subject    = $module->getSubject();
            $instructor = $module->getInstructor();

            fputcsv($out, [
                $module->getId(),
                $module->getCode(),
                $module->getName(),
                $module->getYearLevel(),
                $module->getSemester(),
                $course ? $course->getName() : '',
                $subject ? $subject->getName() : '',
                $instructor ? $instructor->getEmail() : '',
                $module->getStatus(),
                $module->getSchedule(),
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
                'Content-Disposition' => 'attachment; filename="modules.csv"',
            ]
        );
    }

    #[Route('/new', name: 'app_module_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $module = new Module();
        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($module);
            $em->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'module.created',
                'module',
                'Created module ID ' . $module->getId() . '.'
            );

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
        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'module.show.view',
            'module',
            'Viewed module ID ' . $module->getId() . '.'
        );

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin      = $this->isGranted('ROLE_ADMIN');
        $isInstructor = $this->isGranted('ROLE_INSTRUCTOR');

        if ($isInstructor && !$isAdmin) {
            $instructor = $module->getInstructor();
            if (!$instructor || $instructor->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }
        }

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
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin      = $this->isGranted('ROLE_ADMIN');
        $isInstructor = $this->isGranted('ROLE_INSTRUCTOR');

        if ($isInstructor && !$isAdmin) {
            $instructor = $module->getInstructor();
            if (!$instructor || $instructor->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }
        }

        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'module.updated',
                'module',
                'Updated module ID ' . $module->getId() . '.'
            );

            $this->addFlash('success', 'Module updated successfully.');
            return $this->redirectToRoute('app_module_index');
        }

        return $this->render('module/edit.html.twig', [
            'module' => $module,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_module_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Module $module,
        EntityManagerInterface $em
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $module->getId(), $request->request->get('_token'))) {

            $id = $module->getId();
            $em->remove($module);
            $em->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'module.deleted',
                'module',
                'Deleted module ID ' . $id . '.'
            );

            $this->addFlash('success', 'Module deleted.');
        }

        return $this->redirectToRoute('app_module_index');
    }
}
