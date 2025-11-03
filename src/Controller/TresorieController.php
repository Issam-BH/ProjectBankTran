<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TresorieController extends AbstractController
{
    #[Route('/tresorie', name: 'app_tresorie')]
    public function index(): Response
    {
        return $this->render('tresorie/index.html.twig', [
            'controller_name' => 'TresorieController',
        ]);
    }
}
