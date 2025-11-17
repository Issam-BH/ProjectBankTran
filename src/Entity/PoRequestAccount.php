<?php

namespace App\Entity;

use App\Config\PoAccountRequestAction;
use App\Config\PoAccountRequestStatus;
use App\Repository\PoRequestAccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PoRequestAccountRepository::class)]
class PoRequestAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $username;

    #[ORM\Column(type: Types::ARRAY)]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private string $password;

    #[ORM\Column(enumType: PoAccountRequestAction::class)]
    private PoAccountRequestAction $action;

    #[ORM\Column(enumType: PoAccountRequestStatus::class)]
    private PoAccountRequestStatus $status = PoAccountRequestStatus::pending;

    public function __construct()
    {
        $this->status = PoAccountRequestStatus::pending;
    }

    public function getId(): ?int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): static { $this->username = $username; return $this; }

    public function getRoles(): array { return $this->roles; }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getAction(): PoAccountRequestAction { return $this->action; }
    public function setAction(PoAccountRequestAction $action): static { $this->action = $action; return $this; }

    public function getStatus(): PoAccountRequestStatus { return $this->status; }
    public function setStatus(PoAccountRequestStatus $status): static { $this->status = $status; return $this; }
}
