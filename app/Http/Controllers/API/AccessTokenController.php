<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\AccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccessTokenController extends Controller
{
    public function store(Request $request)
    {
        $apiKey = $request->bearerToken();

        abort_unless($apiKey, 401, 'Missing integrated token.');

        $client = ApiClient::where(
            'api_key',
            hash('sha256', $apiKey)
        )
            ->where('is_active', true)
            ->first();

        abort_unless($client, 401, 'Invalid integrated token.');

        $plainToken = Str::random(64);

        AccessToken::create([
            'api_client_id' => $client->id,
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addHour(),
        ]);

        return response()->json([
            'access_token' => $plainToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
    }
}