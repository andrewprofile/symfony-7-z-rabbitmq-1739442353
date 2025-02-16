<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

final readonly class FileUploaderService
{
    public function __construct(
        private string $targetDirectory,
    ) {
    }

    public function upload(UploadedFile $file): string
    {
        $uuidFileName = Uuid::v7();
        $fileName = $uuidFileName.'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException) {
        }

        return $fileName;
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
}