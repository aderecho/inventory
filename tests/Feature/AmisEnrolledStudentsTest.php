<?php

namespace Tests\Feature;

use Tests\TestCase;

class AmisEnrolledStudentsTest extends TestCase
{
    public function test_it_lists_enrolled_students_in_the_amis_format(): void
    {
        $this->getJson('/api/v1/amis')
            ->assertOk()
            ->assertJsonCount(15, 'enrolled_students')
            ->assertJsonStructure([
                'enrolled_students' => [[
                    'term_id', 'campus_id', 'last_name', 'first_name', 'middle_name', 'suffix',
                    'academic_program_id', 'title', 'description', 'birthdate',
                    'country_of_citizenship', 'email', 'year_level', 'status',
                ]],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ])
            ->assertJsonPath('meta.total', 250)
            ->assertJsonPath('enrolled_students.0.term_id', 1261);
    }

    public function test_pages_cover_every_student_once(): void
    {
        $students = collect([1, 2, 3])->flatMap(
            fn (int $page) => $this->getJson("/api/v1/amis?per_page=100&page={$page}")->json('enrolled_students')
        );

        $this->assertCount(250, $students);
        $this->assertCount(250, $students->pluck('campus_id')->unique());
        $this->assertCount(250, $students->pluck('email')->unique());
        $this->assertSame($students->first(), $this->getJson('/api/v1/amis?per_page=1')->json('enrolled_students.0'));
    }

    public function test_page_links_keep_the_page_size(): void
    {
        $this->getJson('/api/v1/amis?per_page=50')
            ->assertJsonPath('meta.last_page', 5)
            ->assertJsonPath('links.next', url('/api/v1/amis?per_page=50&page=2'));
    }

    public function test_it_rejects_invalid_pagination(): void
    {
        $this->getJson('/api/v1/amis?per_page=101')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/v1/amis?page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
    }
}
