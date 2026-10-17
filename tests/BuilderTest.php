<?php

declare(strict_types=1);

namespace Survos\TourBundle\Tests;

use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Survos\TablerBundle\Menu\MenuBuilderTrait;
use Survos\TablerBundle\Service\MenuService;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class BuilderTest extends TestCase
{
    public function testSkippedAndDeniedItemsCannotMarkTheParent(): void
    {
        $builder = new class {
            use MenuBuilderTrait { add as public; }
        };
        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturn(false);
        $builder->setTablerMenuService(new MenuService(['admin' => ['ROLE_ADMIN']], null, $checker, [], null));
        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willThrowException(new RouteNotFoundException());
        $builder->setTablerMenuHelpers($router);
        $root = (new MenuFactory())->createItem('PAGE_NAV');
        $tour = ['id' => 'private', 'content' => 'Private explanation'];
        self::assertSame($root, $builder->add($root, 'admin', tour: $tour));
        self::assertSame($root, $builder->add($root, 'missing', tour: $tour));
        self::assertSame($root, $builder->add($root, uri: '#hidden', if: false, tour: $tour));
        self::assertCount(0, $root);
        self::assertNull($root->getExtra('tour'));
        $child = $builder->add($root, 'public', checkRouteExists: false, tour: ['id' => 'public']);
        self::assertSame('public', $child->getExtra('tourRoute'));
        self::assertSame(['id' => 'public'], $child->getExtra('tour'));
    }
}
