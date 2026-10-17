<?php

declare(strict_types=1);

namespace Survos\TourBundle\Model;

/** A named tour, reusable by PHP menu steps and the layout widget. */
final readonly class Tour
{
    public function __construct(
        public string $name = 'page',
        public string $version = '1',
        public string $offer = 'never',
    ) {
        if ('' === trim($name) || '' === trim($version)) {
            throw new \InvalidArgumentException('A tour requires a non-empty name and version.');
        }
        if (!in_array($offer, ['never', 'once'], true)) {
            throw new \InvalidArgumentException('A tour offer must be "never" or "once".');
        }
    }
}
