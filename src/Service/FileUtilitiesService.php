<?php

namespace App\Service;

use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class FileUtilitiesService
{
    private Filesystem $filesystem;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
    }

    public function createDirectoryIfNotExists(string $path): void
    {
        try {
            $this->filesystem->mkdir(
                Path::normalize($path),
            );
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while creating your directory at " . $exception->getPath();
        }
    }

    public function fileExists(string $path): bool
    {
        return $this->filesystem->exists(Path::normalize($path));
    }

    public function saveContentToFile(string $path, string $content): void
    {
        try {
            $this->filesystem->dumpFile(Path::normalize($path), $content);
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while saving the file at " . $exception->getPath();
        }
    }

    public function getFileContent(string $pdfPath)
    {
        $normalizedPath = Path::normalize($pdfPath);
        return $this->filesystem->readFile($normalizedPath);
    }

}
