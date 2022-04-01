<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['public'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['public', 'accessToken'])]
    private $accessToken;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['public', 'accessToken'])]
    private $accessTokenExpiresAt;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['public'])]
    private $refreshToken;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['public'])]
    private $refreshTokenExpiresAt;

    #[ORM\OneToOne(mappedBy: 'token', targetEntity: User::class, cascade: ['persist', 'remove'])]
    private $user;

    public function __construct()
    {
        $this->accessToken = bin2hex(random_bytes(64));
        $this->accessTokenExpiresAt = new DateTime('+60 minutes');
        $this->refreshToken = bin2hex(random_bytes(64));
        $this->refreshTokenExpiresAt = new DateTime('+120 minutes');
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

    public function isAccessTokenExpired(): bool
    {
        $now = new DateTime();
        $baseTimestamp = strtotime($now->format('Y-m-d H:i:s'));
        $expireTimestamp = strtotime($this->getAccessTokenExpiresAt()->format('Y-m-d H:i:s'));
        $lifetimeTimestamp = $expireTimestamp - $baseTimestamp;
        return $lifetimeTimestamp < 0;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function getRefreshTokenExpiresAt(): DateTime
    {
        return $this->refreshTokenExpiresAt;
    }

    public function isRefreshTokenExpired(): bool
    {
        $now = new DateTime();
        $baseTimestamp = strtotime($now->format('Y-m-d H:i:s'));
        $expireTimestamp = strtotime($this->getRefreshTokenExpiresAt()->format('Y-m-d H:i:s'));
        $lifetimeTimestamp = $expireTimestamp - $baseTimestamp;
        return $lifetimeTimestamp < 0 || $this->getUser() === null;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        // set the owning side of the relation if necessary
        if ($user->getToken() !== $this) {
            $user->setToken($this);
        }

        $this->user = $user;

        return $this;
    }
}
