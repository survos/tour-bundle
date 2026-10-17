<?php

declare(strict_types=1);

namespace Survos\TourBundle\Tests\Fixture;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;
use Knp\Menu\Renderer\TwigRenderer;
use Survos\MenuBundle\Attribute\AsMenuListener;
use Survos\MenuBundle\Event\MenuEvent;
use Survos\MenuBundle\Renderer\ThemeRenderer;
use Survos\MenuBundle\Service\MenuDispatcher;
use Survos\TablerBundle\Menu\MenuBuilderTrait;
use Survos\TourBundle\Menu\TourMenu;
use Survos\TourBundle\Model\Tour;
use Survos\TourBundle\Model\TourStep;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/** A consuming PHP listener plus one layout component; no app tour controller. */
final class FixturePage
{
    use MenuBuilderTrait;

    public function __construct(
        private readonly FactoryInterface $factory,
        private readonly ThemeRenderer $renderer,
        private readonly MenuDispatcher $dispatcher,
        private readonly Environment $twig,
        private readonly MatcherInterface $matcher,
        private readonly TranslatorInterface $translator,
        private readonly TourMenu $tours,
        RouterInterface $router,
    ) {
        $this->setTablerMenuHelpers($router);
    }

    public function menu(string $role = 'anonymous', string $slot = 'PAGE_NAV'): ItemInterface
    {
        return $this->dispatcher->dispatch($slot, ['role' => $role]);
    }

    #[AsMenuListener('PAGE_NAV')]
    #[AsMenuListener('PAGE_ACTIONS')]
    public function populate(MenuEvent $event): void
    {
        $menu = $event->menu;
        $role = $event->getOption('role', 'anonymous');
        $this->add($menu, 'fixture_search', label: 'Newspapers', tour: new TourStep('page', 'search', 10));
        $this->add($menu, uri: '#text', label: 'Search text', tour: ['id' => 'text', 'order' => 20, 'content' => 'Search article text. Generated groups may be partial and unreviewed.']);
        $more = $this->addSubmenu($menu, 'More', tour: ['id' => 'more', 'content' => 'More ways to explore this paper.', 'order' => 30]);
        $this->add($more, 'fixture_dates', label: 'Issue dates', tour: ['id' => 'dates', 'order' => 40]);
        $this->add($menu, uri: '#account', label: 'Account', if: 'anonymous' !== $role, tour: ['id' => 'account', 'content' => 'Your account settings.', 'order' => 50]);
        $this->add($menu, uri: '#admin', label: 'Admin', if: 'admin' === $role, tour: ['id' => 'admin', 'content' => 'Admin-only explanation.', 'order' => 60]);
        $this->add($menu, 'missing_route', label: 'Missing', tour: ['id' => 'missing', 'content' => 'Never serialized']);
        $this->add($menu, uri: '#hidden', label: 'Hidden', tour: ['id' => 'hidden', 'content' => 'Hidden explanation'])->setDisplay(false);
        $this->add($menu, uri: '#no-copy', label: 'No copy', tour: ['id' => 'no-copy']);
    }

    public function render(string $mode = 'survos', string $role = 'anonymous', bool $empty = false, bool $menuControl = false): string
    {
        $menu = $empty ? $this->factory->createItem('PAGE_NAV') : $this->menu($role);
        if ($menuControl) {
            $this->tours->add($menu, new Tour('page', offer: 'once'));
        }
        $html = $this->renderMenu($menu, $mode);

        return $this->twig->render('page.html.twig', ['menuHtml' => $html, 'empty' => $empty, 'menuControl' => $menuControl]);
    }

    public function renderMenu(ItemInterface $menu, string $mode = 'survos', string $theme = 'navbar'): string
    {
        if ('knp' === $mode) {
            return (new TwigRenderer($this->twig, '@SurvosTabler/menu/'.$theme.'.html.twig', $this->matcher))->render($menu, ['allow_safe_labels' => true]);
        }

        return $this->renderer->render($menu, ['themes' => ['@SurvosTabler/menu_theme/'.$theme.'.html.twig', '@SurvosTabler/menu_theme/common.html.twig']]);
    }
}
