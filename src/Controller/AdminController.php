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
    public function index(Request $request, Connection $connection, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, UserRepository $userRepository): Response
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
            $requestId = $request->request->get('request_id');
            $action = $request->request->get('action');
            $entrepriseNom = $request->request->get('entreprise_nom');

            if ($action === 'create') {
                $adminUsername = $request->request->get('admin_username');
                $adminPassword = $request->request->get('admin_password');
                $siret = $request->request->get('siret');

                $user = new User();
                $user->setUsername($adminUsername);
                $user->setRoles(["ROLE_COMMERCANT"]);
                $user->setPassword($userPasswordHasher->hashPassword($user, $adminPassword));
                $entityManager->persist($user);

                $compte = new CompteClient();
                $compte->setLabel($entrepriseNom);
                $compte->setNumeroSiren($siret);
                $compte->setDevise("EUR");
                $compte->setUser($user);
                $entityManager->persist($compte);

                $message = "✅ Le compte client <strong>$adminUsername</strong> a été créé pour l'entreprise $entrepriseNom.";
            } else {
                $userToDelete = $userRepository->findOneBy(['username' => $entrepriseNom]);
                if ($userToDelete && $this->getUser() !== $userToDelete) {
                    $entityManager->remove($userToDelete);
                    $message = "✅ Le compte de <strong>$entrepriseNom</strong> a été supprimé.";
                }
            }

            $connection->update('po_request_account', ['status' => 'done'], ['id' => $requestId]);
            $entityManager->flush();
            
            return $this->redirectToRoute('app_admin', ['message' => $message]);
        }

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'requests'       => $poRequests,
            'selected'       => $selected,
            'message'        => $request->query->get('message') ?? $message
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
            if ($this->getUser() === $user) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
                return $this->redirectToRoute('app_admin_users');
            }
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}