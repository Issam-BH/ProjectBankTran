<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RemiseController extends AbstractController
{
    #[Route('/remise', name: 'app_remise')]
    public function index(): Response
    {
        return $this->render('remise/index.html.twig', [
            'controller_name' => 'RemiseController',
        ]);
    }
}
