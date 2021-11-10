<?php

namespace App\Entity;

use App\Repository\TicketRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TicketRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 50)]
    private $name;

    #[ORM\Column(type: 'float')]
    private $price;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: Order::class, orphanRemoval: true)]
    private $orders;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false)]
    private $game;

    #[ORM\Column(type: 'datetime_immutable')]
    private $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private $updatedAt;

    #[ORM\Column(type: 'integer')]
    private $stock;

    #[ORM\OneToMany(mappedBy: 'ticket', targetEntity: SurveyTicket::class, orphanRemoval: true)]
    private $surveyTickets;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $cgv;

    public function __toString(): string
    {
        return $this->getName().'-'.$this->getPrice().' euro';
    }

    public function __construct()
    {
        $this->orders = new ArrayCollection();
        $this->surveyTickets = new ArrayCollection();

        
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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

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
            $order->setTicket($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): self
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getTicket() === $this) {
                $order->setTicket(null);
            }
        }

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

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    /**
     * @return Collection|SurveyTicket[]
     */
    public function getSurveyTickets(): Collection
    {
        return $this->surveyTickets;
    }

    public function addSurveyTicket(SurveyTicket $surveyTicket): self
    {
        if (!$this->surveyTickets->contains($surveyTicket)) {
            $this->surveyTickets[] = $surveyTicket;
            $surveyTicket->setTicket($this);
        }

        return $this;
    }

    public function removeSurveyTicket(SurveyTicket $surveyTicket): self
    {
        if ($this->surveyTickets->removeElement($surveyTicket)) {
            // set the owning side to null (unless already changed)
            if ($surveyTicket->getTicket() === $this) {
                $surveyTicket->setTicket(null);
            }
        }

        return $this;
    }

    public function getCgv(): ?string
    {
        return $this->cgv;
    }

    public function setCgv(?string $cgv): self
    {
        $this->cgv = $cgv;

        return $this;
    }
}
