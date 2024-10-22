<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UserEditType;
use App\Form\UserAdminType;
use App\Entity\Notification;
use App\Service\MailerService;
use App\Form\AdminEditUserType;
use App\Form\CompleteProfileType;
use Symfony\Component\Mime\Email;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;





#[Route('/admin')]
class AdminController extends AbstractController
{


   //Notifications
   #[Route('/admin/notifications', name: 'app_admin_notifications', methods: ['GET'])]
   public function notifications(): Response
   {
       return $this->render('admin/notifications.html.twig');
   }

   //mettre à jour l'état de la notification
   #[Route('/notification/read/{id}', name: 'app_notification_read', methods: ['GET'])]
public function markAsRead(Notification $notification, EntityManagerInterface $entityManager): Response
{
    if (!$notification->isEstLu()) {
        $notification->setEstLu(true);
        $entityManager->flush();
    }

  
    return $this->redirectToRoute('app_reservation_manage');
}



// afficher la liste des utilisateurs

#[Route('/ListUtilisateurs', name: 'app_utilisateurs', methods: ['GET'])]
public function index(UserRepository $userRepository, Request $request, PaginatorInterface $paginator): Response
{
    $searchTerm = $request->query->get('search', ''); // Get the search term from the request
    
    // Create a query builder to find users matching the search term
    $queryBuilder = $userRepository->createQueryBuilder('u');
    if ($searchTerm) {
        $queryBuilder->where('u.firstname LIKE :searchTerm OR u.lastname LIKE :searchTerm OR u.email LIKE :searchTerm')
                     ->setParameter('searchTerm', '%' . $searchTerm . '%');
    }

    // Order by creation date (assuming 'createdAt' is the date field in your User entity)
    $queryBuilder->orderBy('u.createdAt', 'DESC');

    // Paginate the results of the query
    $pagination = $paginator->paginate(
        $queryBuilder, 
        $request->query->getInt('page', 1), 5  );

    return $this->render('admin/users/index.html.twig', [
        'pagination' => $pagination,
        'searchTerm' => $searchTerm, // Pass the search term to the template
    ]);
}





    //afficher les details d'un utilisateur

    #[Route('/utilisateur/detail/{id}', name: 'app_utilisateur_detail', methods: ['GET'])]
    public function detail(User $user): Response
    {
        return $this->render('admin/users/detail.html.twig', [
            'user' => $user,
        ]);
    }


    /* créer un nouveau utilisateur */

    #[Route('/nouveau/utilisateur', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserAdminType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Générer un mot de passe temporaire
            $temporaryPassword = bin2hex(random_bytes(8)); // Générer un mot de passe temporaire
            $user->setPassword($userPasswordHasher->hashPassword($user, $temporaryPassword));
            
            // Active automatiquement le compte de l'utilisateur
            $user->setVerified(true);  

            // Sauvegarder l'utilisateur
            $entityManager->persist($user);
            $entityManager->flush(); // Nécessaire pour obtenir l'ID de l'utilisateur
    
            // Envoyer l'e-mail pour compléter le profil
            $url = $this->generateUrl('app_complete_profile', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $email = (new TemplatedEmail())
                ->from('noreply@myges.fr')
                ->to($user->getEmail())
                ->subject('Veuillez compléter votre profil')
                ->htmlTemplate('admin/users/mail_complete_profile.html.twig')
                ->context([
                    'user' => $user,
                    'url' => $url, // URL pour compléter le profil
                    'temporaryPassword' => $temporaryPassword, // Mot de passe temporaire
                ]);
    
            $mailer->send($email);
    
            return $this->redirectToRoute('app_utilisateurs', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('admin/users/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
    
 /* editer un utilisateur */

    #[Route('/edit/{id}', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AdminEditUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateurs', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }



    /* supprimer un utilisateur */

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_utilisateurs');
    }


/* débannir un compte */

#[Route(path: 'unban/{id}', name: 'app_admin_unban')]
public function unban(User $user, EntityManagerInterface $entityManager, MailerService $mailerService, NotificationService $notificationService): Response
{
    // Vérifie si l'utilisateur connecté a le rôle admin
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    // Déban l'utilisateur et réinitialise son nombre de retours en retard
    $user->unbanUser();
    $user->resetLateReturnsCount();

    $entityManager->flush();
    $notificationService->createNotification('Votre compte a été débanni.', $user);

    $userEmail = $user->getEmail(); // Assurez-vous que l'utilisateur a une méthode getEmail()
    $subject = 'Votre compte a été débanni';
    $templateTwig = 'reservation/email_debanni_compte.html.twig'; // Le template pour le mail
    $context = [
        'user' => $user,
    ];

   
    $mailerService->send($userEmail, $subject, $templateTwig, $context);

    $this->addFlash('success', 'Utilisateur débanni avec succès.');

    return $this->redirectToRoute('app_banni_manage');
}

}