<?php

namespace App\Controller;

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

#[IsGranted('ROLE_PO')]
#[Route('/product-owner')]
final class ProductOwnerController extends AbstractController
{
    #[Route('', name: 'app_product_owner')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $sortBy = $request->query->get('sort', 'siren');
        $direction = $request->query->get('direction', 'asc');
        $comptes = $entityManager->getRepository(CompteClient::class)->findAll();

        usort($comptes, function ($a, $b) use ($sortBy, $direction) {
            if ($sortBy === 'balance') {
                $valA = $a->getBalance();
                $valB = $b->getBalance();
            } else {
                $valA = $a->getNumeroSiren();
                $valB = $b->getNumeroSiren();
            }
            if ($valA == $valB) return 0;
            return ($direction === 'asc') ? ($valA < $valB ? -1 : 1) : ($valA > $valB ? -1 : 1);
        });

        return $this->render('product_owner/index.html.twig', [
            'comptes' => $comptes,
            'currentSort' => $sortBy,
            'currentDirection' => $direction,
        ]);
    }

    #[Route('/account/{id}', name: 'app_product_owner_account_show')]
    public function showAccount(CompteClient $compte): Response
    {
        return $this->render('product_owner/show_account.html.twig', ['compte' => $compte]);
    }

    #[Route('/account-request', name: 'app_product_owner_account_request')]
    public function accountRequest(EntityManagerInterface $entityManager, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $entrepriseNom = $request->request->get('entreprise_nom');
            $siret = $request->request->get('siret');
            $action = $request->request->get('action');

            $poRequest = new PoRequestAccount();
            $poRequest->setUsername($entrepriseNom) 
                      ->setPassword($siret)
                      ->setAction(PoAccountRequestAction::from($action))
                      ->setStatus(PoAccountRequestStatus::pending);

            $entityManager->persist($poRequest);
            $entityManager->flush();

            $this->addFlash('success', "✅ La demande de " . $action . " pour l'entreprise " . $entrepriseNom . " a bien été envoyée.");

            return $this->redirectToRoute('app_product_owner_account_request');
        }
        return $this->render('product_owner/account_request.html.twig');
    }
}