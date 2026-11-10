<?php

namespace App\Repository;

use App\Entity\ContenuPanier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Accès en base aux entités ContenuPanier.
 *
 * @extends ServiceEntityRepository<ContenuPanier>
 */
class ContenuPanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContenuPanier::class);
    }
}
