<?php

namespace App\Entity;

use App\Repository\CompteClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteClientRepository::class)]
class CompteClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $numero_siren = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(length: 255)]
    private ?string $devise = null;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'compte_client', orphanRemoval: true)]
    private Collection $transactions;

    /**
     * @var Collection<int, Remise>
     */
    #[ORM\OneToMany(targetEntity: Remise::class, mappedBy: 'compteClient')]
    private Collection $remises;

    #[ORM\OneToOne(inversedBy: 'compteClient', cascade: ['persist', 'remove'])]
    private ?User $user = null;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->remises = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroSiren(): ?string
    {
        return $this->numero_siren;
    }

    public function setNumeroSiren(string $numero_siren): static
    {
        $this->numero_siren = $numero_siren;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getDevise(): ?string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setCompteClient($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCompteClient() === $this) {
                $transaction->setCompteClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Remise>
     */
    public function getRemises(): Collection
    {
        return $this->remises;
    }

    public function addRemise(Remise $remise): static
    {
        if (!$this->remises->contains($remise)) {
            $this->remises->add($remise);
            $remise->setCompteClient($this);
        }

        return $this;
    }

    public function removeRemise(Remise $remise): static
    {
        if ($this->remises->removeElement($remise)) {
            // set the owning side to null (unless already changed)
            if ($remise->getCompteClient() === $this) {
                $remise->setCompteClient(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBalance(): float
    {
        $balance = 0.0;
        foreach ($this->transactions as $transaction) {
            $balance += (float) $transaction->getValue();
        }
        return $balance;
    }
}
