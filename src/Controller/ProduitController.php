<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\AjoutPanierType;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

final class ProduitController extends AbstractController
{
    public function __construct(
        private SluggerInterface $slugger,
        private Filesystem $filesystem,
        #[Autowire('%upload_directory%')]
        private string $dossierUploads,
    ) {
    }

    #[Route('/', name: 'app_accueil', methods: ['GET'])]
    public function accueil(ProduitRepository $produitRepository): Response
    {
        return $this->render('produit/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/produit/ajouter', name: 'app_produit_ajouter', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->uploaderPhoto($form, $produit);
            $entityManager->persist($produit);
            $entityManager->flush();

            $this->addFlash('success', 'produit.flash.ajoute');

            return $this->redirectToRoute('app_accueil', [], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted()) {
            $this->addFlash('error', 'produit.flash.ajout_echec');
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/produit/{id}', name: 'app_produit_fiche', methods: ['GET'])]
    public function fiche(Produit $produit): Response
    {
        $formPanier = $this->createForm(AjoutPanierType::class, null, [
            'action' => $this->generateUrl('app_panier_ajouter', ['id' => $produit->getId()]),
        ]);

        return $this->render('produit/show.html.twig', [
            'produit' => $produit,
            'formPanier' => $formPanier,
        ]);
    }

    #[Route('/produit/{id}/modifier', name: 'app_produit_modifier', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function modifier(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->uploaderPhoto($form, $produit);
            $entityManager->flush();

            $this->addFlash('success', 'produit.flash.modifie');

            return $this->redirectToRoute('app_produit_fiche', ['id' => $produit->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($form->isSubmitted()) {
            $this->addFlash('error', 'produit.flash.modification_echec');
        }

        return $this->render('produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/produit/{id}/supprimer', name: 'app_produit_supprimer', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function supprimer(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->getPayload()->getString('_token'))) {
            $this->supprimerPhoto($produit);
            $entityManager->remove($produit);
            $entityManager->flush();

            $this->addFlash('success', 'produit.flash.supprime');
        } else {
            $this->addFlash('error', 'produit.flash.suppression_echec');
        }

        return $this->redirectToRoute('app_accueil', [], Response::HTTP_SEE_OTHER);
    }

    // Déplace la photo envoyée dans public/uploads et enregistre son nom dans le produit
    private function uploaderPhoto(FormInterface $form, Produit $produit): void
    {
        $photo = $form->get('photo')->getData();

        if ($photo) {
            $nomOriginal = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
            $nouveauNom = $this->slugger->slug($nomOriginal).'-'.uniqid().'.'.$photo->guessExtension();

            $photo->move($this->dossierUploads, $nouveauNom);
            $this->supprimerPhoto($produit);
            $produit->setPhoto($nouveauNom);
        }
    }

    // Supprime du disque la photo actuelle du produit
    private function supprimerPhoto(Produit $produit): void
    {
        if ($produit->getPhoto()) {
            $this->filesystem->remove($this->dossierUploads.'/'.$produit->getPhoto());
        }
    }
}
