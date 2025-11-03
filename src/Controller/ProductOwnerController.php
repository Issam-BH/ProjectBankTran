<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_PO')]
final class ProductOwnerController extends AbstractController
{
    #[Route('/product-owner', name: 'app_product_owner')]
    public function index(): Response
    {
        return $this->render('product_owner/index.html.twig', [
            'controller_name' => 'ProductOwnerController',
        ]);
    }
}
