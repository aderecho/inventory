<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RoomApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Production runs with a cached config, where env() returns null outside the
 * config files, so these settings have to come through config().
 */
class ProductionSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_rooms_api_uses_the_configured_url_and_token(): void
    {
        config([
            'services.rooms_api.url' => 'https://rooms.example.test/api/rooms',
            'services.rooms_api.token' => 'rooms-secret',
        ]);
        Cache::forget('rooms_cache');
        Http::fake([
            'rooms.example.test/*' => Http::response(['data' => [['id' => 7, 'room_name' => 'ADM 201']]]),
        ]);

        $result = app(RoomApiService::class)->fetchRooms();

        $this->assertSame('ADM 201', $result['data'][0]['room_name']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer rooms-secret'));
    }

    public function test_a_missing_rooms_url_returns_no_rooms_instead_of_failing(): void
    {
        config(['services.rooms_api.url' => null]);
        Cache::forget('rooms_cache');
        Http::fake();

        $result = app(RoomApiService::class)->fetchRooms();

        $this->assertFalse($result['success']);
        $this->assertSame([], $result['data']);
        Http::assertNothingSent();
    }

    public function test_the_inventory_api_checks_the_configured_token(): void
    {
        config(['services.inventory_api.token' => 'api-secret']);

        $this->withToken('api-secret')->getJson('/api/v1/inventory')->assertOk();
        $this->withToken('wrong')->getJson('/api/v1/inventory')->assertUnauthorized();
    }

    public function test_the_inventory_api_refuses_every_token_when_none_is_configured(): void
    {
        config(['services.inventory_api.token' => null]);

        $this->withToken('anything')->getJson('/api/v1/inventory')->assertUnauthorized();
    }

    public function test_an_import_file_over_the_php_upload_limit_gets_a_clear_message(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('import inventory', 'web'));

        // What PHP hands over when the file is above upload_max_filesize.
        $file = new UploadedFile(__FILE__, 'items.xlsx', null, UPLOAD_ERR_INI_SIZE, true);

        $this->actingAs($user)
            ->postJson(route('inventory.import'), ['file' => $file, 'dry_run' => 1])
            ->assertUnprocessable()
            ->assertJsonPath('errors.file.0', 'The file did not reach the server. It may be larger than the server upload limit.');
    }
}
