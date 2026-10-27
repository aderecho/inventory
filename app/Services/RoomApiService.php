<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class RoomApiService
{

    public function fetchRooms(): array
{
    $cached = Cache::get('rooms_cache');

    if ($cached !== null) {
        return [
            'success' => true,
            'data' => $cached,
        ];
    }

    $apiUrl = config('services.rooms_api.url');
    $token = config('services.rooms_api.token');

    if (blank($apiUrl)) {
        Log::error('Rooms API: SYSTEM_B_API_URL is not set.');

        return [
            'success' => false,
            'data' => [],
        ];
    }

    try {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])
            ->timeout(5)
            ->get($apiUrl);

        if (!$response->successful()) {
            Log::warning('Rooms API returned HTTP ' . $response->status());
        }

        $data = $response->successful()
            ? $response->json()
            : [];

        $rooms = $data['data'] ?? [];

        // Only cache if we got actual data
        if (!empty($rooms)) {
            Cache::put('rooms_cache', $rooms, now()->addHours(24));
        }

        return [
            'success' => true,
            'data' => $rooms,
        ];
    } catch (ConnectionException $e) {
        Log::error($e->getMessage());

        return [
            'success' => false,
            'data' => Cache::get('rooms_cache', []),
        ];
    }
}
}
