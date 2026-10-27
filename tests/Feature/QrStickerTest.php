<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\User;
use App\Services\RoomApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class QrStickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sticker_is_generated_for_an_item_without_an_accountable_person(): void
    {
        $item = InventoryItem::factory()->create();

        $response = $this->actingAs($this->stickerPrinter())
            ->post(route('inventory.qr.pngs'), ['ids' => [$item->id]]);

        $response->assertOk()->assertDownload();
        $this->assertSame('image/png', $response->headers->get('content-type'));
    }

    public function test_the_zip_still_carries_stickers_for_unassigned_items(): void
    {
        $items = InventoryItem::factory()->count(2)->create();

        $response = $this->actingAs($this->stickerPrinter())
            ->post(route('inventory.qr.pngs'), ['ids' => $items->pluck('id')->all()]);

        $response->assertOk()->assertDownload();
        $this->assertSame('application/zip', $response->headers->get('content-type'));
    }

    public function test_sticker_generation_still_requires_the_print_permission(): void
    {
        $item = InventoryItem::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('inventory.qr.pngs'), ['ids' => [$item->id]])
            ->assertForbidden();
    }

    public function test_the_png_stream_reports_stages_then_the_file(): void
    {
        $item = InventoryItem::factory()->create();

        $response = $this->actingAs($this->stickerPrinter())
            ->post(route('inventory.qr.pngs.stream'), ['ids' => [$item->id]])
            ->assertOk();

        $events = $this->streamedEvents($response);
        $completed = end($events);

        $this->assertSame('working', $events[0]['state']);
        $this->assertSame('completed', $completed['state']);
        $this->assertSame('qr-code.png', $completed['filename']);
        $this->assertStringStartsWith("\x89PNG", base64_decode($completed['file']));

        $this->assertContains('Rendering the sticker', collect($events)->pluck('step')->all());
    }

    public function test_the_zip_stream_reports_each_sticker_and_the_pack_step(): void
    {
        $items = InventoryItem::factory()->count(2)->create();

        $response = $this->actingAs($this->stickerPrinter())
            ->post(route('inventory.qr.pngs.stream'), ['ids' => $items->pluck('id')->all()])
            ->assertOk();

        $events = $this->streamedEvents($response);
        $completed = end($events);
        $steps = collect($events)->pluck('step')->all();

        $this->assertSame('qr-codes.zip', $completed['filename']);
        $this->assertSame('PK', substr(base64_decode($completed['file']), 0, 2));
        $this->assertContains('Rendered sticker 1 of 2', $steps);
        $this->assertContains('Rendered sticker 2 of 2', $steps);
        $this->assertContains('Packing the ZIP file', $steps);
    }

    public function test_the_pdf_stream_returns_a_pdf(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $item = InventoryItem::factory()->create();

        $response = $this->actingAs($this->stickerPrinter())
            ->post(route('inventory.qr.pdfs.stream'), ['ids' => [$item->id]])
            ->assertOk();

        $events = $this->streamedEvents($response);
        $completed = end($events);

        $this->assertSame('qr-code.pdf', $completed['filename']);
        $this->assertStringStartsWith('%PDF', base64_decode($completed['file']));
    }

    public function test_the_stream_requires_the_print_permission(): void
    {
        $item = InventoryItem::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('inventory.qr.pngs.stream'), ['ids' => [$item->id]])
            ->assertForbidden();
    }

    /** @return array<int, array<string, mixed>> */
    private function streamedEvents(TestResponse $response): array
    {
        return collect(explode("\n", trim($response->streamedContent())))
            ->filter()
            ->map(fn (string $line) => json_decode($line, true))
            ->all();
    }

    private function stickerPrinter(): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('print inventory', 'web'));

        return $user;
    }
}
