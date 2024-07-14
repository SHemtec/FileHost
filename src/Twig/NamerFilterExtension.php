<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class NamerFilterExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('remove_namer_suffix', [$this, 'removeNamerSuffix']),
        ];
    }

    public function removeNamerSuffix($filename)
    {
        // Assuming the namer adds a suffix of the form "uniqueid_"
        return preg_replace('/^[a-zA-Z0-9]+_/', '', $filename);
    }
}