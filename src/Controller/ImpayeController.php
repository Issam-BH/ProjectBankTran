<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_USER")]
final class ImpayeController extends AbstractController
{
    #[Route('/impaye', name: 'app_impaye')]
    public function index(Request $request, TransactionRepository $transactionRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PO')) {
            return $this->redirectToRoute('app_admin');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $impayes = [];
        $impayesBySiren = [];

        // Base query for unpaid transactions
        $qb = $transactionRepository->createQueryBuilder('t')
            ->where('t.value < 0')
            ->andWhere('t.remise IS NULL');

        if ($this->isGranted('ROLE_COMMERCANT') && !$this->isGranted('ROLE_PO')) {
            // US1 & US2 for Commercant
            $compte = $user->getCompteClient();
            if ($compte) {
                $qb->andWhere('t.compte_client = :compte')->setParameter('compte', $compte);

                $startDate = $request->query->get('start_date');
                $endDate = $request->query->get('end_date');
                $sort = $request->query->get('sort', 'DESC');

                if ($startDate) {
                    $qb->andWhere('t.createdAt >= :startDate')->setParameter('startDate', new \DateTime($startDate));
                }
                if ($endDate) {
                    $qb->andWhere('t.createdAt <= :endDate')->setParameter('endDate', (new \DateTime($endDate))->setTime(23, 59, 59));
                }

                $qb->orderBy('t.value', $sort);
                $impayes = $qb->getQuery()->getResult();
            }
        } elseif ($this->isGranted('ROLE_PO')) {
            // US3 for PO
            $qb->select('IDENTITY(t.compte_client) as compteId, c.numero_siren, c.label, SUM(t.value) as totalImpayes, c.devise')
               ->leftJoin('t.compte_client', 'c')
               ->groupBy('c.id')
               ->orderBy('c.numero_siren');

            $impayesBySiren = $qb->getQuery()->getResult();
        }

        return $this->render('impaye/index.html.twig', [
            'impayes' => $impayes,
            'impayesBySiren' => $impayesBySiren,
            'startDate' => $request->query->get('start_date'),
            'endDate' => $request->query->get('end_date'),
            'sort' => $request->query->get('sort', 'DESC'),
        ]);
    }
}
