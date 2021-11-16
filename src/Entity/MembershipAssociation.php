<?php

namespace App\Entity;

use App\Repository\MembershipAssociationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MembershipAssociationRepository::class)]
class MembershipAssociation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: Membership::class, inversedBy: 'membershipAssociations')]
    #[ORM\JoinColumn(nullable: false)]
    private $membership;

    #[ORM\Column(type: 'boolean')]
    private $paid;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'membershipAssociations')]
    #[ORM\JoinColumn(nullable: false)]
    private $member;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMembership(): ?Membership
    {
        return $this->membership;
    }

    public function setMembership(?Membership $membership): self
    {
        $this->membership = $membership;

        return $this;
    }

    public function getPaid(): ?bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): self
    {
        $this->paid = $paid;

        return $this;
    }

    public function getMember(): ?User
    {
        return $this->member;
    }

    public function setMember(?User $member): self
    {
        $this->member = $member;

        return $this;
    }

}
