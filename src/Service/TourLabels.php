<?php

declare(strict_types=1);

namespace Survos\TourBundle\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

final class TourLabels
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /** @return array<string, string> */
    public function all(): array
    {
        $labels = [];
        foreach (['replay', 'invitation', 'start', 'later', 'back', 'next', 'done', 'close', 'progress'] as $key) {
            $labels[$key] = $this->translator->trans($key, [], 'SurvosTour');
        }

        return $labels;
    }
}
