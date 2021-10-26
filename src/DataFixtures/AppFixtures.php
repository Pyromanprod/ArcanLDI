<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $encoder;
    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }
    public function load(ObjectManager $manager): void
    {

        $admin = new User();

        $admin
            ->setEmail('a@a.a')
            ->setFirstName('Enzo')
            ->setPseudo('ArcanAdmin')
            ->setLastname('Renaud')
            ->setRoles(["ROLE_ADMIN"])
            ->setPassword(
                $this->encoder->hashPassword($admin, 'a')
            )
        ;
        $manager->persist($admin);

        $gdn = new Game();

        $gdn
            ->setName('Arcan')
            ->setDescription('je suis un jeu hey !')
        ;


        $manager->flush();
    }
}
