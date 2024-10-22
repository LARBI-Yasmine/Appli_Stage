<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class BaseController extends AbstractController
{
    private $notificationRepository;
    private $security;

    public function __construct(NotificationRepository $notificationRepository, Security $security)
    {
        $this->notificationRepository = $notificationRepository;
        $this->security = $security;
    }

    protected function renderWithNotifications(string $view, array $parameters = [], ?Response $response = null): Response
    {
        $user = $this->security->getUser();

        // Fetch unread notifications for admin only
        if ($user && $this->isGranted('ROLE_ADMIN')) {
            $notifications = $this->notificationRepository->findUnreadNotifications();
        } else {
            $notifications = [];
        }

        $parameters['notifications'] = $notifications;
      

        return parent::render($view, $parameters, $response);
       
        
    }

}