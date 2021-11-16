<?php

namespace App\Entity;

use App\Repository\MembershipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MembershipRepository::class)]
class Membership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $price;

    #[ORM\Column(type: 'string', length: 4)]
    private $year;

    #[ORM\OneToMany(mappedBy: 'membership', targetEntity: MembershipAssociation::class, orphanRemoval: true)]
    private $membershipAssociations;

    public function __construct()
    {
        $this->membershipAssociations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getYear(): ?string
    {
        return $this->year;
    }

    public function setYear(string $year): self
    {
        $this->year = $year;

        return $this;
    }

    /**
     * @return Collection|MembershipAssociation[]
     */
    public function getMembershipAssociations(): Collection
    {
        return $this->membershipAssociations;
    }

    public function addMembershipAssociation(MembershipAssociation $membershipAssociation): self
    {
        if (!$this->membershipAssociations->contains($membershipAssociation)) {
            $this->membershipAssociations[] = $membershipAssociation;
            $membershipAssociation->setMembership($this);
        }

        return $this;
    }

    public function removeMembershipAssociation(MembershipAssociation $membershipAssociation): self
    {
        if ($this->membershipAssociations->removeElement($membershipAssociation)) {
            // set the owning side to null (unless already changed)
            if ($membershipAssociation->getMembership() === $this) {
                $membershipAssociation->setMembership(null);
            }
        }

        return $this;
    }
}
