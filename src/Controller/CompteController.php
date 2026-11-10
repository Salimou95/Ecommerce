<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Form\ProfilType;
use App\Repository\PanierRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/compte')]
#[IsGranted('ROLE_USER')]
final class CompteController extends AbstractController
{
    #[Route('', name: 'app_compte', methods: ['GET', 'POST'])]
    public function index(Request $request, PanierRepository $panierRepository, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfilType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'compte.flash.modifie');

            return $this->redirectToRoute('app_compte');
        }

        if ($form->isSubmitted()) {
            // On annule les valeurs refusées, sinon l'utilisateur serait déconnecté
            $entityManager->refresh($this->getUser());

            $this->addFlash('error', 'compte.flash.modification_echec');
        }

        $commandes = $panierRepository->findBy(
            ['utilisateur' => $this->getUser(), 'etat' => true],
            ['dateAchat' => 'DESC'],
        );

        return $this->render('compte/index.html.twig', [
            'form' => $form,
            'commandes' => $commandes,
        ]);
    }

    #[Route('/commande/{id}', name: 'app_compte_commande', methods: ['GET'])]
    public function commande(Panier $panier): Response
    {
        if ($panier->getUtilisateur() !== $this->getUser() || !$panier->isEtat()) {
            throw $this->createNotFoundException();
        }

        return $this->render('compte/commande.html.twig', [
            'panier' => $panier,
        ]);
    }
}
