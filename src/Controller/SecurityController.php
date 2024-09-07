<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/connexion', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
   
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
      
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    #[Route(path: '/déconnexion', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route('/demander-reinitialisation', name: 'app_request_password')]
    public function requestPassword(Request $request, UserProviderInterface $userProvider): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');

             // Vérifiez si l'utilisateur avec cet email existe
        try {
            $user = $userProvider->loadUserByIdentifier($email);
        } catch (\Exception $e) {
            $user = null;
        }
            if ($user) {
                // Rediriger vers la page de réinitialisation
                return $this->redirectToRoute('app_reset_password', ['email' => $email]);
            } else {
                // Afficher un message d'erreur si l'utilisateur n'existe pas
                $this->addFlash('error', 'Aucun utilisateur trouvé avec cet email.');
            }
        }

        return $this->render('security/request_password.html.twig');
    }

    #[Route('/reinitialiser-mot-de-passe/{email}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $email,EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordEncoder, UserProviderInterface $userProvider): Response
    {




         // Vérifiez si l'utilisateur avec cet email existe
         try {
            $user = $userProvider->loadUserByIdentifier($email);
        } catch (\Exception $e) {
            $user = null;
        }
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');

            // Encoder le nouveau mot de passe
            $encodedPassword = $passwordEncoder->hashPassword($user, $newPassword);
            $user->setPassword($encodedPassword);

            // Sauvegarder le nouveau mot de passe dans la base de données
         
            $entityManager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password.html.twig', ['email' => $email]);
    }
}