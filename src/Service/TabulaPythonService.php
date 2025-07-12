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
            $original[$pageNumber] = array_values($this->mergeEmptyEndingLines($pageContent));
        }
        return array_values($original);
    }

    private function mergeEmptyEndingLines(array $pageContent): array
    {
        foreach ($pageContent as $rowIndex => $cells) {
            if ($this->shouldMergeRow($cells, $rowIndex)) {
                $previousRowIndex = $this->findPreviousValidRow($pageContent, $rowIndex);
                $pageContent = $this->mergeCellsWithPreviousRow(
                    $pageContent,
                    $rowIndex,
                    $previousRowIndex,
                    $cells
                );
            }
        }
        return $pageContent;
    }

    private function shouldMergeRow(array $cells, int $rowIndex): bool
    {
        return end($cells) === "" && $rowIndex !== 0;
    }

    private function findPreviousValidRow(array $pageContent, int $currentRow): int
    {
        $offset = 1;
        while (!array_key_exists($currentRow - $offset, $pageContent)) {
            $offset++;
        }
        return $currentRow - $offset;
    }

    private function mergeCellsWithPreviousRow(
        array $pageContent,
        int $currentRow,
        int $previousRow,
        array $cells
    ): array {
        foreach ($cells as $columnIndex => $value) {
            $pageContent[$previousRow][$columnIndex] .= $pageContent[$currentRow][$columnIndex];
        }
        unset($pageContent[$currentRow]);
        return $pageContent;
    }


}
