<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    public const MOTIFS_IMPAYES = [
        '01' => 'Fraude à la carte',
        '02' => 'Compte à découvert',
        '03' => 'Compte clôturé',
        '04' => 'Compte bloqué',
        '05' => 'Provision insuffisante',
        '06' => 'Opération contestée par le débiteur',
        '07' => 'Titulaire décédé',
        '08' => 'Raison non communiquée',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 3)]
    private ?string $value = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CompteClient $compte_client = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    private ?Remise $remise = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $motif = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getCompteClient(): ?CompteClient
    {
        return $this->compte_client;
    }

    public function setCompteClient(?CompteClient $compte_client): static
    {
        $this->compte_client = $compte_client;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getRemise(): ?Remise
    {
        return $this->remise;
    }

    public function setRemise(?Remise $remise): static
    {
        $this->remise = $remise;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }
}
