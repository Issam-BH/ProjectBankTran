<?php

namespace App\Controller;

use App\Entity\Remise;
use App\Entity\User;
use App\Repository\CompteClientRepository;
use App\Repository\RemiseRepository;
use App\Repository\TransactionRepository;
use App\Service\ExportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted("ROLE_USER")]
final class RemiseController extends AbstractController
{
    #[Route('/remise', name: 'app_remise')]
    public function index(Request $request, RemiseRepository $remiseRepository, CompteClientRepository $compteClientRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PO')) {
            return $this->redirectToRoute('app_admin');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 10));

        $remisesQb = $this->getRemisesQueryBuilder($request, $remiseRepository);

        // Pagination logic
        $countQb = clone $remisesQb;
        $countQb->select('count(DISTINCT r.id)');
        $totalCount = $countQb->getQuery()->getSingleScalarResult();

        $remisesQb->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $remises = $remisesQb->getQuery()->getResult();
        $totalPages = ceil($totalCount / $limit);

        $comptes = [];
        if ($this->isGranted('ROLE_PO')) {
            $comptes = $compteClientRepository->findBy([], ['label' => 'ASC']);
        }

        return $this->render('remise/index.html.twig', [
            'remises' => $remises,
            'totalCount' => $totalCount,
            'currentPage' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'q' => $request->query->get('q'),
            'compteId' => $request->query->get('compte_id'),
            'comptes' => $comptes,
        ]);
    }

    #[Route('/remise/new', name: 'app_remise_new')]
    #[IsGranted('ROLE_COMMERCANT')]
    public function new(Request $request, TransactionRepository $transactionRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $compte = $user->getCompteClient();

        if (!$compte) {
            $this->addFlash('error', 'Vous devez avoir un compte commerçant pour créer une remise.');
            return $this->redirectToRoute('app_remise');
        }

        $transactions = $transactionRepository->findAvailableForRemise($compte);

        if ($request->isMethod('POST')) {
            $selectedIds = $request->request->all('transactions');
            if (empty($selectedIds)) {
                $this->addFlash('error', 'Veuillez sélectionner au moins une transaction.');
            } else {
                $remise = new Remise();
                $remise->setCompteClient($compte);
                $remise->setNumero('REM-' . date('YmdHis')); // Simple number generation

                $count = 0;
                foreach ($selectedIds as $id) {
                    $transaction = $transactionRepository->find($id);
                    if ($transaction && $transaction->getCompteClient() === $compte && $transaction->getRemise() === null) {
                        $remise->addTransaction($transaction);
                        $count++;
                    }
                }

                if ($count > 0) {
                    $entityManager->persist($remise);
                    $entityManager->flush();
                    $this->addFlash('success', 'Remise créée avec succès.');
                    return $this->redirectToRoute('app_remise');
                } else {
                    $this->addFlash('error', 'Aucune transaction valide sélectionnée.');
                }
            }
        }

        return $this->render('remise/new.html.twig', [
            'transactions' => $transactions,
        ]);
    }

    #[Route('/remise/export/{format}', name: 'app_remise_export')]
    public function export(
        Request $request,
        RemiseRepository $remiseRepository,
        CompteClientRepository $compteClientRepository,
        ExportService $exportService,
        string $format
    ): Response {
        if ($this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_PO')) {
            return $this->redirectToRoute('app_admin');
        }

        if (!in_array($format, ['csv', 'xls', 'pdf'])) {
            throw $this->createNotFoundException('Invalid format');
        }

        $remisesQb = $this->getRemisesQueryBuilder($request, $remiseRepository);
        $remises = $remisesQb->getQuery()->getResult();

        $headers = ['Numéro', 'Date', 'Montant Total', 'Transactions', 'Compte', 'SIREN'];
        $data = [];
        foreach ($remises as $remise) {
            $data[] = [
                $remise->getNumero(),
                $remise->getDateCreation()->format('d/m/Y H:i'),
                $remise->getTotalAmount(),
                $remise->getTransactions()->count(),
                $remise->getCompteClient()->getLabel(),
                $remise->getCompteClient()->getNumeroSiren(),
            ];
        }

        $title = "LISTE DES REMISES";
        $compteId = $request->query->get('compte_id');
        if ($compteId && $this->isGranted('ROLE_PO')) {
            $compte = $compteClientRepository->find($compteId);
            if ($compte) {
                $title .= ' DE L\'ENTREPRISE ' . $compte->getLabel() . ' N° SIREN ' . $compte->getNumeroSiren();
            }
        } elseif ($this->isGranted('ROLE_COMMERCANT')) {
            $user = $this->getUser();
            if ($user instanceof User && $user->getCompteClient()) {
                $compte = $user->getCompteClient();
                $title .= ' DE L\'ENTREPRISE ' . $compte->getLabel() . ' N° SIREN ' . $compte->getNumeroSiren();
            }
        }

        return match ($format) {
            'csv' => $exportService->createCsvResponse($data, $headers, $title),
            'xls' => $exportService->createXlsResponse($data, $headers, $title),
            'pdf' => $exportService->createPdfResponse($data, $headers, $title),
        };
    }

    private function getRemisesQueryBuilder(Request $request, RemiseRepository $remiseRepository): \Doctrine\ORM\QueryBuilder
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $q = $request->query->get('q');
        $compteId = $request->query->get('compte_id');

        $qb = $remiseRepository->createQueryBuilder('r')
            ->leftJoin('r.transactions', 't')
            ->addSelect('t')
            ->leftJoin('r.compteClient', 'c')
            ->addSelect('c')
            ->orderBy('r.dateCreation', 'DESC');

        // Role-based filtering
        if ($this->isGranted('ROLE_COMMERCANT') && !$this->isGranted('ROLE_PO')) {
            $compte = $user->getCompteClient();
            if ($compte) {
                $qb->andWhere('r.compteClient = :compte')
                    ->setParameter('compte', $compte);
            } else {
                $qb->andWhere('1 = 0');
            }
        } elseif ($this->isGranted('ROLE_PO') && $compteId) {
            $qb->andWhere('r.compteClient = :compteId')
                ->setParameter('compteId', $compteId);
        }

        if ($q) {
            $qb->andWhere('r.numero LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        return $qb;
    }
}
