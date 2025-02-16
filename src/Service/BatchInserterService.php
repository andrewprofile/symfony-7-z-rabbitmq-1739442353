<?php

namespace App\Service;

use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;

final readonly class BatchInserterService
{
    public const BATCH_SIZE = 100;

    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function insert(array $payload): void
    {
        $i = 1;
        foreach ($payload as $item) {
            $customer = new Customer();
            $customer->setCustomerId($item[FileColumnEnum::Id->name]);
            $customer->setFullName($item[FileColumnEnum::FullName->name]);
            $customer->setEmail($item[FileColumnEnum::Email->name]);
            $customer->setCity($item[FileColumnEnum::City->name]);
            $this->em->persist($customer);
            if (($i % self::BATCH_SIZE) === 0) {
                $this->em->flush();
                $this->em->clear();
            }

            ++$i;
        }

        $this->em->flush();
        $this->em->clear();
    }
}