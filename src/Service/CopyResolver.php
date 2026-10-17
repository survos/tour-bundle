<?php

declare(strict_types=1);

namespace Survos\TourBundle\Service;

use Psr\Log\LoggerInterface;
use Survos\FieldBundle\Registry\RouteMetaRegistry;
use Survos\TourBundle\Model\TourStep;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CopyResolver
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly string $domain = 'messages',
        private readonly bool $routeMetaFallback = false,
        private readonly ?RouteMetaRegistry $routeMeta = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * content/title are literal public text; contentKey/titleKey are catalogue keys.
     * Legacy tourOptions content/title keys are recognized when present in the catalogue.
     *
     * @param array<string, mixed> $options
     */
    public function resolve(array $options, string $label = '', ?string $route = null, string $scope = ''): ?TourStep
    {
        $id = $options['id'] ?? null;
        if (!is_string($id) || '' === trim($id)) {
            throw new \InvalidArgumentException('A tour step requires a stable, explicit id.');
        }
        $domain = $options['domain'] ?? $this->domain;
        $parameters = $options['parameters'] ?? [];
        $title = $this->copy($options, 'title', $domain, $parameters) ?? $label;
        $description = $this->copy($options, 'content', $domain, $parameters);
        foreach (array_filter([$route ? $route.'.tour' : null, $route ? $route.'.info' : null]) as $key) {
            $description ??= $this->translation($key, 'routes', $parameters);
        }
        $description ??= $this->translation('tour.'.$id.'.content', $domain, $parameters);
        if (null === $description && $this->routeMetaFallback && null !== $route) {
            $description = $this->routeMeta?->get($route)?->description;
        }
        if (null === $description || '' === trim($description)) {
            $this->logger?->debug('Skipping tour step without public copy.', ['step' => $id, 'route' => $route]);

            return null;
        }
        $placement = $options['placement'] ?? 'bottom';
        if (!in_array($placement, ['top', 'right', 'bottom', 'left', 'over'], true)) {
            throw new \InvalidArgumentException('Invalid tour step placement.');
        }

        return new TourStep(
            $options['name'] ?? $options['tour'] ?? 'page',
            ('' === $scope ? '' : $scope.':').$id,
            strip_tags($title),
            $description,
            (int) ($options['order'] ?? 0),
            $placement,
            $options['selector'] ?? null,
        );
    }

    private function copy(array $options, string $field, string $domain, array $parameters): ?string
    {
        if (isset($options[$field.'Key'])) {
            return $this->translation($options[$field.'Key'], $domain, $parameters);
        }
        if (!isset($options[$field]) || '' === trim($options[$field])) {
            return null;
        }
        $text = $options[$field];
        // Preserve Tabler tourOptions() defaults without exposing missing keys as prose.
        if (str_starts_with($text, 'tour.') && str_ends_with($text, '.'.$field)) {
            return $this->translation($text, $domain, $parameters);
        }

        return $text;
    }

    private function translation(string $key, string $domain, array $parameters): ?string
    {
        if (!$this->translator instanceof TranslatorBagInterface || !$this->translator->getCatalogue()->has($key, $domain)) {
            return null;
        }
        $text = $this->translator->trans($key, $parameters, $domain);

        return '' === trim($text) ? null : $text;
    }
}
