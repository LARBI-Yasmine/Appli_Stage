<?php


namespace App\Service;

use App\Entity\User;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;


class NotificationService
{

private $entityManager;
private $security;

public function __construct(EntityManagerInterface $entityManager, Security $security)
{
$this->entityManager = $entityManager;
$this->security = $security;
}

public function createNotification(string $contenu, User $user): void

{

    

$notification = new Notification();
$notification->setContenu($contenu);
$notification->setEstLu(false);
$notification->setCreatedAt(new \DateTimeImmutable());
$notification->setUser($user);

$this->entityManager->persist($notification);
$this->entityManager->flush();
}

public function notifyReservationApproval(User $user): void
{
    if ($this->security->isGranted(attributes: 'ROLE_USER', subject: $user)) {

    
            $this->createNotification('Votre réservation a été approuvée.', $user);
        }
    }

    public function notifyReservationRejection(User $user): void
    {
        if ($this->security->isGranted('ROLE_USER', $user)) {

            $this->createNotification('Votre réservation a été refusée.', $user);
        }
    }
}