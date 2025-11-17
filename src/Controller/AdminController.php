<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request, Connection $connection,UserPasswordHasherInterface $userPasswordHasher): Response
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

        if ($request->isMethod('POST')) {

            $user = new User();
            $user->setPassword

            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $requestId = $request->request->get('request_id');

            $connection->insert('user', [
                'username' => $username,
                'password' => $userPasswordHasher->hashPassword($username, $password),
                'roles' => '["ROLE_COMMERCANT"]'
            ]);

            $connection->update('po_requests', [
                'status' => 'done'
            ], ['id' => $requestId]);

            $message = "✅ Le compte client <strong>$clientName</strong> a été créé.";
        }

        return $this->render('admin/index.html.twig', [
            'controller_name' => 'AdminController',
            'requests'       => $poRequests,
            'selected'       => $selected,
            'message'        => $message
        ]);
    }
}
