<?php

namespace Tests\Unit;

use App\Models\UserProfile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UserProfileNameTest extends TestCase
{
    public static function names(): array
    {
        return [
            'two-word last name' => ['dela peña', 'Dela Peña'],
            'saved in capitals' => ['DELA PEÑA', 'Dela Peña'],
            'accented first name' => ['MARÍA JOSÉ', 'María José'],
            'hyphenated' => ['santos-cruz', 'Santos-Cruz'],
            'apostrophe' => ["O'BRIEN", "O'Brien"],
            'extra spaces' => ['  de  los   santos ', 'De Los Santos'],
            'mixed case' => ['jUAN', 'Juan'],
            'empty' => [null, ''],
        ];
    }

    #[DataProvider('names')]
    public function test_every_word_of_a_name_part_starts_with_a_capital(?string $raw, string $expected): void
    {
        $this->assertSame($expected, UserProfile::formatNamePart($raw));
    }

    public function test_the_full_name_capitalises_first_middle_and_last_names(): void
    {
        $profile = new UserProfile([
            'first_name' => 'MARTE',
            'middle_name' => 'toleza',
            'last_name' => 'Dela peña',
        ]);

        $this->assertSame('Marte Toleza Dela Peña', $profile->full_name);
    }

    public function test_a_missing_middle_name_leaves_no_gap(): void
    {
        $profile = new UserProfile(['first_name' => 'ana', 'middle_name' => '', 'last_name' => 'DE LOS SANTOS']);

        $this->assertSame('Ana De Los Santos', $profile->full_name);
    }
}
