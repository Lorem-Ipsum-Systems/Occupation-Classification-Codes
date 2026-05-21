<?php

declare(strict_types=1);

namespace ClassificationOccupation\Model;

/**
 * @immutable
 */
readonly class ClassificationOccupationSearchTerm
{
    public string $normalizedTerm;

    /**
     * @param array<string, mixed> $sourceMetadata
     */
    public function __construct(
        public ClassificationOccupationSystem $system,
        public string $version,
        public string $jurisdiction,
        public string $code,
        public string $term,
        public ?string $context = null,
        public bool $isIllustrativeExample = false,
        public array $sourceMetadata = []
    ) {
        $this->normalizedTerm = self::normalize($this->term);
    }

    public static function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[[:punct:]]/u', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
