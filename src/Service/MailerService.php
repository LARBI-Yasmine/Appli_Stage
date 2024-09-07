<?php


namespace App\Service;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;



class MailerService
{



public function __construct(private readonly MailerInterface $mailer){}


public function send(String $to,String $subject,String $templateTwig,array $context):void
    {

        $email = (new TemplatedEmail())
               ->from(new Address('noreplay@myges.fr','appli_Emprunt'))
               ->to($to)
               ->subject($subject)
               ->htmlTemplate($templateTwig)
               ->context($context);


               try{
                    $this->mailer->send($email);
                }

                catch(TransportExceptionInterface $transportException){
                    throw $transportException;
               }

    }



 

}