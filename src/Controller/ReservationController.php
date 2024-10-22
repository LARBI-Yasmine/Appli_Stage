<?php

namespace App\Controller;

use App\Entity\Objet;
use App\Entity\Retour;
use App\Form\RetourType;
use App\Entity\Reservation;
use App\Entity\Notification;
use App\Form\ReservationType;
use App\Service\MailerService;
use App\Repository\UserRepository;
use App\Repository\ObjetRepository;
use App\Repository\RetourRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ReservationRepository;
use Knp\Component\Pager\PaginatorInterface;
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


     /*Afficher la liste des reservations */

    #[Route('/ListReservations', name: 'app_reservations')]
public function index(ReservationRepository $reservationRepository, PaginatorInterface $paginator, Request $request): Response
{
    $queryBuilder = $reservationRepository->createQueryBuilder('r')
        ->where('r.user = :user')
        ->setParameter('user', $this->getUser())
        ->orderBy('r.date_debut', 'DESC');

    $pagination = $paginator->paginate(
        $queryBuilder, 
        $request->query->getInt('page', 1), 
        5 
    );

    return $this->render('reservation/MesReservations.html.twig', [
        'pagination' => $pagination,
    ]);
}




 /*Créer une nouvelle réservation */

 #[Route('/reservation/new/{id}', name: 'app_reservation_new')]
 public function new(Request $request, Objet $objet, EntityManagerInterface $entityManager, MailerService $mailerService): Response
 {
     $user = $this->getUser();
 
     if ($user->IsBanned()) {
        $this->addFlash('danger', 'Votre compte est banni en raison de retours tardifs répétés.');

    // Notification pour l'utilisateur
    $this->notificationService->createNotification('Votre compte est banni', $user);
          // Envoi d'un e-mail à l'utilisateur
          $userEmail = $user->getEmail(); // Assurez-vous que l'utilisateur a une méthode getEmail()
          $subject = 'Votre compte est banni';
          $templateTwig = 'reservation/email_banni_compte.html.twig';
          $context = [
              'user' => $user,
          ];
  
          $mailerService->send($userEmail, $subject, $templateTwig, $context);
          
          return $this->redirectToRoute('app_reservations');
       
    }
 
     $reservation = new Reservation();
     $reservation->setUser($user); 
     $reservation->setObjet($objet);
 
     $form = $this->createForm(ReservationType::class, $reservation);
     $form->handleRequest($request);
 
     if ($form->isSubmitted() && $form->isValid()) {
         $objet->setDisponibilite('réservé');
         $entityManager->persist($reservation);
         $entityManager->flush();
 
         // Notification pour utilisateur
        
         $this->notificationService->createNotification('Nouvelle réservation.', $user);
 
         // Envoi d'un mail à l'administrateur avec les détails de la réservation
         $adminEmail = 'ylarbi5@myges.fr'; // Remplace avec l'email de l'admin
         $subject = 'Nouvelle demande de réservation';
         $templateTwig = 'reservation/email_nouvel_reservation.html.twig';
         $context = [
             'reservation' => $reservation,
             'objet' => $objet,
             'user' => $user,
         ];
 
         $mailerService->send($adminEmail, $subject, $templateTwig, $context);
 
         return $this->redirectToRoute('app_reservations');
     
    }
     return $this->render('reservation/newReservation.html.twig', [
         'form' => $form->createView(),
     ]);
 }
 


     /*Gestion  des reservations */

    #[Route('/reservation/manage', name: 'app_reservation_manage')]
    public function manage(ReservationRepository $reservationRepository,Request $request,PaginatorInterface $paginator): Response
    {


        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $queryBuilder = $reservationRepository->createQueryBuilder('r')
     
        ->orderBy('r.date_debut', 'DESC');

    $pagination = $paginator->paginate(
        $queryBuilder, 
        $request->query->getInt('page', 1), 
        5 
    );

    return $this->render('reservation/reservationManage.html.twig', [
        'pagination' => $pagination,
    ]);
    }



    

 /*Approuver une reservation */

 #[Route('/reservation/approve/{id}', name: 'app_reservation_approve')]
