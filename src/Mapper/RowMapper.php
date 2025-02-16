<?php

namespace App\Mapper;

use App\Exception\InvalidRowException;
use App\Service\FileColumnEnum;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;

final class RowMapper
{
    public function mapRowToPayload(array $row, array &$payload): void
    {
        $currentRow = [
            FileColumnEnum::Id->name => (int) $row[FileColumnEnum::Id->value],
            FileColumnEnum::FullName->name => $row[FileColumnEnum::FullName->value],
            FileColumnEnum::Email->name => $row[FileColumnEnum::Email->value],
            FileColumnEnum::City->name => $row[FileColumnEnum::City->value],
        ];

        $this->validate($currentRow);

        $payload[] = $currentRow;
    }

    private function validate(array $mappedData): void
    {
        $validator = Validation::createValidator();

        $constraints = new Assert\Collection([
            FileColumnEnum::Id->name => [
                new Assert\Type('integer'),
                new Assert\NotBlank(),
                new Assert\GreaterThan(0),
            ],
            FileColumnEnum::FullName->name => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Length(['min' => 3, 'max' => 255]),
            ],
            FileColumnEnum::Email->name => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
            FileColumnEnum::City->name => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
                new Assert\Length(['min' => 3, 'max' => 255]),
            ],
        ]);

        $constraints = $validator->validate($mappedData, $constraints);

        if (count($constraints) > 0) {
            throw new InvalidRowException();
        }
    }
}
