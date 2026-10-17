<?php

declare(strict_types=1);

namespace Survos\TourBundle\Menu;

use Knp\Menu\ItemInterface;
use Survos\Kit\Twig\SurvosStimulusExtension;
use Survos\TourBundle\Model\Tour;
use Survos\TourBundle\Service\TourLabels;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

/** Place a self-contained replay control in any existing rendered menu slot. */
final class TourMenu
{
    public function __construct(
        private readonly SurvosStimulusExtension $controllers,
        private readonly StimulusHelper $stimulus,
        private readonly TourLabels $labels,
        private readonly string $appId = 'app',
    ) {
    }

    public function add(ItemInterface $menu, Tour $tour = new Tour(), ?string $label = null, ?string $icon = null): ItemInterface
    {
        $labels = $this->labels->all();
        $controller = $this->controllers->stimulusController('tour-bundle', 'tour');
        $attributes = $this->stimulus->createStimulusAttributes();
        $attributes->addController($controller, [
            'name' => $tour->name, 'version' => $tour->version, 'offer' => $tour->offer,
            'app' => $this->appId, 'labels' => $labels, 'menu' => true,
        ]);
        $attributes->addAction($controller, 'start', 'click');
        $attributes->addAction($controller, 'start', 'keydown.space');
        $attributes->addTarget($controller, 'replay');

        return $menu->addChild('tour-replay-'.$tour->name, [
            'uri' => '#tour-'.rawurlencode($tour->name),
            'label' => $label ?? $labels['replay'],
            'attributes' => ['data-tour-control' => $tour->name, 'hidden' => true],
            'linkAttributes' => $attributes->toArray() + ['role' => 'button', 'hidden' => true, 'data-tour-replay' => $tour->name],
            'extras' => ['translation_domain' => false, 'icon' => $icon],
        ]);
    }
}
