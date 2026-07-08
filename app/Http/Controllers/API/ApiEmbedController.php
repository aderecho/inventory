<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller; // <- add this
use App\Models\ApiClient;
use Illuminate\Http\Request;

class ApiEmbedController extends Controller
{
    public function issueToken(Request $request)
    {
        $apiKey = $request->bearerToken();
        abort_unless($apiKey, 401, 'Missing API key.');

        $client = ApiClient::where('api_key', hash('sha256', $apiKey))
            ->where('is_active', true)
            ->first();

        abort_unless($client, 401, 'Invalid API key.');

        $payload = [
            'client_id' => $client->id,
            'exp' => now()->addMinutes(1440)->timestamp,
        ];

        $token = encrypt($payload);

        return response()->json([
            'embed_url' => route('embed.dashboard', $token),
            'expires_in' => 600,
        ]);
    }
}