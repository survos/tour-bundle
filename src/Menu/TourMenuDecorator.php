<?php

declare(strict_types=1);

namespace Survos\TourBundle\Menu;

use Knp\Menu\ItemInterface;
use Survos\MenuBundle\Renderer\LinkAttributesDecoratorInterface;
use Survos\TourBundle\Model\TourStep;
use Survos\TourBundle\Service\CopyResolver;

final class TourMenuDecorator implements LinkAttributesDecoratorInterface
{
    public function __construct(private readonly CopyResolver $copy)
    {
    }

    public function decorate(ItemInterface $item, string $label, array $options = []): array
    {
        $metadata = $item->getExtra('tour');
        $metadata = $metadata instanceof TourStep ? $metadata->toArray() : $metadata;
        if (!is_array($metadata) || !$item->isDisplayed()) {
            return [];
        }
        $scope = (string) $item->getRoot()->getName();
        if (isset($metadata['instance'])) {
            $scope .= ':'.$metadata['instance'];
        }
        $step = $this->copy->resolve($metadata, $label, $item->getExtra('tourRoute'), $scope);

        return null === $step ? [] : ['data-tour-step' => json_encode($step, JSON_THROW_ON_ERROR)];
    }
}
