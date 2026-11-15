<?php

declare(strict_types=1);

namespace App\Game;

/**
 * Game invite codes: 8 characters from an alphabet without look-alikes (no 0/O, 1/I), case-insensitive.
 * Stored normalized ("K7QM3XPA"), displayed in two groups ("K7QM-3XPA"); players may type either form.
 */
final class InviteCode
{
    public const string ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    public const int LENGTH = 8;

    public static function generate(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; ++$i) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }

    /**
     * @return string|null the stored form, or null when the input cannot be a code (wrong length or characters)
     */
    public static function normalize(string $input): ?string
    {
        // Separators players may type or paste: dashes, spaces.
        $code = strtoupper((string) preg_replace('/[\s-]+/', '', $input));

        if (self::LENGTH !== strlen($code) || self::LENGTH !== strspn($code, self::ALPHABET)) {
            return null;
        }

        return $code;
    }

    public static function format(string $code): string
    {
        return substr($code, 0, 4).'-'.substr($code, 4);
    }
}
