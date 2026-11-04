<?php

namespace App\Support;

/** Builds one property number per item by incrementing the sequence segment. */
class PropertyNumber
{
    /**
     * @return string[]
     */
    public static function forQuantity(string $propertyNumber, int $quantity): array
    {
        $quantity = max(1, $quantity);
        $propertyNumber = self::withoutLegacyQuantitySuffix($propertyNumber);
        $hadTrailingDash = str_ends_with($propertyNumber, '-');
        $base = rtrim($propertyNumber, '-');

        if ($base === '') {
            return array_fill(0, $quantity, '');
        }

        $parts = explode('-', $base);
        $sequenceIndex = self::sequenceIndex($parts, $hadTrailingDash);

        if ($sequenceIndex === null) {
            $numbers = [];

            for ($index = 0; $index < $quantity; $index++) {
                $numbers[] = $base . '-' . (2000 + $index);
            }

            return $numbers;
        }

        $width = strlen($parts[$sequenceIndex]);
        $sequence = (int) $parts[$sequenceIndex];
        $numbers = [];

        for ($index = 0; $index < $quantity; $index++) {
            $current = $parts;
            $current[$sequenceIndex] = str_pad((string) ($sequence + $index), $width, '0', STR_PAD_LEFT);
            $numbers[] = implode('-', $current);
        }

        return $numbers;
    }

    /** Removes an old trailing -001/-002 quantity marker from a property number. */
    public static function withoutLegacyQuantitySuffix(string $propertyNumber): string
    {
        if (str_ends_with($propertyNumber, '-')) {
            return $propertyNumber;
        }

        $parts = explode('-', rtrim($propertyNumber, '-'));
        $count = count($parts);

        if ($count < 2) {
            return $propertyNumber;
        }

        $last = $parts[$count - 1];
        $before = $parts[$count - 2];

        if (preg_match('/^0\d{2}$/', $last) && ctype_digit($before) && strlen($before) >= 3) {
            array_pop($parts);

            return implode('-', $parts);
        }

        return $propertyNumber;
    }

    /** @param array<int, string> $parts */
    private static function sequenceIndex(array $parts, bool $hadTrailingDash): ?int
    {
        $count = count($parts);

        if ($count === 0) {
            return null;
        }

        if ($hadTrailingDash) {
            return ctype_digit($parts[$count - 1]) ? $count - 1 : null;
        }

        if ($count >= 2) {
            $penultimate = $parts[$count - 2];

            if (ctype_digit($penultimate) && strlen($penultimate) >= 3) {
                return $count - 2;
            }
        }

        for ($index = $count - 1; $index >= 0; $index--) {
            if (ctype_digit($parts[$index])) {
                return $index;
            }
        }

        return null;
    }
}