public function approve(Reservation $reservation, EntityManagerInterface $entityManager, MailerService $mailerService): Response
{
    // Vérifie que l'utilisateur a le rôle admin
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    // Change le statut de la réservation à "Demande Approuvée"
    $reservation->setStatut('Demande Approuvée');
    $entityManager->flush();

    // Récupère l'utilisateur qui a fait la réservation
    $user = $reservation->getUser();

     // Récupère l'objet lié à la réservation
     $objet = $reservation->getObjet();

    // Vérifie que l'utilisateur a le rôle "ROLE_USER"
   if ($this->security->isGranted('ROLE_USER', $user)) {
        // Envoie une notification par email
        $this->notificationService->notifyReservationApproval($user);
        
        // Prépare les données pour l'email
        $emailContext = [
            'user' => $user,
            'reservation' => $reservation,
            'objet' => $objet,  // On ajoute l'objet au contexte de l'email
        ];

        // Appelle le service de messagerie pour envoyer l'email
        $mailerService->send(
            $user->getEmail(),  // Adresse email de l'utilisateur
            'Réservation Approuvée',  // Sujet de l'email
            'reservation/email_approval_reservation.html.twig',  // Template Twig de l'email
            $emailContext  // Contexte pour le template
        );
    }

    // Redirige vers la page de gestion des réservations
    return $this->redirectToRoute('app_reservation_manage');
}




/*Refuser une  reservation */

#[Route('/reservation/reject/{id}', name: 'app_reservation_reject')]
public function reject(Reservation $reservation, EntityManagerInterface $entityManager, MailerService $mailerService): Response
{
    // Vérifie que l'utilisateur a le rôle admin
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    // Change le statut de la réservation à "Demande Refusée"
    $reservation->setStatut('Demande Refusée');
    $entityManager->flush();

    // Récupère l'utilisateur qui a fait la réservation
    $user = $reservation->getUser();
      // Récupère l'objet lié à la réservation
      $objet = $reservation->getObjet();

    // Vérifie que l'utilisateur a le rôle "ROLE_USER"
     if ($this->security->isGranted(attributes: 'ROLE_USER', subject: $user) ) 
        {

        // Optionnel : Appelle la méthode de notification si nécessaire
        $this->notificationService->notifyReservationRejection($user);
        // Prépare les données pour l'email
        $emailContext = [
            'user' => $user,
            'reservation' => $reservation,
            'objet' => $objet,  // On ajoute l'objet au contexte de l'email
        ];

        // Appelle le service de messagerie pour envoyer l'email
        $mailerService->send(
            $user->getEmail(),  // Adresse email de l'utilisateur
            'Réservation Refusée',  // Sujet de l'email
            'reservation/email_refus_reservation.html.twig',  // Template Twig de l'email
            $emailContext  // Contexte pour le template
        );
        
    }

    return $this->redirectToRoute('app_reservation_manage');
}




 /*Afficher la liste des retours */

    #[Route('/ListRetours', name: 'app_retours')]
    public function indexRetour(Request $request,RetourRepository $retourRepository,PaginatorInterface $paginator): Response
    {
        $queryBuilder = $retourRepository->createQueryBuilder('r')
        ->where('r.user = :user')
        ->setParameter('user', $this->getUser())
        ->orderBy('r.returnDate', 'DESC');
        
        $pagination = $paginator->paginate(
                      
        $queryBuilder,$request->query->getInt('page', 1),         
        5 );
        return $this->render('Retour/MesRetours.html.twig', [
            'pagination' => $pagination,      

        ]);
    }
    


     /*Gestion des retours */

     #[Route('/retour/manage', name: 'app_retour_manage')]
public function manageRetour(RetourRepository $retourRepository, PaginatorInterface $paginator, Request $request): Response
{
    
$this->denyAccessUnlessGranted('ROLE_ADMIN');
   

$queryBuilder = $retourRepository->createQueryBuilder('r')
->orderBy('r.returnDate', 'DESC');

$pagination = $paginator->paginate(
              
$queryBuilder,$request->query->getInt('page', 1),         
5 );
return $this->render('Retour/retourManage.html.twig', [
        
       
'pagination' => $pagination,
    ]);
}

       


