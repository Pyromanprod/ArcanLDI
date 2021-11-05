<?php

namespace App\Entity;

use App\Repository\SurveyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SurveyRepository::class)]
class Survey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\OneToMany(mappedBy: 'survey', targetEntity: Question::class, orphanRemoval: true)]
    private $question;


    #[ORM\Column(type: 'boolean')]
    private $general;

    #[ORM\OneToMany(mappedBy: 'survey', targetEntity: SurveyTicket::class, orphanRemoval: true)]
    private $surveyTickets;

    public function __construct()
    {
        $this->question = new ArrayCollection();
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

    /**
     * @return Collection|Question[]
     */
    public function getQuestion(): Collection
    {
        return $this->question;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->question->contains($question)) {
            $this->question[] = $question;
            $question->setSurvey($this);
        }

        return $this;
    }

    public function removeQuestion(Question $question): self
    {
        if ($this->question->removeElement($question)) {
            // set the owning side to null (unless already changed)
            if ($question->getSurvey() === $this) {
                $question->setSurvey(null);
            }
        }

        return $this;
    }

    public function getGeneral(): ?bool
    {
        return $this->general;
    }

    public function setGeneral(bool $general): self
    {
        $this->general = $general;

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
            $surveyTicket->setSurvey($this);
        }

        return $this;
    }

    public function removeSurveyTicket(SurveyTicket $surveyTicket): self
    {
        if ($this->surveyTickets->removeElement($surveyTicket)) {
            // set the owning side to null (unless already changed)
            if ($surveyTicket->getSurvey() === $this) {
                $surveyTicket->setSurvey(null);
            }
        }

        return $this;
    }
}
