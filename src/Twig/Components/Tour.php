<?php

declare(strict_types=1);

namespace Survos\TourBundle\Twig\Components;

use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Tour', template: '@SurvosTour/components/Tour.html.twig')]
final class Tour
{
    public string $name = 'page';
    public string $offer = 'never';
    public string $version = '1';

    public function __construct(private readonly TranslatorInterface $translator, public readonly string $appId = 'app')
    {
    }

    public function getLabels(): array
    {
        $labels = [];
        foreach (['replay', 'invitation', 'start', 'later', 'back', 'next', 'done', 'close', 'progress'] as $key) {
            $labels[$key] = $this->translator->trans($key, [], 'SurvosTour');
        }

        return $labels;
    }
}
