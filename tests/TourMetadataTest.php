<?php

declare(strict_types=1);

namespace Survos\TourBundle\Tests;

use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Survos\TourBundle\Menu\TourMenuDecorator;
use Survos\TourBundle\Model\ResolvedTourStep;
use Survos\TourBundle\Model\Tour;
use Survos\TourBundle\Model\TourStep;
use Survos\TourBundle\Service\CopyResolver;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

final class TourMetadataTest extends TestCase
{
    public function testTypedStepsResolveCopyAndDecorateTheRenderedItem(): void
    {
        $translator = new Translator('en');
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['search_help' => 'Search %archive%.'], 'en', 'help');
        $copy = new CopyResolver($translator);
        $tour = new Tour('page', version: '2', offer: 'once');
        $metadata = new TourStep($tour, 'search', 20, contentKey: 'search_help', domain: 'help', parameters: ['%archive%' => 'this archive'], instance: 'archive-42');
        $step = $copy->resolve($metadata, 'Search');
        self::assertInstanceOf(ResolvedTourStep::class, $step);
        self::assertSame('Search this archive.', $step->description);
        self::assertSame(20, $step->order);
        self::assertSame('page', $step->name);
        $root = (new MenuFactory())->createItem('PAGE_NAV');
        $item = $root->addChild('random-name', ['uri' => '#search'])->setExtra('tour', $metadata);
        $attributes = (new TourMenuDecorator($copy))->decorate($item, 'Search');
        $rendered = json_decode($attributes['data-tour-step'], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('PAGE_NAV:archive-42:search', $rendered['id']);
        self::assertSame('Search this archive.', $rendered['description']);
        self::assertSame('page', (new TourStep('page', 'literal', content: 'Public copy'))->tour->name);
    }

    public function testInvalidStepMetadataFailsAtConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new TourStep('page', 'search', placement: 'sideways');
    }

    public function testInvalidTourMetadataFailsAtConstruction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Tour('page', offer: 'automatic-modal');
    }
}
