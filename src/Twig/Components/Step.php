<?php

declare(strict_types=1);

namespace Survos\TourBundle\Twig\Components;

use Survos\TourBundle\Model\TourStep;
use Survos\TourBundle\Service\CopyResolver;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Tour:Step', template: '@SurvosTour/components/Step.html.twig')]
final class Step
{
    public string $name = 'page';
    public string $id;
    public string $selector;
    public string $title = '';
    public ?string $titleKey = null;
    public ?string $content = null;
    public ?string $contentKey = null;
    public ?string $domain = null;
    public array $parameters = [];
    public int $order = 0;
    public string $placement = 'bottom';

    public function __construct(private readonly CopyResolver $copy)
    {
    }

    public function getDescriptor(): ?TourStep
    {
        return $this->copy->resolve([
            'name' => $this->name, 'id' => $this->id, 'selector' => $this->selector,
            'title' => $this->title, 'titleKey' => $this->titleKey,
            'content' => $this->content, 'contentKey' => $this->contentKey,
            'domain' => $this->domain, 'parameters' => $this->parameters,
            'order' => $this->order, 'placement' => $this->placement,
        ]);
    }
}
