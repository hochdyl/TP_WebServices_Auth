<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['login'], message: 'This login already exist.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['public'])]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Login cannot be empty.')]
    #[Groups(['public'])]
    private $login;

    #[ORM\Column(type: 'json')]
    #[Groups(['public'])]
    private $roles = [];

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(message: 'Login cannot be empty.')]
    private $password;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Choice(choices: ['open', 'close'], message: 'Choose a valid status.')]
    #[Assert\NotNull(message: 'A status is required.')]
    private $status;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'Created at date cannot be empty.')]
    #[Groups(['public'])]
    private $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotBlank(message: 'Updated at date cannot be empty.')]
    #[Groups(['public'])]
    private $updatedAt;

    #[ORM\OneToOne(inversedBy: 'user', targetEntity: Token::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'A token is required.')]
    private $token;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->status = "open";
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): self
    {
        $this->login = $login;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->login;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getToken(): ?Token
    {
        return $this->token;
    }

    public function setToken(Token $token): self
    {
        $this->token = $token;

        return $this;
    }
}
