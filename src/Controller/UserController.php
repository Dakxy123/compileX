<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Repository\StudentProfileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\ActivityLogger;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    public function __construct(
        private ActivityLogger $activityLogger
    ) {}

    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $q          = trim((string) $request->query->get('q', ''));
        $sort       = (string) $request->query->get('sort', 'email');
        $direction  = strtoupper((string) $request->query->get('direction', 'ASC'));
        $roleFilter = (string) $request->query->get('role', '');

        $allowedSorts = ['id', 'email', 'password'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'email';
        $direction = in_array($direction, ['ASC', 'DESC'], true) ? $direction : 'ASC';

        $qb = $userRepository->createQueryBuilder('u');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        if ($roleFilter !== '') {
            $qb->andWhere('u.roles LIKE :role')->setParameter('role', '%'.$roleFilter.'%');
        }

        $qb->orderBy('u.' . $sort, $direction);
        $users = $qb->getQuery()->getResult();

        // ✅ ACTIVITY LOG
        $this->activityLogger->log('user.index', 'user', 'Viewed user list.');

        return $this->render('user/index.html.twig', [
            'users'     => $users,
            'q'         => $q,
            'sort'      => $sort,
            'direction' => $direction,
            'role'      => $roleFilter,
        ]);
    }

    #[Route('/export', name: 'app_user_export', methods: ['GET'])]
    public function export(Request $request, UserRepository $userRepository): Response
    {
        $q          = trim((string) $request->query->get('q', ''));
        $roleFilter = (string) $request->query->get('role', '');

        $qb = $userRepository->createQueryBuilder('u');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        if ($roleFilter !== '') {
            $qb->andWhere('u.roles LIKE :role')->setParameter('role', '%'.$roleFilter.'%');
        }

        $qb->orderBy('u.email', 'ASC');
        $rows = $qb->getQuery()->getResult();

        // ✅ ACTIVITY LOG
        $this->activityLogger->log('user.export', 'user', 'Exported user list to CSV.');

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Email', 'Roles']);

        foreach ($rows as $row) {
            fputcsv($out, [$row->getId(), $row->getEmail(), implode(', ', $row->getRoles())]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return new Response(
            $csv,
            200,
            [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="users.csv"',
            ]
        );
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $roleData = $form->get('roles')->getData();
            $role = is_array($roleData) ? ($roleData[0] ?? 'ROLE_USER') : (string) $roleData;
            $user->setRoles([$role]);

            $rawPassword = (string) $form->get('password')->getData();
            if ($rawPassword !== '') {
                $user->setPassword($hasher->hashPassword($user, $rawPassword));
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'user.created',
                'user',
                'Created user ID '.$user->getId().'.'
            );

            $this->addFlash('success', 'User created successfully.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $hasher
    ): Response {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $roleData = $form->get('roles')->getData();
            $role = is_array($roleData) ? ($roleData[0] ?? 'ROLE_USER') : (string) $roleData;
            $user->setRoles([$role]);

            $rawPassword = (string) $form->get('password')->getData();
            if ($rawPassword !== '') {
                $user->setPassword($hasher->hashPassword($user, $rawPassword));
            }

            $entityManager->flush();

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'user.updated',
                'user',
                'Updated user ID '.$user->getId().'.'
            );

            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        // ✅ ACTIVITY LOG
        $this->activityLogger->log(
            'user.show',
            'user',
            'Viewed user ID '.$user->getId().'.'
        );

        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        StudentProfileRepository $studentProfileRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {

            $studentProfile = $studentProfileRepository->findOneBy(['user' => $user]);
            if ($studentProfile) {
                $entityManager->remove($studentProfile);
            }

            // ✅ ACTIVITY LOG
            $this->activityLogger->log(
                'user.deleted',
                'user',
                'Deleted user ID '.$user->getId().'.'
            );

            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'User deleted successfully.');
        }

        return $this->redirectToRoute('app_user_index');
    }
}
