<?php

namespace App\Controller;

use App\Entity\Objet;
use App\Form\ObjetType;
use App\Form\ObjetEditType;
use App\Repository\ObjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/admin')]
class ObjetController extends AbstractController
{
    #[Route('/ListObjets', name: 'app_objet', methods: ['GET'])]
    public function index(Request $request, ObjetRepository $objetRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $objetRepository->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC'); // Assurez-vous que 'createdAt' est la propriété de votre entité qui stocke la date de création
    
        $pagination = $paginator->paginate(
            $queryBuilder, 
            $request->query->getInt('page', 1), 
            6 
        );
    
        return $this->render('objet/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }
    

     // Ajouter un nouveau objet
    #[Route('/nouveau/objet', name: 'app_objet_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager,SluggerInterface $slugger): Response
    {
        $objet = new Objet();
        $form = $this->createForm(ObjetType::class, $objet);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        /** @var UploadedFile $photoFile */
        $photoFile = $form->get('photo_url')->getData();

        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

            // Move the file to the directory where photos are stored
            try {
                $photoFile->move(
                    $this->getParameter('photos_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // Handle the exception if something happens during file upload
                throw new \Exception('Échec du téléchargement du fichier: ' . $e->getMessage());
            }

            // Set the new filename in the entity
            $objet->setPhotoUrl($newFilename);
        }



            $entityManager->persist($objet);
            $entityManager->flush();

            return $this->redirectToRoute('app_objet', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('objet/new.html.twig', [
            'objet' => $objet,
            'form' => $form,
        ]);
    }

    //afficher les details d'un objet
    #[Route('/objet/detail/{id}', name: 'app_objet_detail', methods: ['GET'])]
    public function detail(Objet $objet): Response
    {
        return $this->render('objet/detail.html.twig', [
            'objet' => $objet,
        ]);
    }


    
    #[Route('/edit/objet/{id}', name: 'app_objet_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Objet $objet, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ObjetType::class, $objet);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo_url')->getData(); // Nouveau fichier image
    
            if ($photoFile) {
                // Gérer l'upload de la nouvelle image (vous devrez stocker le fichier sur le serveur)
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
    
                // Déplace le fichier vers le répertoire où les images sont stockées
                try {
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );
                    // Met à jour l'URL de la photo dans l'objet
                    $objet->setPhotoUrl($newFilename);
                } catch (FileException $e) {
                    // gérer les exceptions si nécessaire
                }
            }
    
            $entityManager->flush();
    
            return $this->redirectToRoute('app_objet', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('objet/edit.html.twig', [
            'objet' => $objet,
            'form' => $form,
        ]);
    }
    



    //supprimer un objet
    #[Route('/objet/{id}', name: 'app_objet_delete', methods: ['POST'])]
    public function delete(Request $request, Objet $objet, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$objet->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($objet);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_objet', [], Response::HTTP_SEE_OTHER);
    }
}