<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\CompteClient;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin')]
    public function index(Request $request, Connection $connection, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $message = null;

        $poRequests = $connection->fetchAllAssociative(
            "SELECT * FROM po_request_account WHERE status = 'pending'"
        );

        $selected = null;

        if ($request->query->get('request_id')) {
            $selected = $connection->fetchAssociative(
                "SELECT * FROM po_request_account WHERE id = ?",
                [$request->query->get('request_id')]
            );
        }

        if ($request->isMethod('POST') && $request->request->has('request_id')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $requestId = $request->request->get('request_id');

            $user = new User();
            $user->setUsername($username);
            $user->setRoles(["ROLE_COMMERCANT"]);
            $user->setPassword($userPasswordHasher->hashPassword($user, $password));

            $entityManager->persist($user);

            $compte = new CompteClient();
            $compte->setLabel("Compte de " . $username);
            $compte->setNumeroSiren($password);
            $compte->setDevise("EUR");
            $compte->setUser($user);

            $entityManager->persist($compte);

            $entityManager->flush();

            $connection->update('po_request_account', [
                'status' => 'done'
            ], ['id' => $requestId]);

            $message = "✅ Le compte client <strong>$username</strong> a été créé.";
        }

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'requests'       => $poRequests,
            'selected'       => $selected,
            'message'        => $message
        ]);
    }

    #[Route('/users', name: 'app_admin_users')]
    public function userList(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy([], ['username' => 'ASC']);
        return $this->render('admin/users.html.twig', ['users' => $users]);
    }

    #[Route('/users/create', name: 'app_admin_user_create')]
    public function userCreate(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, ['new_user' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_admin_users');
        }

        return $this->render('admin/user_form.html.twig', [
            'form' => $form->createView(),
            'is_new' => true,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function userDelete(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // Prevent admin from deleting themselves
            if ($this->getUser() === $user) {
                $this->addFlash('error', 'Vous не pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_admin_users');
            }
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
