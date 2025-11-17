<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(Request $request, Connection $connection): Response
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

            $clientName = $request->request->get('client_name');
            $email = $request->request->get('email');
            $requestId = $request->request->get('request_id');

            $connection->insert('clients', [
                'client_name' => $clientName,
                'email' => $email
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
