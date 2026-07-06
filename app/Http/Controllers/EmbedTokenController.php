<?php

namespace App\Http\Controllers;

use App\Models\EmbedToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmbedTokenController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'allowed_domains' => 'nullable|array',
            'allowed_domains.*' => 'url',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $token = EmbedToken::create([
            'token' => Str::random(48),
            'user_id' => $request->user()->id,
            'allowed_domains' => $validated['allowed_domains'] ?? null,
            'expires_at' => isset($validated['expires_in_days'])
                ? now()->addDays($validated['expires_in_days'])
                : null,
        ]);

        $url = route('embed.dashboard', $token->token);

        return response()->json([
            'embed_url' => $url,
            'iframe_snippet' => sprintf(
                '<iframe src="%s" width="100%%" height="600" frameborder="0"></iframe>',
                $url
            ),
        ]);
    }
}