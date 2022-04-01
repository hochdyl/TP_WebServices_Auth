<?php

namespace App\DataFixtures;

use App\Entity\Token;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;

class AccountFixtures extends Fixture
{
    /**
     * @throws Exception
     */
    public function load(ObjectManager $manager): void
    {
        // User accounts
        for ($i = 0; $i <= 2; $i++) {
            $user = new User();
            $user->setLogin('user'.$i);
            $user->setPassword('pass'.$i);

            $token = new Token();
            $user->setToken($token);

            $manager->persist($token);
            $manager->persist($user);
        }

        // Admin account
        $user = new User();
        $user->setLogin('admin');
        $user->setPassword('pass');
        $user->setRoles(['ROLE_ADMIN']);

        $token = new Token();
        $user->setToken($token);

        $manager->persist($token);
        $manager->persist($user);

        $manager->flush();

        echo "\n";
        echo "================= ADMIN CREDENTIALS =================\n";
        echo "-> Login\n";
        echo "\033[36m admin \033[0m\n";
        echo "-> Password\n";
        echo "\033[36m pass \033[0m\n";
        echo "-> Access token\n";
        echo "\033[36m".$token->getAccessToken()."\033[0m\n";
        echo "-> Refresh token\n";
        echo "\033[36m".$token->getRefreshToken()."\033[0m\n";
        echo "================= ENJOY TESTING =================\n";
        echo "\n";
    }
}