<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class JSONDecodeExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('json_decode', [$this, 'decodeJson']),
        ];
    }

    public function decodeJson(string $json)
    {
        return json_decode($json);
    }
}