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

#[IsGranted('ROLE_PO')]
final class ProductOwnerController extends AbstractController
{
    #[Route('/product-owner', name: 'app_product_owner')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $comptes = $entityManager->getRepository(CompteClient::class)->findAll();

        return $this->render('product_owner/index.html.twig', [
            'controller_name' => 'ProductOwnerController',
            'comptes' => $comptes
        ]);
    }

        #[Route('/product-owner/dashboard', name: 'app_product_owner_dashboard')]
    public function dashboard(EntityManagerInterface $entityManager,Request $request): Response
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

        return $this->render('product_owner/dashboard.html.twig', [
            'comptes' => $comptes,
            'message' => $message,
        ]);
    }
}
