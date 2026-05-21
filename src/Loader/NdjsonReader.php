<?php

declare(strict_types=1);

namespace ClassificationOccupation\Loader;

class NdjsonReader
{
    /**
     * @return iterable<array<string, mixed>>
     */
    public function read(string $filePath): iterable
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException(sprintf('File not found: %s', $filePath));
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException(sprintf('Could not open file: %s', $filePath));
        }

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                $record = json_decode($line, true);
                if (is_array($record)) {
                    yield $record;
                }
            }
        } finally {
            fclose($handle);
        }
    }
}
