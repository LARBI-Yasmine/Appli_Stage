<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Service\MailerService;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register', methods:['GET', 'POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager,
    MailerService $mailerService,TokenGeneratorInterface $tokenGeneratorInterface): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            //token
            $tokenRegistration = $tokenGeneratorInterface->generateToken();

          
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

             //user token

             $user->setTokenRegistration($tokenRegistration) ;




           // $user->setCreatedAt(new \DateTime());
            $entityManager->persist($user);
            $entityManager->flush();


            //mailer send 
            $mailerService->send(
                $user->getEmail(),
                'Confirmation du compte utilisateur','registration_confirmation.html.twig',
                [
                    'user' => $user,
                    'token'=>$tokenRegistration,
                    'lifeTimeToken'=> $user->getTokenRegistrationLifeTime()->format('Y-m-d H:i:s')
                ]
            );

       
            $this->addFlash('success','Votre compte a bien été créé,veuillez vérifier vos e-mails pour l\'activer');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'form' =>$form->createView(),
        ]);
    }


    #[Route('/verify/{token}/{id<\d+>}', name: 'account_verify', methods:['GET'])]
    public function verify(String $token, User $user, EntityManagerInterface $em):Response{

      if($user->getTokenRegistration() !== $token){

        throw new AccessDeniedException();
      }

      if($user->getTokenRegistration() === null){

        throw new AccessDeniedException();
      }

      if(new DateTime('now') > $user->getTokenRegistrationLifeTime() ){

        throw new AccessDeniedException();
      }


      $user->setVerified(true);
      $user->setTokenRegistration(null);
      $em->flush();

      $this->addFlash('success','votre compte a bien été activé, Vous pouvez maintenant vous connecter');
      return $this->redirectToRoute('app_login');

    }







}