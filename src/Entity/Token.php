<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Exception;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $accessToken;

    #[ORM\Column(type: 'datetime')]
    private $accessTokenExpiresAt;

    #[ORM\Column(type: 'string', length: 255)]
    private $refreshToken;

    #[ORM\Column(type: 'datetime')]
    private $refreshTokenExpiresAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tokens')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->accessToken = bin2hex(random_bytes(64));
        $this->accessTokenExpiresAt = new \DateTime('+1 hour');
        $this->refreshToken = bin2hex(random_bytes(64));
        $this->refreshTokenExpiresAt = new \DateTime('+2 hours');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function getAccessTokenExpiresAt(): DateTime
    {
        return $this->accessTokenExpiresAt;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getRefreshTokenExpiresAt(): DateTime
    {
        return $this->refreshTokenExpiresAt;
    }

    public function setRefreshTokenExpiresAt(DateTimeImmutable $refreshTokenExpiresAt): self
    {
        $this->refreshTokenExpiresAt = $refreshTokenExpiresAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
