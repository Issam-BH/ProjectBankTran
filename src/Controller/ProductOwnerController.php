<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\CompteClient;
use App\Entity\PoRequestAccount;
use App\Config\PoAccountRequestAction;
use App\Config\PoAccountRequestStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProductOwnerController extends AbstractController
{
    #[Route('/product-owner', name: 'app_product_owner')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $sortBy = $request->query->get('sort', 'siren');
        $direction = $request->query->get('direction', 'asc');

        $comptes = $entityManager->getRepository(CompteClient::class)->findAll();

        // Manual sorting since balance is a calculated field not in DB
        usort($comptes, function ($a, $b) use ($sortBy, $direction) {
            if ($sortBy === 'balance') {
                $valA = $a->getBalance();
                $valB = $b->getBalance();
            } else {
                $valA = $a->getNumeroSiren();
                $valB = $b->getNumeroSiren();
            }

            if ($valA == $valB) {
                return 0;
            }

            if ($direction === 'asc') {
                return ($valA < $valB) ? -1 : 1;
            } else {
                return ($valA > $valB) ? -1 : 1;
            }
        });

        return $this->render('product_owner/index.html.twig', [
            'comptes' => $comptes,
            'currentSort' => $sortBy,
            'currentDirection' => $direction,
        ]);
    }

    #[Route('/product-owner/account/{id}', name: 'app_product_owner_account_show')]
    public function showAccount(CompteClient $compte): Response
    {
        return $this->render('product_owner/show_account.html.twig', [
            'compte' => $compte,
        ]);
    }

    #[Route('/product-owner/account-request', name: 'app_product_owner_account_request')]
    public function accountRequest(EntityManagerInterface $entityManager, Request $request): Response
    {
        $comptes = $entityManager->createQuery('SELECT u FROM App\Entity\User u WHERE u.roles LIKE :role')->setParameter('role', '%ROLE_COMMERCANT%')->getResult();
        $message = null;

        if ($request->isMethod('POST')) {
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $action = $request->request->get('action');

            $poRequest = new PoRequestAccount();
            $poRequest->setUsername($username)
                      ->setPassword($password)
                      ->setAction(PoAccountRequestAction::from($action))
                      ->setStatus(PoAccountRequestStatus::pending);

            $entityManager->persist($poRequest);
            $entityManager->flush();

            $message = "✅ La demande a été envoyée avec succès.";
        }

        return $this->render('product_owner/account_request.html.twig', [
            'comptes' => $comptes,
            'message' => $message,
        ]);
    }
}