/* faire un retour */

#[Route('/reservation/return/{id}', name: 'app_reservation_return')]
public function returnObject(Request $request, int $id, EntityManagerInterface $entityManager, MailerService $mailerService, NotificationService $notificationService): Response
{
    // Retrieve the reservation using the ID
    $reservation = $entityManager->getRepository(Reservation::class)->find($id);

    if (!$reservation) {
        throw $this->createNotFoundException('No reservation found for id ' . $id);
    }

    // Create new Retour entity and associate it with the reservation and user
    $retour = new Retour();
    $retour->setReservation($reservation);
    $retour->setUser($this->getUser());

    // Create and handle the form
    $form = $this->createForm(RetourType::class, $retour);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Update reservation and object status
        $reservation->setStatut('Objet Retourné');
        $reservation->getObjet()->setDisponibilite('Disponible');

        // Set return date and object status from the form
        $retour->setReturnDate($form->get('returnDate')->getData());
        $retour->setObjectStatus($form->get('objectStatus')->getData());

        // Check if the return is late
        if ($retour->isLate()) {
            // Increment late returns count for the user
            $user = $this->getUser();
            $user->incrementLateReturns();
            $entityManager->persist($user);

            // Check if the user is banned due to repeated late returns
            if ($user->isBanned()) {
                $this->addFlash('danger', 'Votre compte est banni en raison de retours tardifs répétés.');
                $notificationService->createNotification('Votre compte est banni', $user);
            }
        }

        // Persist the return object
        $entityManager->persist($retour);
        $entityManager->flush();

        // Create a notification for the user
        $notificationService->createNotification('Nouveau Retour', $reservation->getUser());

        // Prepare data for the email
        $emailContext = [
            'user' => $reservation->getUser(),
            'reservation' => $reservation,
            'retour' => $retour,
        ];

        // Send an email to the administrator
        $mailerService->send(
            'admin@myges.fr',
            'Retour d\'objet emprunté',
            'reservation/email_retour_objet.html.twig',
            $emailContext
        );

        // Redirect to the reservations page
        return $this->redirectToRoute('app_reservations');
    }

    // Always return a Response, even if the form is not submitted or not valid
    return $this->render('Retour/newRetour.html.twig', [
        'form' => $form->createView(),
    ]);
}




/* supprimer un retour */
 
 #[Route('/retour/delete/{id}', name: 'app_retour_delete',methods: ['POST'] )]

 public function  deleteRetour(Request $request, Retour $retour, EntityManagerInterface $entityManager): Response

{

    $this->denyAccessUnlessGranted('ROLE_ADMIN');
    // Check if the CSRF token is valid
    if ($this->isCsrfTokenValid('delete' . $retour->getId(), $request->request->get('_token'))) {
        // Remove the Retour from the database
        $entityManager->remove($retour);
        $entityManager->flush();

        // Add a flash message to notify the user
        $this->addFlash('success', 'Le Retour est supprimé avec succès.');
    } else {
        // Add an error message in case the CSRF token is invalid
        $this->addFlash('danger', 'Échec de la suppression du retour.');
    }

    // Redirect to the list of retours
    return $this->redirectToRoute('app_retours');
}




/* Gestion des comptes bannis */

#[Route('/ManagecompteBanni', name: 'app_banni_manage')]
public function compteBanni(UserRepository $userRepository, PaginatorInterface $paginator, Request $request): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $queryBuilder = $userRepository->createQueryBuilder('u')
        ->where('u.isBanned = true') // Assurez-vous de filtrer les comptes bannis
        ->orderBy('u.firstname', 'ASC');

    $pagination = $paginator->paginate(
        $queryBuilder,
        $request->query->getInt('page', 1),
        5
    );

    return $this->render('reservation/compte_banni_manage.html.twig', [
        'pagination' => $pagination,
    ]);
}
}