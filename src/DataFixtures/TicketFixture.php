<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\Ticket;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker;

class TicketFixture extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {

            $ticket = new Ticket();

            $ticket
                ->setName($faker->title)
                ->setPrice($faker->numberBetween(200,500))
                ->setStock($faker->numberBetween(10,200))
                ->setGame($this->getReference('gdn'));
            $manager->persist($ticket);
        }


        $manager->flush();
    }
    public function getDependencies(): array
    {
        return [AppFixtures::class];
    }
}
