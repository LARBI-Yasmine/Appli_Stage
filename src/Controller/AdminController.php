<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Entity\Notification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\NotificationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;




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





    // #[Route('/ListUtilisateurs', name: 'app_utilisateurs', methods: ['GET'])]
    // public function index(UserRepository $userRepository): Response
    // {
    //     return $this->render('admin/users/index.html.twig', [
    //         'users' => $userRepository->findAll(),
    //     ]);
    // }


    // #[Route('/ListUtilisateurs', name: 'app_utilisateurs', methods: ['GET'])]
    // public function index(UserRepository $userRepository, Request $request, PaginatorInterface $paginator): Response
    // {
    //     // Get the query for all users
    //     $queryBuilder = $userRepository->createQueryBuilder('u');
    
    //     // Paginate the results of the query
    //     $pagination = $paginator->paginate(
    //         $queryBuilder, /* query NOT result */
    //         $request->query->getInt('page', 1), /* page number */
    //         5 /* limit per page */
    //     );
    
    //     return $this->render('admin/users/index.html.twig', [
    //         'pagination' => $pagination,
    //     ]);

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

    // Paginate the results of the query
    $pagination = $paginator->paginate(
        $queryBuilder, /* query NOT result */
        $request->query->getInt('page', 1), /* page number */
        5 /* limit per page */
    );

    return $this->render('admin/users/index.html.twig', [
        'pagination' => $pagination,
        'searchTerm' => $searchTerm, // Pass the search term to the template
    ]);
}





    #[Route('/utilisateur/detail/{id}', name: 'app_utilisateur_detail', methods: ['GET'])]
    public function detail(User $user): Response
    {
        return $this->render('admin/users/detail.html.twig', [
            'user' => $user,
        ]);
    }




    #[Route('/nouveau/utilisateur', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager,SluggerInterface $slugger): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('password')->getData()
                    )
                );

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_utilisateurs', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/users/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }


    #[Route('/edit/{id}', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
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

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_utilisateurs', [], Response::HTTP_SEE_OTHER);
    }



   /* #[Route('/admin/unban/{id}', name: 'app_admin_unban')]
    public function unban(User $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user->unbanUser();
        $user->resetLateReturnsCount();
        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur débanni avec succès.');
        return $this->redirectToRoute('app_reservation_manage');
    }*/
}