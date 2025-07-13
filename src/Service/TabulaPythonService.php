<?php

namespace App\Service;

class TabulaPythonService
{

    public function generateJsonFromPdf(string $pdfPath, bool $delete = true): array
    {
        $jsonFilePath = str_replace('.pdf', '.json', $pdfPath);

        if (!file_exists($pdfPath)) {
            throw new \InvalidArgumentException('PDF file not found: ' . $pdfPath);
        }

        $command = sprintf(
            'python3 %s %s',
            escapeshellarg(__DIR__ . '/../../bin/tabula-adjudicaciones.py'),
            escapeshellarg($pdfPath),
        );

        exec(
            command: $command,
            output: $output,
            result_code: $returnVar
        );

        if ($returnVar !== 0) {
            throw new \RuntimeException('Error executing Tabula Python script: ' . implode("\n", $output));
        }

        if (!file_exists($jsonFilePath)) {
            throw new \RuntimeException('JSON file not found: ' . $jsonFilePath);
        }

        $output = $this->getFileContent($jsonFilePath);

        if ($delete) {
            $this->deleteFile($jsonFilePath);
        }

        $jsonToArray = json_decode(implode("\n", $output), true);

        $sanitized = $this->sanitizeJsonOutput($jsonToArray);
        return $this->fixDoubledLines($sanitized);
    }

    private function deleteFile(string $path): void
    {
        if (!unlink($path)) {
            throw new \RuntimeException('Error deleting JSON file: ' . $path);
        }
    }

    private function getFileContent(string $jsonFilePath): array
    {
        $output = file($jsonFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($output === false) {
            throw new \RuntimeException('Error reading JSON file: ' . $jsonFilePath);
        }
        return $output;
    }

    private function sanitizeJsonOutput(array $input): array
    {
        $sanitized = [];

        foreach ($input as $page => $content) {
            if (array_key_exists('data', $content)) {
                $sanitized[$page] = $content['data'];
            }
        }

        foreach ($sanitized as $page => $content) {
            foreach ($content as $row => $cells) {
                foreach ($cells as $column => $cell) {
                    if (array_key_exists('text', $cell)) {
                        $sanitized[$page][$row][$column] = $cell['text'];
                    }
                }
            }
        }

        return $sanitized;
    }


    private function fixDoubledLines(array $original): array
    {
        foreach ($original as $pageNumber => $pageContent) {
            $original = array_values(
                $this->mergeEmptyEndingLines($original, $pageContent, $pageNumber)
            );
        }
        return array_values($original);
    }

    private function mergeEmptyEndingLines(array $original, array $pageContent, int $pageNumber): array
    {
        foreach ($pageContent as $rowIndex => $cells) {
            if ($this->shouldMergeRow($cells)) {
                $toPageIndex = $this->findPreviousValidPage($original, $pageNumber, $rowIndex);
                $toRowIndex = $this->findPreviousValidRow($original, $pageNumber, $rowIndex, $toPageIndex);

                $original = $this->mergeCellsFromTo(
                    content: $original,
                    fromPage: $pageNumber,
                    fromRow: $rowIndex,
                    toPage: $toPageIndex,
                    toRow: $toRowIndex,
                    cells: $cells,
                );
            }
        }
        return $original;
    }

    private function shouldMergeRow(array $cells): bool
    {
        return end($cells) === "";
    }

    private function findPreviousValidRow(array $pageContent, int $pageNumber, int $currentRow, int $previousValidPage): int
    {
        if ($pageNumber == $previousValidPage) {
            $offset = 1;
            while (!array_key_exists($currentRow - $offset, $pageContent[$pageNumber])) {
                $offset++;
            }
            $previousValidRow = $currentRow - $offset;
        } else {
            $keys = array_keys($pageContent[$previousValidPage]);
            $previousValidRow = end($keys);
        }

        return $previousValidRow;
    }

    private function findPreviousValidPage(array $pageContent, int $pageNumber, int $currentRow): int
    {
        // check if the current row is the first row of the page

        $array = array_keys($pageContent[$pageNumber]);
        $firstKey = reset($array);

        $previousValidPage = $pageNumber;

        if ($currentRow == $firstKey) {
            $offset = 1;
            while (!array_key_exists($pageNumber - $offset, $pageContent)) {
                $offset++;
            }
            return $pageNumber - $offset;
        }

        return $previousValidPage;
    }

    private function mergeCellsFromTo(
        array $content,
        int $fromPage,
        int $fromRow,
        int $toPage,
        int $toRow,
        array $cells
    ): array {
        foreach ($cells as $columnIndex => $value) {
            if ($content[$fromPage][$fromRow][$columnIndex] != "") {
                $content[$toPage][$toRow][$columnIndex] .= " " . $content[$fromPage][$fromRow][$columnIndex];
            }
        }
        unset($content[$fromPage][$fromRow]);
        return $content;
    }


}
