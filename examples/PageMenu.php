<?php

declare(strict_types=1);

namespace App\Menu;

use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\MenuSlot;
use Survos\TablerBundle\Menu\MenuBuilderTrait;
use Survos\TourBundle\Menu\TourMenu;
use Survos\TourBundle\Model\Tour;
use Survos\TourBundle\Model\TourStep;

final class PageMenu
{
    use MenuBuilderTrait;

    public function __construct(private readonly TourMenu $tours)
    {
    }

    #[AsMenuListener(MenuSlot::NAVBAR_MENU_END)]
    public function replay(MenuEvent $event): void
    {
        // Any existing rendered slot works; no companion Twig widget is required.
        $this->tours->add($event->menu, new Tour('page'), icon: 'tabler:help-circle');
    }

    #[AsMenuListener(MenuSlot::PAGE_NAV)]
    public function navigation(MenuEvent $event): void
    {
        // Attach metadata through add(): skipped/denied routes never mark the parent.
        $this->add($event->menu, 'app_homepage', label: 'navigation.home',
            tour: new TourStep('page', 'home', order: 10));

        $this->add($event->menu, 'app_search', label: 'navigation.search',
            tour: new TourStep('page', 'search', order: 20));

        // Route-less links use explicit translated copy; the tour never clicks them.
        $this->add($event->menu, uri: '#help', label: 'navigation.help',
            tour: new TourStep('page', 'help', order: 30,
                contentKey: 'tour.help.content', domain: 'messages'));
    }
}
