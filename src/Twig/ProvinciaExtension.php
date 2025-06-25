<?php

namespace App\Twig;

use App\Enum\ProvinciaEnum;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ProvinciaExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('provincia_label', [$this, 'getProvinciaLabel']),
        ];
    }

    public function getProvinciaLabel(int $numero): ?string
    {
        $provincia = ProvinciaEnum::fromCode($numero);
        return $provincia->getWithCode();
    }
}
