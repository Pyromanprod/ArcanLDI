<?php

namespace App\Entity;

use App\Repository\RoleGroupeRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleGroupeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class RoleGroupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 50)]
    private $name;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'roleGroupes')]
    #[ORM\JoinColumn(nullable: false)]
    private $game;

    #[ORM\OneToMany(mappedBy: 'roleGroupe', targetEntity: Article::class, orphanRemoval: true)]
    private $articles;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $updatedAt;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'roleGroupes')]
    private $player;

    public function __toString(): string
    {
        return $this->getName();
    }

    public function __construct()
    {
        $this->articles = new ArrayCollection();
        $this->player = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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



    /**
     * @return Collection|Article[]
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles[] = $article;
            $article->setRoleGroupe($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getRoleGroupe() === $this) {
                $article->setRoleGroupe(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    #[ORM\PrePersist]
    public function setCreatedAt(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAt(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * @return Collection|User[]
     */
    public function getPlayer(): Collection
    {
        return $this->player;
    }

    public function addPlayer(User $player): self
    {
        if (!$this->player->contains($player)) {
            $this->player[] = $player;
        }

        return $this;
    }

    public function removePlayer(User $player): self
    {
        $this->player->removeElement($player);

        return $this;
    }
}
