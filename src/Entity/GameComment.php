<?php

namespace App\Entity;

use App\Repository\GameCommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: GameCommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class GameComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'text')]
    #[Assert\Length(
        min: 30,
        max: 10000,
        minMessage: 'votre commentaire doit faire au minimum  {{ limit }} caractère de long',
        maxMessage: 'votre commentaire peut faire au maximum   {{ limit }} caractère de long',
    )]
    private $content;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'gameComments')]
    #[ORM\JoinColumn(nullable: false)]
    private $author;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'gameComments')]
    #[ORM\JoinColumn(nullable: false)]
    private $game;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
