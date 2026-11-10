<?php

namespace App\Controller;

use App\Repository\PanierRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/super-admin')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class SuperAdminController extends AbstractController
{
    #[Route('/paniers', name: 'app_super_admin_paniers', methods: ['GET'])]
    public function paniers(PanierRepository $panierRepository): Response
    {
        return $this->render('super_admin/paniers.html.twig', [
            'paniers' => $panierRepository->findBy(['etat' => false]),
        ]);
    }

    #[Route('/inscrits', name: 'app_super_admin_inscrits', methods: ['GET'])]
    public function inscrits(UtilisateurRepository $utilisateurRepository): Response
    {
        return $this->render('super_admin/inscrits.html.twig', [
            'utilisateurs' => $utilisateurRepository->findInscritsAujourdhui(),
        ]);
    }
}
