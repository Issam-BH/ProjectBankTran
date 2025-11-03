<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImpayeController extends AbstractController
{
    #[Route('/impaye', name: 'app_impaye')]
    public function index(): Response
    {
        return $this->render('impaye/index.html.twig', [
            'controller_name' => 'ImpayeController',
        ]);
    }
}
