<?php

namespace App\Controller;

use App\Entity\ContenuPanier;
use App\Entity\Panier;
use App\Entity\Produit;
use App\Form\AjoutPanierType;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panier')]
#[IsGranted('ROLE_USER')]
final class PanierController extends AbstractController
{
    #[Route('', name: 'app_panier', methods: ['GET'])]
    public function index(PanierRepository $panierRepository): Response
    {
        $panier = $panierRepository->findOneBy(['utilisateur' => $this->getUser(), 'etat' => false]);

        return $this->render('panier/index.html.twig', [
            'panier' => $panier,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'app_panier_ajouter', methods: ['POST'])]
    public function ajouter(Request $request, Produit $produit, PanierRepository $panierRepository, EntityManagerInterface $entityManager): Response
    {
        $contenu = new ContenuPanier();
        $form = $this->createForm(AjoutPanierType::class, $contenu);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $this->addFlash('error', 'panier.flash.ajout_echec');

            return $this->redirectToRoute('app_produit_fiche', ['id' => $produit->getId()]);
        }

        // On récupère le panier en cours, ou on en crée un nouveau
        $panier = $panierRepository->findOneBy(['utilisateur' => $this->getUser(), 'etat' => false]);
        if (!$panier) {
            $panier = new Panier();
            $panier->setUtilisateur($this->getUser());
            $entityManager->persist($panier);
        }

        // Si le produit est déjà dans le panier, on augmente sa quantité
        $ligneExistante = null;
        foreach ($panier->getContenus() as $ligne) {
            if ($ligne->getProduit() === $produit) {
                $ligneExistante = $ligne;
            }
        }

        $quantiteTotale = $contenu->getQuantite();
        if ($ligneExistante) {
            $quantiteTotale += $ligneExistante->getQuantite();
        }

        if ($quantiteTotale > $produit->getStock()) {
            $this->addFlash('error', 'panier.flash.stock_insuffisant');

            return $this->redirectToRoute('app_produit_fiche', ['id' => $produit->getId()]);
        }

        if ($ligneExistante) {
            $ligneExistante->setQuantite($quantiteTotale);
        } else {
            $contenu->setProduit($produit);
            $panier->addContenu($contenu);
            $entityManager->persist($contenu);
        }

        $entityManager->flush();

        $this->addFlash('success', 'panier.flash.ajoute');

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/supprimer/{id}', name: 'app_panier_supprimer', methods: ['POST'])]
    public function supprimer(Request $request, ContenuPanier $contenu, EntityManagerInterface $entityManager): Response
    {
        $panier = $contenu->getPanier();

        // La ligne doit appartenir au panier en cours de l'utilisateur connecté
        if ($panier->getUtilisateur() === $this->getUser()
            && !$panier->isEtat()
            && $this->isCsrfTokenValid('supprimer'.$contenu->getId(), $request->getPayload()->getString('_token'))
        ) {
            $entityManager->remove($contenu);
            $entityManager->flush();

            $this->addFlash('success', 'panier.flash.supprime');
        } else {
            $this->addFlash('error', 'panier.flash.suppression_echec');
        }

        return $this->redirectToRoute('app_panier');
    }

    #[Route('/acheter', name: 'app_panier_acheter', methods: ['POST'])]
    public function acheter(Request $request, PanierRepository $panierRepository, EntityManagerInterface $entityManager): Response
    {
        $panier = $panierRepository->findOneBy(['utilisateur' => $this->getUser(), 'etat' => false]);

        if (!$panier || $panier->getContenus()->isEmpty() || !$this->isCsrfTokenValid('acheter', $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'panier.flash.achat_echec');

            return $this->redirectToRoute('app_panier');
        }

        // Le stock a pu changer depuis l'ajout au panier : on le vérifie à nouveau
        foreach ($panier->getContenus() as $ligne) {
            if ($ligne->getQuantite() > $ligne->getProduit()->getStock()) {
                $this->addFlash('error', 'panier.flash.stock_insuffisant_achat');

                return $this->redirectToRoute('app_panier');
            }
        }

        $panier->setEtat(true);
        $panier->setDateAchat(new \DateTimeImmutable());
        $entityManager->flush();

        $this->addFlash('success', 'panier.flash.achete');

        return $this->redirectToRoute('app_accueil');
    }
}
