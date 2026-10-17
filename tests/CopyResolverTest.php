<?php

declare(strict_types=1);

namespace Survos\TourBundle\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Survos\FieldBundle\Model\RouteMetaDescriptor;
use Survos\FieldBundle\Registry\RouteMetaRegistry;
use Survos\TourBundle\Service\CopyResolver;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

final class CopyResolverTest extends TestCase
{
    public function testPrecedenceLocaleFallbackParametersAndMissingCopy(): void
    {
        $translator = new Translator('fr_CA');
        $translator->setFallbackLocales(['en']);
        $translator->addLoader('array', new ArrayLoader());
        $translator->addResource('array', ['paper.tour' => 'Find %what%', 'paper.info' => 'Info'], 'en', 'routes');
        $translator->addResource('array', ['custom' => 'Explicit', 'tour.search.content' => 'Legacy'], 'en');
        $resolver = new CopyResolver($translator);
        $options = ['id' => 'search', 'parameters' => ['%what%' => 'newspapers']];
        self::assertSame('Find newspapers', $resolver->resolve($options, 'Papers', 'paper')->description);
        self::assertSame('Papers', $resolver->resolve($options, 'Papers', 'paper')->title);
        self::assertSame('Explicit', $resolver->resolve($options + ['contentKey' => 'custom'], '', 'paper')->description);
        self::assertSame('Literal', $resolver->resolve($options + ['content' => 'Literal'], '', 'paper')->description);
        self::assertSame('Find newspapers', $resolver->resolve($options + ['contentKey' => 'missing'], '', 'paper')->description);
        self::assertSame('Legacy', $resolver->resolve($options + ['content' => 'tour.search.content'])->description);
        self::assertNull($resolver->resolve(['id' => 'unknown', 'content' => 'tour.unknown.content']));
        self::assertNull($resolver->resolve(['id' => 'unknown', 'contentKey' => 'missing']));
        $translator->addResource('array', ['route.info' => 'Public info'], 'en', 'routes');
        self::assertSame('Public info', $resolver->resolve(['id' => 'info'], '', 'route')->description);
        self::assertSame('NAVBAR:search', $resolver->resolve($options, '', 'paper', 'NAVBAR')->id);
    }

    public function testRouteMetaRequiresExplicitOptInAndMissingCopyHasDiagnostic(): void
    {
        $registry = new RouteMetaRegistry([new RouteMetaDescriptor('paper', '/', [], 'controller', 'Developer description')]);
        $logger = new class extends AbstractLogger {
            public array $messages = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $context;
            }
        };
        $translator = new Translator('en');
        self::assertNull((new CopyResolver($translator, routeMeta: $registry, logger: $logger))->resolve(['id' => 'x'], '', 'paper'));
        self::assertSame('x', $logger->messages[0]['step']);
        self::assertSame('Developer description', (new CopyResolver($translator, routeMetaFallback: true, routeMeta: $registry))->resolve(['id' => 'x'], '', 'paper')->description);
    }
}
