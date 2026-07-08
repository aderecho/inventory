<?php

namespace App\Http\Controllers;

use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ApiClientController extends Controller
{
    public function index()
    {
        return Inertia::render('ApiClients/Index', [
            'clients' => ApiClient::latest()->get()->map(fn($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'allowed_domains' => $client->allowed_domains ?? [],
                'is_active' => $client->is_active,
                'created_at' => $client->created_at->format('M d, Y'),
            ]),
        ]);
    }

    public function regenerate(ApiClient $apiClient)
    {
        $plainKey = \Illuminate\Support\Str::random(40);

        $apiClient->update([
            'api_key' => hash('sha256', $plainKey),
        ]);

        return redirect()->back()->with([
            'plain_api_key' => $plainKey,
            'client_name' => $apiClient->name,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_domains' => 'nullable|array',
            'allowed_domains.*' => 'url',
        ]);

        $plainKey = Str::random(40);

        $client = ApiClient::create([
            'name' => $validated['name'],
            'api_key' => hash('sha256', $plainKey),
            'user_id' => $request->user()->id,
            'allowed_domains' => $validated['allowed_domains'] ?? null,
            'is_active' => true,
        ]);

        // Flash the plain key once — it's never retrievable again after this request
        return redirect()->back()->with([
            'plain_api_key' => $plainKey,
            'client_name' => $client->name,
        ]);
    }

    public function update(Request $request, ApiClient $apiClient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'allowed_domains' => 'nullable|array',
            'allowed_domains.*' => 'url',
            'is_active' => 'boolean',
        ]);

        $apiClient->update($validated);

        return redirect()->back()->with('success', 'Client updated.');
    }

    public function destroy(ApiClient $apiClient)
    {
        $apiClient->delete();

        return redirect()->back()->with('success', 'Client removed.');
    }
}
