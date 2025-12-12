<?php

namespace App\Repository;

use App\Entity\InstructorApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InstructorApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstructorApplication::class);
    }

    public function search(?string $term, ?string $status): array
    {
        $qb = $this->createQueryBuilder('ia')
            ->leftJoin('ia.applicant', 'u')->addSelect('u')
            ->orderBy('ia.createdAt', 'DESC');

        if ($term) {
            $qb->andWhere('u.email LIKE :term OR ia.reason LIKE :term')
               ->setParameter('term', '%' . $term . '%');
        }

        if ($status) {
            $qb->andWhere('ia.status = :status')
               ->setParameter('status', strtoupper($status));
        }

        return $qb->getQuery()->getResult();
    }

    public function findLatest(int $limit = 5): array
    {
        return $this->createQueryBuilder('ia')
            ->leftJoin('ia.applicant', 'u')->addSelect('u')
            ->orderBy('ia.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
