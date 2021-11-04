<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;


#[UniqueEntity(fields: 'email', message:"Adresse Mail déjà utilisée")]
#[UniqueEntity(fields: 'pseudo', message:"Pseudo déjà utilisée")]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    #[Assert\NotBlank(message: 'tout les champ doivent etre remplis')]
    #[Assert\Email(message: 'l\'email {{ value }} n\'est pas un email valide')]
    private $email;

    #[ORM\Column(type: 'json')]
    private $roles = [];

    #[ORM\Column(type: 'string')]
    private $password;

    #[Assert\NotBlank(message: 'tout les champ doivent etre remplis')]
    #[ORM\Column(type: 'string', length: 36)]
    private $firstname;

    #[Assert\NotBlank(message: 'tout les champ doivent etre remplis')]
    #[ORM\Column(type: 'string', length: 32)]
    private $lastname;

    #[Assert\NotBlank(message: 'tout les champ doivent etre remplis')]
    #[ORM\Column(type: 'string', length: 50)]
    private $pseudo;

    #[ORM\OneToMany(mappedBy: 'player', targetEntity: Order::class)]
    private $orders;

    #[ORM\OneToMany(mappedBy: 'player', targetEntity: Answer::class, orphanRemoval: true)]
    private $answers;


    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Article::class)]
    private $articles;

    #[ORM\OneToMany(mappedBy: 'player', targetEntity: Coment::class)]
    private $coments;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $updatedAt;

    #[ORM\ManyToMany(targetEntity: RoleGroupe::class, mappedBy: 'player')]
    private $roleGroupes;

    #[ORM\Column(type: 'datetime')]
    private $birthDate;

    #[ORM\Column(type: 'string', length: 60)]
    private $photo;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: NewsComment::class, orphanRemoval: true)]
    private $newsComments;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: News::class, orphanRemoval: true)]
    private $news;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->articles = new ArrayCollection();
        $this->coments = new ArrayCollection();
        $this->roleGroupes = new ArrayCollection();
        $this->newsComments = new ArrayCollection();
        $this->news = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return (string) $this->email;
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
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    /**
     * @return Collection|Order[]
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): self
    {
        if (!$this->orders->contains($order)) {
            $this->orders[] = $order;
            $order->setPlayer($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getPlayer() === $this) {
                $order->setPlayer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Answer[]
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setPlayer($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getPlayer() === $this) {
                $answer->setPlayer(null);
            }
        }

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
            $article->setAuthor($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getAuthor() === $this) {
                $article->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Coment[]
     */
    public function getComents(): Collection
    {
        return $this->coments;
    }

    public function addComent(Coment $coment): self
    {
        if (!$this->coments->contains($coment)) {
            $this->coments[] = $coment;
            $coment->setPlayer($this);
        }

        return $this;
    }

    public function removeComent(Coment $coment): self
    {
        if ($this->coments->removeElement($coment)) {
            // set the owning side to null (unless already changed)
            if ($coment->getPlayer() === $this) {
                $coment->setPlayer(null);
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
     * @return Collection|RoleGroupe[]
     */
    public function getRoleGroupes(): Collection
    {
        return $this->roleGroupes;
    }

    public function addRoleGroupe(RoleGroupe $roleGroupe): self
    {
        if (!$this->roleGroupes->contains($roleGroupe)) {
            $this->roleGroupes[] = $roleGroupe;
            $roleGroupe->addPlayer($this);
        }

        return $this;
    }

    public function removeRoleGroupe(RoleGroupe $roleGroupe): self
    {
        if ($this->roleGroupes->removeElement($roleGroupe)) {
            $roleGroupe->removePlayer($this);
        }

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeInterface $birthDate): self
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * @return Collection|NewsComment[]
     */
    public function getNewsComments(): Collection
    {
        return $this->newsComments;
    }

    public function addNewsComment(NewsComment $newsComment): self
    {
        if (!$this->newsComments->contains($newsComment)) {
            $this->newsComments[] = $newsComment;
            $newsComment->setAuthor($this);
        }

        return $this;
    }

    public function removeNewsComment(NewsComment $newsComment): self
    {
        if ($this->newsComments->removeElement($newsComment)) {
            // set the owning side to null (unless already changed)
            if ($newsComment->getAuthor() === $this) {
                $newsComment->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|News[]
     */
    public function getNews(): Collection
    {
        return $this->news;
    }

    public function addNews(News $news): self
    {
        if (!$this->news->contains($news)) {
            $this->news[] = $news;
            $news->setAuthor($this);
        }

        return $this;
    }

    public function removeNews(News $news): self
    {
        if ($this->news->removeElement($news)) {
            // set the owning side to null (unless already changed)
            if ($news->getAuthor() === $this) {
                $news->setAuthor(null);
            }
        }

        return $this;
    }
}
