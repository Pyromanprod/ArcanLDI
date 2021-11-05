<?php

namespace App\Entity;

use App\Repository\SurveyTicketRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SurveyTicketRepository::class)]
class SurveyTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $orderBy;

    #[ORM\ManyToOne(targetEntity: Survey::class, inversedBy: 'surveyTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private $survey;

    #[ORM\ManyToOne(targetEntity: Ticket::class, inversedBy: 'surveyTickets')]
    #[ORM\JoinColumn(nullable: false)]
    private $ticket;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderBy(): ?int
    {
        return $this->orderBy;
    }

    public function setOrderBy(?int $orderBy): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(?Survey $survey): self
    {
        $this->survey = $survey;

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): self
    {
        $this->ticket = $ticket;

        return $this;
    }
}
