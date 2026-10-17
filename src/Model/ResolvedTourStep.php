<?php

declare(strict_types=1);

namespace Survos\TourBundle\Model;

/** Translated public copy. Text is always escaped by the renderer. */
final readonly class ResolvedTourStep implements \JsonSerializable
{
    public function __construct(
        public string $name,
        public string $id,
        public string $title,
        public string $description,
        public int $order = 0,
        public string $placement = 'bottom',
        public ?string $selector = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
