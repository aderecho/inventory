<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DisposalPrintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use setasign\Fpdi\Tcpdf\Fpdi;
use Tests\TestCase;

class DisposalCombinedPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_mixed_selection_prints_both_forms_in_one_document(): void
    {
        $result = app(DisposalPrintService::class)->generateCombinedIirupPdf($this->payload([
            ['property_no' => '223-1-1-001', 'unit_cost' => 60000],
            ['property_no' => '223-1-1-002', 'unit_cost' => 12000],
        ]));

        $this->assertSame('IIRUP', $result['type']);
        $this->assertStringStartsWith('%PDF', $result['pdf']);
        // One page for the ₱50,000 and above form, one for the one below it.
        $this->assertSame(2, $this->pageCount($result['pdf']));
    }

    public function test_a_one_sided_selection_prints_only_the_form_it_belongs_on(): void
    {
        $service = app(DisposalPrintService::class);

        $above = $service->generateCombinedIirupPdf($this->payload([
            ['property_no' => '223-1-1-003', 'unit_cost' => 75000],
        ]));
        $this->assertSame(1, $this->pageCount($above['pdf']));

        $below = $service->generateCombinedIirupPdf($this->payload([
            ['property_no' => '223-1-1-004', 'unit_cost' => 5000],
        ]));
        $this->assertSame(1, $this->pageCount($below['pdf']));
    }

    public function test_the_single_form_endpoints_still_refuse_the_wrong_side(): void
    {
        $service = app(DisposalPrintService::class);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('below ₱50,000');

        $service->generateIirupPdf($this->payload([
            ['property_no' => '223-1-1-005', 'unit_cost' => 12000],
        ]));
    }

    public function test_the_combined_stream_reports_each_form_then_the_file(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->post(route('disposal.print.iirup.combined.stream'), $this->payload([
                ['property_no' => '223-1-1-006', 'unit_cost' => 60000],
                ['property_no' => '223-1-1-007', 'unit_cost' => 9000],
            ]))
            ->assertOk();

        $events = $this->streamedEvents($response);
        $completed = end($events);
        $steps = collect($events)->pluck('step')->all();

        $this->assertSame('completed', $completed['state']);
        $this->assertSame(2, $this->pageCount(base64_decode($completed['file'])));
        $this->assertContains('Preparing the ₱50,000 and above form', $steps);
        $this->assertContains('Preparing the below ₱50,000 form', $steps);
    }

    /** @param array<int, array<string, mixed>> $items */
    private function payload(array $items): array
    {
        return [
            'entity_name' => 'UNIVERSITY OF THE PHILIPPINES CEBU',
            'fund_cluster' => '01',
            'as_at' => 'October 16, 2026',
            'accountable_officer' => 'Jerecho E. Latosa',
            'designation' => 'Supply Officer',
            'station' => 'Supply Office',
            'items' => array_map(fn (array $item) => [
                'date_acquired' => '2026-03-11',
                'particulars' => 'Item ' . $item['property_no'],
                'property_no' => $item['property_no'],
                'serial_number' => 'SN-' . $item['property_no'],
                'qty' => 1,
                'unit_measure' => 'unit',
                'unit_cost' => $item['unit_cost'],
                'impairment_loss' => 0,
                'remarks' => 'For disposal',
            ], $items),
        ];
    }

    private function pageCount(string $pdf): int
    {
        $path = tempnam(sys_get_temp_dir(), 'iirup-test') . '.pdf';
        file_put_contents($path, $pdf);

        $count = (new Fpdi())->setSourceFile($path);

        @unlink($path);

        return $count;
    }

    /** @return array<int, array<string, mixed>> */
    private function streamedEvents(TestResponse $response): array
    {
        return collect(explode("\n", trim($response->streamedContent())))
            ->filter()
            ->map(fn (string $line) => json_decode($line, true))
            ->all();
    }
}
