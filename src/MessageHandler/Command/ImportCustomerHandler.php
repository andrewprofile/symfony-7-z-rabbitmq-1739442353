<?php

namespace App\MessageHandler\Command;

use App\Exception\InvalidRowException;
use App\Mapper\RowMapper;
use App\Message\Command\ImportCustomer;
use App\Service\BatchInserterService;
use App\Service\ComputeProgressService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsMessageHandler(bus: 'app.command_bus')]
class ImportCustomerHandler
{
    public function __construct(
        private BatchInserterService $batchInserterService,
        private RowMapper $rowMapper,
        private ComputeProgressService $computeProgressService,
        private CacheInterface $cache,
    ) {
    }

    public function __invoke(ImportCustomer $command): void
    {
        $fileName = $command->getFileName();
        $filePath = $command->getTargetDirectory().DIRECTORY_SEPARATOR.$fileName;

        if (!file_exists($filePath)) {
            throw new \RuntimeException('File not found');
        }

        $file = new \SplFileObject($filePath);
        $file->setFlags(
            \SplFileObject::READ_CSV
            | \SplFileObject::READ_AHEAD
            | \SplFileObject::SKIP_EMPTY
            | \SplFileObject::DROP_NEW_LINE
        );
        $file->seek(PHP_INT_MAX);
        $totalRows = $file->key();

        $payload = [];
        $currentRow = 0;
        $errorRow = 0;
        foreach ($file as $key => $row) {
            if (0 === $key) {
                continue;
            }
            try {
                ++$currentRow;
                $this->rowMapper->mapRowToPayload($row, $payload);

                $this->computeProgressService->compute($fileName, $currentRow, $totalRows);

                if (($currentRow % BatchInserterService::BATCH_SIZE) === 0) {
                    $this->batchInserterService->insert($payload);
                }
            } catch (InvalidRowException) {
                ++$errorRow;
                continue;
            } catch (ExceptionInterface) {
                continue;
            }
        }

        $this->saveErrors($fileName, $errorRow);
    }

    private function saveErrors(string $fileName, int $errorRow): void
    {
        $item = $this->cache->getItem('errors_'.$fileName);
        $item->set($errorRow);
        $item->expiresAfter(3060);
        $this->cache->save($item);
    }
}
