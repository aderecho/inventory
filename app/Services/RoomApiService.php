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

    $apiUrl = env('SYSTEM_B_API_URL');
    $token = env('SYSTEM_B_API_TOKEN');

    try {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ])
            ->timeout(5)
            ->get($apiUrl);

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
