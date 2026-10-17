<?php

declare(strict_types=1);

namespace Survos\TourBundle\Tests;

use PHPUnit\Framework\TestCase;
use Survos\TourBundle\Tests\Fixture\FixturePage;
use Survos\TourBundle\Tests\Fixture\Kernel;

final class IntegrationTest extends TestCase
{
    public function testConsumerKernelRendersComponentsMenuModesAndResolvesAssets(): void
    {
        require_once __DIR__.'/Fixture/bootstrap.php';
        $kernel = new Kernel('test', true);
        $kernel->boot();
        try {
            $container = $kernel->getContainer();
            $page = $container->get(FixturePage::class);
            foreach (['survos', 'knp'] as $mode) {
                foreach (['anonymous', 'user', 'admin'] as $role) {
                    $html = $page->render($mode, $role);
                    self::assertStringContainsString('data-tour-step=', $html);
                    self::assertStringContainsString('Find newspapers by catalogue', $html);
                    self::assertStringContainsString('survos--tour-bundle--tour', $html);
                    self::assertStringContainsString('scan-text', $html);
                    self::assertStringNotContainsString('Never serialized', $html);
                    self::assertStringNotContainsString('Hidden explanation', $html);
                    self::assertSame('admin' === $role, str_contains($html, 'Admin-only explanation'));
                    self::assertSame('anonymous' !== $role, str_contains($html, 'Your account settings'));
                }
            }
            $asset = $container->get('fixture.asset_mapper')->getAsset('@survos/tour-bundle/src/controllers/tour_controller.js');
            self::assertNotNull($asset);
            $map = $container->get('fixture.controller_map')->getControllersMap();
            self::assertArrayHasKey('survos--tour-bundle--tour', $map);
        } finally {
            $kernel->shutdown();
        }
    }

    public function testExplicitComponentsWorkWithoutMenusOrTabler(): void
    {
        $kernel = new Kernel('standalone', true, false);
        $kernel->boot();
        try {
            $html = $kernel->getContainer()->get('fixture.twig')->createTemplate('<twig:Tour name="help" /><twig:Tour:Step name="help" id="scan" selector="#scan" content="Scan copy" />')->render();
            self::assertStringContainsString('data-tour-step=', $html);
            self::assertStringContainsString('Scan copy', $html);
            self::assertStringContainsString('survos--tour-bundle--tour', $html);
        } finally {
            $kernel->shutdown();
        }
    }

    public function testSlotsAndExplicitInstancesKeepDistinctStableIdentities(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        try {
            $page = $kernel->getContainer()->get(FixturePage::class);
            $one = $page->renderMenu($page->menu(slot: 'PAGE_NAV'));
            $two = $page->renderMenu($page->menu(slot: 'PAGE_ACTIONS'));
            self::assertStringContainsString('PAGE_NAV:search', $one);
            self::assertStringContainsString('PAGE_ACTIONS:search', $two);
            $menu = $page->menu();
            $child = array_values($menu->getChildren())[0];
            $child->setExtra('tour', ['id' => 'search', 'instance' => 'second', 'content' => 'Second placement']);
            self::assertStringContainsString('PAGE_NAV:second:search', $page->renderMenu($menu));
        } finally {
            $kernel->shutdown();
        }
    }

    public function testLegacyTablerRegistersDecorationWithoutMenuBundle(): void
    {
        $kernel = new Kernel('legacy_tabler', true, false, true);
        $kernel->boot();
        try {
            $twig = $kernel->getContainer()->get('fixture.twig');
            self::assertNotNull($twig->getFunction('menu_link_attributes'));
            $root = (new \Knp\Menu\MenuFactory())->createItem('PAGE_NAV');
            $root->addChild('public', ['uri' => '#public', 'label' => 'Public'])
                ->setExtra('tour', ['id' => 'public', 'content' => 'Public copy']);
            $html = (new \Knp\Menu\Renderer\TwigRenderer($twig, '@SurvosTabler/menu/actions.html.twig', new \Knp\Menu\Matcher\Matcher()))->render($root);
            self::assertStringContainsString('data-tour-step=', $html);
            self::assertStringContainsString('Public copy', $html);
        } finally {
            $kernel->shutdown();
        }
    }

    public function testLayoutAcceptsATypedTourDefinition(): void
    {
        $kernel = new Kernel('standalone', true, false);
        $kernel->boot();
        try {
            $html = $kernel->getContainer()->get('fixture.twig')->createTemplate('<twig:Tour :tour="definition" />')->render([
                'definition' => new \Survos\TourBundle\Model\Tour('archive', version: '3', offer: 'once'),
            ]);
            self::assertStringContainsString('name-value="archive"', $html);
            self::assertStringContainsString('version-value="3"', $html);
            self::assertStringContainsString('offer-value="once"', $html);
        } finally {
            $kernel->shutdown();
        }
    }
}
