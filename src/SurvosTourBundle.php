<?php

declare(strict_types=1);

namespace Survos\TourBundle;

use Survos\FieldBundle\Registry\RouteMetaRegistry;
use Survos\Kit\AbstractUxBundle;
use Survos\Kit\SurvosKitBundle;
use Survos\MenuBundle\Renderer\LinkAttributesDecoratorInterface;
use Survos\TourBundle\Menu\TourMenuDecorator;
use Survos\TourBundle\Service\CopyResolver;
use Survos\TourBundle\Twig\Components\Tour;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;

#[RequiredBundle(SurvosKitBundle::class)]
// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosTourBundle extends AbstractUxBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()->children()
            ->scalarNode('app_id')->defaultValue('app')->info('Unique application identity on this origin; dismissal is versioned per app/tour.')->end()
            ->scalarNode('translation_domain')->defaultValue('messages')->end()
            ->booleanNode('route_meta_fallback')->defaultFalse()->info('Opt in only after reviewing developer-facing RouteMeta descriptions as public copy.')->end()
        ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);
        $services = $container->services()->defaults()->autowire()->autoconfigure();
        $services->set(CopyResolver::class)->args([
            new Reference('translator'), $config['translation_domain'], $config['route_meta_fallback'],
            new Reference(RouteMetaRegistry::class, ContainerInterface::NULL_ON_INVALID_REFERENCE),
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        ]);
        $services->get(Tour::class)->arg('$appId', $config['app_id']);
        if (interface_exists(LinkAttributesDecoratorInterface::class)) {
            $services->set(TourMenuDecorator::class)->tag('survos_menu.link_attributes_decorator');
        }
    }
}
