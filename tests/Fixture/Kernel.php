<?php

declare(strict_types=1);

namespace Survos\TourBundle\Tests\Fixture;

use Knp\Bundle\MenuBundle\KnpMenuBundle;
use Survos\Kit\SurvosKitBundle;
use Survos\MenuBundle\SurvosMenuBundle;
use Survos\TourBundle\SurvosTourBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\UX\StimulusBundle\StimulusBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug, private readonly bool $withMenu = true, private readonly bool $withTabler = false)
    {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new \Twig\Extra\TwigExtraBundle\TwigExtraBundle();
        yield new \Symfony\UX\Icons\UXIconsBundle();
        yield new TwigComponentBundle();
        yield new StimulusBundle();
        yield new SurvosKitBundle();
        if ($this->withMenu || $this->withTabler) {
            yield new KnpMenuBundle();
        }
        if ($this->withMenu) {
            yield new SurvosMenuBundle();
        }
        if ($this->withTabler) {
            yield new \Survos\TablerBundle\SurvosTablerBundle();
        }
        yield new SurvosTourBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return dirname(__DIR__, 4).'/var/tour-fixture/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return dirname(__DIR__, 4).'/var/tour-fixture/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'fixture', 'test' => true, 'http_method_override' => false,
            'router' => ['utf8' => true],
            'asset_mapper' => ['paths' => [dirname(__DIR__, 2).'/assets' => '@survos/tour-bundle'], 'missing_import_mode' => 'warn'],
            'translator' => ['fallbacks' => ['en'], 'paths' => [__DIR__.'/translations']],
        ]);
        $container->extension('twig', ['default_path' => __DIR__.'/templates', 'strict_variables' => true,
            'paths' => [dirname(__DIR__, 3).'/tabler-bundle/templates' => 'SurvosTabler']]);
        $container->extension('twig_component', ['defaults' => [], 'anonymous_template_directory' => 'components']);
        $container->extension('stimulus', ['controller_paths' => [__DIR__.'/assets/controllers'], 'controllers_json' => __DIR__.'/controllers.json']);
        if ($this->withTabler) {
            $container->extension('survos_tabler', ['routes_enabled' => false]);
            $container->services()->set(\Survos\FieldBundle\Registry\EntityMetaRegistry::class);
            $container->services()->set(\Survos\FieldBundle\Registry\RouteMetaRegistry::class);
        }
        $container->extension('survos_tour', ['app_id' => 'tour-fixture']);
        if ($this->withMenu) {
            $container->services()->set(\Survos\TablerBundle\Service\MenuService::class)->args([[], null, null, [], null]);
            $container->services()->set(FixturePage::class)->autowire()->autoconfigure()->public();
        }
        $container->services()->alias('fixture.twig', 'twig')->public();
        $container->services()->alias('fixture.asset_mapper', 'asset_mapper')->public();
        $container->services()->alias('fixture.controller_map', 'stimulus.asset_mapper.controllers_map_generator')->public();
    }
}
