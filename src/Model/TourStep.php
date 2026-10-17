<?php

declare(strict_types=1);

namespace Survos\TourBundle\Model;

/** Author-supplied step metadata; resolution and translation happen at rendering. */
final readonly class TourStep
{
    public const PLACEMENTS = ['top', 'right', 'bottom', 'left', 'over'];

    public Tour $tour;

    /** @param array<string, mixed> $parameters Translation parameters. */
    public function __construct(
        Tour|string $tour,
        public string $id,
        public int $order = 0,
        public ?string $title = null,
        public ?string $content = null,
        public ?string $titleKey = null,
        public ?string $contentKey = null,
        public ?string $domain = null,
        public array $parameters = [],
        public string $placement = 'bottom',
        public ?string $selector = null,
        public ?string $instance = null,
    ) {
        $this->tour = is_string($tour) ? new Tour($tour) : $tour;
        if ('' === trim($id)) {
            throw new \InvalidArgumentException('A tour step requires a stable, explicit id.');
        }
        if (!in_array($placement, self::PLACEMENTS, true)) {
            throw new \InvalidArgumentException('Invalid tour step placement.');
        }
    }

    /** @return array<string, mixed> Compatibility boundary for legacy tourOptions arrays. */
    public function toArray(): array
    {
        return ['name' => $this->tour->name] + array_diff_key(get_object_vars($this), ['tour' => true]);
    }
}
