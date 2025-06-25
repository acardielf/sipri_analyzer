<?php

namespace App\Service;

use App\Entity\Convocatoria;
use Exception;
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


    /**
     * @throws Exception
     */
    public static function getLocalPathForConvocatoria(int $convocatoria): string
    {
        $cursoDto = Convocatoria::getCursoFromConvocatoria($convocatoria);
        return 'pdfs/' . $cursoDto->id . '/';
    }

    /**
     * @throws Exception
     */
    public static function getFilesForConvocatoria(int $convocatoria): array
    {
        return [
            'plazas' => [
                'url' => null,
                'sink' => static::getLocalPathForConvocatoria($convocatoria) . $convocatoria . '_plazas.pdf',
            ],
            'adjudicados' => [
                'url' => null,
                'sink' => static::getLocalPathForConvocatoria($convocatoria) . $convocatoria . '_adjudicados.pdf',
            ],
        ];
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

    public function getFileContent(string $pdfPath): string
    {
        $normalizedPath = Path::normalize($pdfPath);
        return $this->filesystem->readFile($normalizedPath);
    }

    public function removeFile(string $path): void
    {
        try {
            $this->filesystem->remove(Path::normalize($path));
        } catch (IOExceptionInterface $exception) {
            echo "An error occurred while removing the file at " . $exception->getPath();
        }
    }


}
