<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COMMERCANT')]
final class TransactionController extends AbstractController
{
    #[Route('/transaction/new', name: 'app_transaction_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        
        $compte = $user->getCompteClient();
        if (!$compte) {
            $this->addFlash('error', 'Vous devez avoir un compte commerçant pour créer une transaction.');
            return $this->redirectToRoute('app_commercant');
        }

        if ($request->isMethod('POST')) {
            $label = $request->request->get('label');
            $value = $request->request->get('value');
            $isImpaye = $request->request->get('is_impaye') === '1';
            $motif = $request->request->get('motif');

            if ($label && is_numeric($value)) {
                $transaction = new Transaction();
                $transaction->setLabel($label);
                
                $amount = (float)$value;
                if ($isImpaye) {
                    $amount = -abs($amount); // Ensure it's negative
                    if ($motif && array_key_exists($motif, Transaction::MOTIFS_IMPAYES)) {
                        $transaction->setMotif($motif);
                    } else {
                        $this->addFlash('error', 'Veuillez sélectionner un motif valide pour l\'impayé.');
                        return $this->render('transaction/new.html.twig', ['motifs' => Transaction::MOTIFS_IMPAYES]);
                    }
                }
                
                $transaction->setValue($amount);
                $transaction->setCompteClient($compte);

                $entityManager->persist($transaction);
                $entityManager->flush();

                $this->addFlash('success', 'Transaction créée avec succès.');
                return $this->redirectToRoute('app_commercant');
            } else {
                $this->addFlash('error', 'Veuillez remplir correctement le formulaire.');
            }
        }

        return $this->render('transaction/new.html.twig', [
            'motifs' => Transaction::MOTIFS_IMPAYES
        ]);
    }
}
