<?php

namespace App\Message\Command;

final readonly class ImportCustomer
{
    public function __construct(private string $targetDirectory, private string $fileName)
    {
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}