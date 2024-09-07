<?php

namespace App\Controller;

use App\Entity\Objet;
use App\Form\ObjetType;
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
    // #[Route('/ListObjets', name: 'app_objet', methods: ['GET'])]
    // public function index(ObjetRepository $objetRepository): Response
    // {
    //     return $this->render('objet/index.html.twig', [
    //         'objets' => $objetRepository->findAll(),
    //     ]);
    // }

    #[Route('/ListObjets', name: 'app_objet', methods: ['GET'])]
    public function index(Request $request, ObjetRepository $objetRepository, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $objetRepository->createQueryBuilder('o');
    
        $pagination = $paginator->paginate(
            $queryBuilder, // Query to paginate
            $request->query->getInt('page', 1), // Current page number, starts at 1
            6 // Number of results per page
        );
    
        return $this->render('objet/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }










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
            $entityManager->flush();

            return $this->redirectToRoute('app_objet', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('objet/edit.html.twig', [
            'objet' => $objet,
            'form' => $form,
        ]);
    }

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