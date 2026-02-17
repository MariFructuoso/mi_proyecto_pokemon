<?php

namespace App\Repository;

use App\Entity\Pokemon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pokemon>
 */
class PokemonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pokemon::class);
    }

    public function buscarPorFiltros(?string $texto, ?string $tipo, ?string $fechaDesde, ?string $fechaHasta): array
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.fechaCreacion', 'DESC'); 

        if ($texto) {
            $qb->andWhere('(p.nombre LIKE :texto OR p.descripcion LIKE :texto)')
               ->setParameter('texto', '%' . $texto . '%');
        }

        if ($tipo) {
            $qb->andWhere('p.tipo = :tipo')
               ->setParameter('tipo', $tipo);
        }

        if ($fechaDesde) {
            $qb->andWhere('p.fechaCreacion >= :desde')
               ->setParameter('desde', new \DateTime($fechaDesde));
        }

        if ($fechaHasta) {
            $qb->andWhere('p.fechaCreacion <= :hasta')
               ->setParameter('hasta', new \DateTime($fechaHasta . ' 23:59:59'));
        }

        return $qb->getQuery()->getResult();
    }
}
