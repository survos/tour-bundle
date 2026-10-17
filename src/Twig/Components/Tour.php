<?php

declare(strict_types=1);

namespace Survos\TourBundle\Twig\Components;

use Survos\TourBundle\Model\Tour as TourDefinition;
use Survos\TourBundle\Service\TourLabels;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Tour', template: '@SurvosTour/components/Tour.html.twig')]
final class Tour
{
    public string $name = 'page';
    public string $offer = 'never';
    public string $version = '1';

    public function __construct(private readonly TourLabels $labels, public readonly string $appId = 'app')
    {
    }

    public function mount(?TourDefinition $tour = null): void
    {
        if (null !== $tour) {
            $this->name = $tour->name;
            $this->version = $tour->version;
            $this->offer = $tour->offer;
        }
    }

    public function getLabels(): array
    {
        return $this->labels->all();
    }
}
