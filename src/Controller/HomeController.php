<?php

namespace App\Controller;

use App\Entity\Objet;
use App\Entity\Reservation;
use App\Repository\ObjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class HomeController extends AbstractController
{


    private $notificationRepository;
    private  $objetRepo;

    public function __construct(NotificationRepository $notificationRepository,ObjetRepository $objetRepo,EntityManagerInterface $entityManager)
    {
        $this->notificationRepository = $notificationRepository;
        $this->objetRepo = $objetRepo;
        $this->entityManager = $entityManager;
    }


    
    #[Route('/home', name: 'app_home')]
    public function index(Request $request,ObjetRepository $objetRepo, PaginatorInterface $paginator): Response
    {
     // Utilisation du QueryBuilder pour paginer les objets
     $query = $this->objetRepo->createQueryBuilder('o')
     ->orderBy('o.id', 'ASC')
     ->getQuery();
       


        // Paginer les résultats
        $objets = $paginator->paginate(
            $query, // Requête ou QueryBuilder contenant les données à paginer
            $request->query->getInt('page', 1), 6);

        // Récupérer les notifications non lues
        $notifications = $this->notificationRepository->findUnreadNotifications();

        return $this->render('home/home.html.twig', [
            'objets' => $objets,
            'notifications' => $notifications,
        ]);
    }


     


}