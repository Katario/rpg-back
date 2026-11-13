<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApiTokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Opaque API access token. Only the SHA-256 hash of the token is stored: the plain value is
 * handed to the client once, at issue time (see ApiTokenManager), and never persisted.
 */
#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
class ApiToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    // Assigned by Doctrine (generated value), which PHPStan cannot see without the Doctrine extension.
    private ?int $id = null; // @phpstan-ignore property.unusedType

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: User::class)]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private User $user,
        #[ORM\Column(type: 'string', length: 64, unique: true)]
        private string $tokenHash,
        #[ORM\Column(type: 'datetime_immutable')]
        private \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $now = new \DateTimeImmutable(),
    ) {
        $this->createdAt = $now;
    }

    public static function hash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function isExpired(\DateTimeImmutable $now = new \DateTimeImmutable()): bool
    {
        return $this->expiresAt <= $now;
    }

    public function markUsed(\DateTimeImmutable $now = new \DateTimeImmutable()): void
    {
        $this->lastUsedAt = $now;
    }
}
