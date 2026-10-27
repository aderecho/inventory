<?php

namespace Tests\Feature;

use App\Models\AcknowledgementItem;
use App\Models\InventoryItem;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\PrintService;
use App\Services\RoomApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use setasign\Fpdi\Tcpdf\Fpdi;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PrintedFormsPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });
    }

    public function test_a_receipt_below_50k_prints_as_one_a4_portrait_ics_page(): void
    {
        $item = $this->issuedItem(12000);

        $result = app(PrintService::class)->generateReceiptPdf([$item->id]);

        $this->assertSame('ICS', $result['type']);
        $this->assertPages($result['pdf'], 1, 210, 297);
    }

    public function test_a_receipt_at_50k_or_more_prints_as_one_a4_portrait_par_page(): void
    {
        $item = $this->issuedItem(65000);

        $result = app(PrintService::class)->generateReceiptPdf([$item->id]);

        $this->assertSame('PAR', $result['type']);
        $this->assertPages($result['pdf'], 1, 210, 297);
    }

    public function test_the_property_card_prints_on_one_letter_landscape_page(): void
    {
        $item = $this->issuedItem(12000);
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('view property cards', 'web'));

        $response = $this->actingAs($user)->get(route('property-cards.pdf', $item));

        $response->assertOk();
        $this->assertPages($response->getContent(), 1, 279.4, 215.9);
    }

    private function issuedItem(float $unitCost): InventoryItem
    {
        $item = InventoryItem::factory()->create(['unit_cost' => $unitCost]);

        AcknowledgementItem::factory()->create([
            'inventory_item_id' => $item->id,
            'accountable_person_id' => UserProfile::factory()->create(['user_id' => User::factory()])->id,
            'issued_by_id' => UserProfile::factory()->create(['user_id' => User::factory()])->id,
        ]);

        return $item;
    }

    private function assertPages(string $pdf, int $count, float $width, float $height): void
    {
        $this->assertStringStartsWith('%PDF', $pdf);

        $path = tempnam(sys_get_temp_dir(), 'form') . '.pdf';
        file_put_contents($path, $pdf);

        $document = new Fpdi();
        $pages = $document->setSourceFile($path);
        $size = $document->getTemplateSize($document->importPage(1));

        @unlink($path);

        $this->assertSame($count, $pages);
        $this->assertEqualsWithDelta($width, $size['width'], 1);
        $this->assertEqualsWithDelta($height, $size['height'], 1);
    }
}
