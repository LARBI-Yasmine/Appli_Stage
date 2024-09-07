<?php 

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\AccountExpiredException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserConfirmation implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;

        }

        if (!$user->isVerified()) {
           
            throw new CustomUserMessageAccountStatusException("Votre compte n'est pas vérifié, merci de le confirmer avant le
            {$user->getTokenRegistrationLifeTime()->format('d-m-Y à H:i:s') }");
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

       
    }
}