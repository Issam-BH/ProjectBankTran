<?php

namespace App\Controller;

use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_USER")]
final class RapportController extends AbstractController
{
    #[Route('/rapport', name: 'app_rapport')]
    public function index(TransactionRepository $transactionRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PO')) {
            return $this->redirectToRoute('app_admin');
        }
        
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $chartData = [];

        if ($this->isGranted('ROLE_COMMERCANT') && !$this->isGranted('ROLE_PO')) {
            $compte = $user->getCompteClient();
            if ($compte) {
                $chartData['evolution'] = $this->getEvolutionData($transactionRepository, $compte);
                $chartData['motifs'] = $this->getMotifsData($transactionRepository, $compte);
            }
        } elseif ($this->isGranted('ROLE_PO')) {
            $chartData['motifs'] = $this->getMotifsData($transactionRepository, null);
        }

        return $this->render('rapport/index.html.twig', [
            'chartData' => json_encode($chartData),
            'motifsLabels' => json_encode(Transaction::MOTIFS_IMPAYES),
        ]);
    }

    private function getEvolutionData(TransactionRepository $repo, \App\Entity\CompteClient $compte): array
    {
        $data = [];
        for ($i = 11; $i >= 0; --$i) {
            $date = new \DateTime("first day of -$i month");
            $month = $date->format('Y-m');
            $data[$month] = ['revenue' => 0, 'unpaid' => 0];
        }

        $revenue = $repo->createQueryBuilder('t')
            ->select("SUBSTRING(t.createdAt, 1, 7) as month, SUM(t.value) as total")
            ->where('t.compte_client = :compte')
            ->andWhere('t.value > 0')
            ->andWhere('t.createdAt >= :date')
            ->groupBy('month')
            ->setParameter('compte', $compte)
            ->setParameter('date', new \DateTime('-12 months'))
            ->getQuery()->getResult();

        $unpaid = $repo->createQueryBuilder('t')
            ->select("SUBSTRING(t.createdAt, 1, 7) as month, SUM(t.value) as total")
            ->where('t.compte_client = :compte')
            ->andWhere('t.value < 0')
            ->andWhere('t.remise IS NULL')
            ->andWhere('t.createdAt >= :date')
            ->groupBy('month')
            ->setParameter('compte', $compte)
            ->setParameter('date', new \DateTime('-12 months'))
            ->getQuery()->getResult();

        foreach ($revenue as $row) {
            $data[$row['month']]['revenue'] = (float) $row['total'];
        }
        foreach ($unpaid as $row) {
            $data[$row['month']]['unpaid'] = abs((float) $row['total']);
        }

        return $data;
    }

    private function getMotifsData(TransactionRepository $repo, ?\App\Entity\CompteClient $compte): array
    {
        $qb = $repo->createQueryBuilder('t')
            ->select('t.motif, COUNT(t.id) as count, SUM(t.value) as total')
            ->where('t.value < 0')
            ->andWhere('t.remise IS NULL')
            ->andWhere('t.motif IS NOT NULL')
            ->groupBy('t.motif');

        if ($compte) {
            $qb->andWhere('t.compte_client = :compte')->setParameter('compte', $compte);
        }

        $result = $qb->getQuery()->getResult();
        $data = [];
        foreach ($result as $row) {
            $data[$row['motif']] = [
                'count' => $row['count'],
                'total' => abs((float)$row['total']),
            ];
        }

        return $data;
    }
}
