<?php

namespace App\Services\Api;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Temporary stand-in for the AMIS enrolled students feed until the real AMIS API
 * is connected. Students are generated from fixed lists so every page is stable
 * between requests without needing a database table.
 */
class AmisService
{
    private const TERM_ID = 1261;

    private const TOTAL_STUDENTS = 250;

    // The newest entrants of the 1261 term are in their first year.
    private const FIRST_YEAR_ENTRY = 2026;

    private const PROGRAMS = [
        ['BSCS', 'Bachelor of Science in Computer Science', 'BS Computer Science'],
        ['BSMATH', 'Bachelor of Science in Mathematics', 'BS Mathematics'],
        ['BSBIO', 'Bachelor of Science in Biology', 'BS Biology'],
        ['BSSTAT', 'Bachelor of Science in Statistics', 'BS Statistics'],
        ['BSMGT', 'Bachelor of Science in Management', 'BS Management'],
        ['BAPOLSCI', 'Bachelor of Arts in Political Science', 'BA Political Science'],
        ['BAPSYCH', 'Bachelor of Arts in Psychology', 'BA Psychology'],
        ['BACOMM', 'Bachelor of Arts in Communication', 'BA Communication'],
        ['BFAPD', 'Bachelor of Fine Arts in Product Design', 'BFA Product Design'],
    ];

    private const YEAR_LEVELS = [1 => 'First Year', 2 => 'Second Year', 3 => 'Third Year', 4 => 'Fourth Year'];

    // Each first name has distinct initials so generated emails stay unique.
    private const FIRST_NAMES = [
        'HYACINTH MARI', 'JUAN CARLOS', 'MARIA ISABEL', 'JOHN PAUL', 'ANGELICA ROSE',
        'KENNETH', 'PATRICIA ANNE', 'CHRISTIAN JAY', 'SOPHIA', 'MIGUEL ANGELO',
        'LEA', 'RAFAEL', 'BEATRICE', 'GABRIEL LUIS', 'DANICA',
        'NATHANIEL', 'ERICA JOY', 'VINCENT', 'TRISHA MAE', 'ANDREI',
        'FRANCES', 'JASMINE', 'CARLO', 'ISABELLA', 'MARK',
    ];

    private const LAST_NAMES = [
        'DITAN', 'SANTOS', 'REYES', 'CRUZ', 'BAUTISTA',
        'OCAMPO', 'GARCIA', 'MENDOZA', 'TORRES', 'VILLANUEVA',
        'RAMOS', 'AQUINO', 'CASTILLO', 'FLORES', 'MARQUEZ',
        'NAVARRO', 'PASCUAL', 'SALAZAR', 'TAN', 'UY',
    ];

    public function paginateEnrolledStudents(int $page, int $perPage): LengthAwarePaginator
    {
        $start = ($page - 1) * $perPage;
        $end = min($start + $perPage, self::TOTAL_STUDENTS);

        $students = [];
        for ($index = $start; $index < $end; $index++) {
            $students[] = $this->student($index);
        }

        return new LengthAwarePaginator($students, self::TOTAL_STUDENTS, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    private function student(int $index): array
    {
        // Pairs every first name with every last name once (3 is coprime with 20),
        // so names and emails never repeat within TOTAL_STUDENTS.
        $firstNameIndex = $index % count(self::FIRST_NAMES);
        $round = intdiv($index, count(self::FIRST_NAMES));
        $firstName = self::FIRST_NAMES[$firstNameIndex];
        $lastName = self::LAST_NAMES[(3 * $round + $firstNameIndex) % count(self::LAST_NAMES)];

        [$programId, $title, $description] = $this->pick(self::PROGRAMS, 'program', $index);
        $yearLevel = $this->pick(array_keys(self::YEAR_LEVELS), 'year', $index);
        $entryYear = self::FIRST_YEAR_ENTRY - $yearLevel + 1;

        return [
            'term_id' => self::TERM_ID,
            'campus_id' => $entryYear.sprintf('%05d', 1000 + $index * 13),
            'last_name' => $lastName,
            'first_name' => $firstName,
            'middle_name' => $this->pick(self::LAST_NAMES, 'middle', $index),
            'suffix' => $index % 40 === 39 ? 'JR.' : null,
            'academic_program_id' => $programId,
            'title' => $title,
            'description' => $description,
            'birthdate' => sprintf(
                '%d-%02d-%02d',
                $entryYear - 19,
                crc32("month-{$index}") % 12 + 1,
                crc32("day-{$index}") % 28 + 1,
            ),
            'country_of_citizenship' => null,
            'email' => $this->email($firstName, $lastName),
            'year_level' => self::YEAR_LEVELS[$yearLevel],
            'status' => 'ACTIVE',
        ];
    }

    private function email(string $firstName, string $lastName): string
    {
        $initials = implode('', array_map(fn ($word) => $word[0], explode(' ', $firstName)));

        return strtolower($initials.$lastName).'@up.edu.ph';
    }

    private function pick(array $values, string $salt, int $index): mixed
    {
        return $values[crc32("{$salt}-{$index}") % count($values)];
    }
}
