<?php

namespace App\Controller;

use App\Entity\Objet;
use App\Entity\Retour;
use App\Form\RetourType;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\UserRepository;
use App\Repository\ObjetRepository;
use App\Repository\RetourRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ReservationController extends AbstractController
{


    private $notificationService;
    private $security;


    public function __construct(Security $security,NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
        $this->security = $security;
    }



    
    #[Route('/ListReservations', name: 'app_reservations')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        $reservations = $reservationRepository->findBy(
            ['user' => $this->getUser()]);
   

        return $this->render('reservation/MesReservations.html.twig', [
            'reservations' => $reservations,
            

        ]);
    }

    



    #[Route('/reservation/new/{id}', name: 'app_reservation_new')]
    public function new(Request $request, Objet $objet, EntityManagerInterface $entityManager): Response

{


    $user = $this->getUser();

    // Check if the user is banned
    if ($user->getIsBanned()) {
        $this->addFlash('danger', 'Votre compte est banni en raison de retours tardifs répétés.');
        return $this->redirectToRoute('app_reservations');
    }

     // Check if the object is available
     if ($objet->getDisponibilite() !== 'Disponible') {
        
    
     }
        $reservation = new Reservation();
        $reservation->setUser($this->getUser()); 
        $reservation->setObjet($objet);

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $objet->setDisponibilite('réservé');
            $entityManager->persist($reservation);
            $entityManager->flush();

          
             $this->notificationService->createNotification('Nouvelle réservation.', $this->getUser());
             


             return $this->redirectToRoute('app_reservations');
          
        }
    

        return $this->render('reservation/newReservation.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reservation/manage', name: 'app_reservation_manage')]
    public function manage(ReservationRepository $reservationRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservations = $reservationRepository->findAll();

        return $this->render('reservation/reservationManage.html.twig', [
            'reservations' => $reservations,
        ]);
    }




    #[Route('/ListRetours', name: 'app_retours')]
    public function indexRetour(RetourRepository $retourRepository): Response
    {
        $retours = $retourRepository->findBy(
            ['user' => $this->getUser()]);
   

        return $this->render('Retour/MesRetours.html.twig', [
            'retours' => $retours,
            

        ]);
    }
    
    #[Route('/retour/manage', name: 'app_retour_manage')]
    public function manageRetour(RetourRepository $retourRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $retours = $retourRepository->findAll();

        return $this->render('Retour/retourManage.html.twig', [
            'retours' => $retours,
        ]);
    }




    #[Route('/reservation/approve/{id}', name: 'app_reservation_approve')]
    public function approve(Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation->setStatut('Demande Approuvée');
        $entityManager->flush();

         $user = $reservation->getUser();

         if (($this->security->isGranted('ROLE_USER', $user))  ){

       $this->notificationService->notifyReservationApproval($user);

         }
        return $this->redirectToRoute('app_reservation_manage');
    }





    #[Route('/reservation/reject/{id}', name: 'app_reservation_reject')]
    public function reject(Reservation $reservation, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $reservation->setStatut('Demande Refusée');
        $entityManager->flush();

        
         $user = $reservation->getUser();


         if (($this->security->isGranted('ROLE_USER', $user))  ){

       $this->notificationService->notifyReservationRejection($user);
         }

       


        return $this->redirectToRoute('app_reservation_manage');
    }





#[Route('/reservation/return/{id}', name: 'app_reservation_return')]
public function returnObject(Request $request, Reservation $reservation, int $id, EntityManagerInterface $entityManager): Response
{
    // Ensure the reservation is fetched correctly using the ID
    $reservation = $entityManager->getRepository(Reservation::class)->find($id);

    if (!$reservation) {
        throw $this->createNotFoundException('No reservation found for id ' . $id);
    }

    $retour = new Retour();
    $retour->setReservation($reservation);
    $retour->setUser($this->getUser());

    $form = $this->createForm(RetourType::class, $retour);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();

        $reservation->setStatut('Objet Retourné');
        $reservation->getObjet()->setDisponibilite('Disponible');
        $retour->setReturnDate($form->get('returnDate')->getData());
        $retour->setObjectStatus($form->get('objectStatus')->getData());

        $entityManager->persist($retour);

        // Check if the return is late
        $returnDate = $retour->getReturnDate();
        $plannedReturnDate = $reservation->getDateFin(); // assuming you have a dateFin field
        if ($returnDate > $plannedReturnDate) {
            $user = $reservation->getUser();
            $user->incrementLateReturnsCount();

            if ($user->getLateReturnsCount() > 3) {
                $user->banUser();
                $this->notificationService->createNotification('Votre compte a été banni en raison de retours tardifs répétés.', $user);
            }

            $entityManager->persist($user);
        }

        $entityManager->flush();

        $this->notificationService->createNotification('nouveau Retour', $reservation->getUser());

        return $this->redirectToRoute('app_reservations');
    }

    return $this->render('Retour/newRetour.html.twig', [
        'form' => $form->createView(),
    ]);
}
}