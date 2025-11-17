<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/user')]
#[IsGranted('ROLE_ADMIN')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $role = (string) $request->query->get('role', '');
        $qb = $userRepository->createQueryBuilder('u');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q')
               ->setParameter('q', '%'.$q.'%');
        }

        if ($role !== '') {
            $qb->andWhere(':role MEMBER OF u.roles')
               ->setParameter('role', $role);
        }

        $qb->orderBy('u.email', 'ASC');
        $users = $qb->getQuery()->getResult();

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'q' => $q,
            'role' => $role,
        ]);
    }

    #[Route('/export', name: 'app_user_export', methods: ['GET'])]
    public function export(Request $request, UserRepository $userRepository): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $role = (string) $request->query->get('role', '');
        $qb = $userRepository->createQueryBuilder('u');

        if ($q !== '') {
            $qb->andWhere('u.email LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        if ($role !== '') {
            $qb->andWhere(':role MEMBER OF u.roles')->setParameter('role', $role);
        }

        $qb->orderBy('u.email', 'ASC');
        $rows = $qb->getQuery()->getResult();

        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['ID', 'Email', 'Roles']);
        /** @var User $row */
        foreach ($rows as $row) {
            fputcsv($out, [
                $row->getId(),
                $row->getEmail(),
                implode(', ', $row->getRoles()),
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="users.csv"');
        return $response;
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rawPassword = (string) $form->get('password')->getData();
            if ($rawPassword !== '') {
                $user->setPassword($hasher->hashPassword($user, $rawPassword));
            }
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $hasher): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rawPassword = (string) $form->get('password')->getData();
            if ($rawPassword !== '') {
                $user->setPassword($hasher->hashPassword($user, $rawPassword));
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
