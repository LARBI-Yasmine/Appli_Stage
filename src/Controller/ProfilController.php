<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Form\UserEditType;
use App\Form\CompleteProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $userPasswordHasher->hashPassword($user,$form->get('password')->getData()));
            $entityManager->flush();
            return $this->redirectToRoute('app_home');
        }

        

        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'form' => $form,
            
        ]);
    }

    #[Route('/completer/profil/{id}', name: 'app_complete_profile', methods: ['GET', 'POST'])]
public function completeProfile(Request $request, User $user, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(CompleteProfileType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Enregistrez les données du profil complété
        $entityManager->flush();

        // Rediriger vers une page de succès ou connexion
        return $this->redirectToRoute('app_login'); // Remplacez par votre route
    }

    return $this->render('profil/complete_profile.html.twig', [
        'form' => $form->createView(),
    ]);
}

}