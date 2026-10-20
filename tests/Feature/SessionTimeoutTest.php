<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A route behind the same guard the authenticated modules use.
        Route::middleware(['web', 'auth', 'check.session.timeout'])
            ->get('/__tests/session-guarded', fn () => response('ok'));
    }

    public function test_activity_inside_the_idle_window_is_allowed_and_refreshes_the_clock(): void
    {
        config(['session.idle_timeout' => 900]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'login_time' => now()->timestamp,
                'last_activity' => now()->subMinutes(5)->timestamp,
            ])
            ->get('/__tests/session-guarded')
            ->assertOk();

        $this->assertGreaterThanOrEqual(now()->subSecond()->timestamp, session('last_activity'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_idle_sessions_are_signed_out(): void
    {
        config(['session.idle_timeout' => 60]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'login_time' => now()->timestamp,
                'last_activity' => now()->subSeconds(61)->timestamp,
            ])
            ->get('/__tests/session-guarded')
            ->assertRedirect(route('login'))
            ->assertSessionHas('message', 'You were logged out due to inactivity.');

        $this->assertGuest();
    }

    public function test_the_absolute_cap_signs_the_user_out_even_while_active(): void
    {
        config(['session.lifetime' => 120]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'login_time' => now()->subMinutes(121)->timestamp,
                'last_activity' => now()->timestamp,
            ])
            ->get('/__tests/session-guarded')
            ->assertRedirect(route('login'))
            ->assertSessionHas('message', 'Your session has expired. Please log in again.');

        $this->assertGuest();
    }

    public function test_staying_signed_in_refreshes_the_server_clock(): void
    {
        config(['session.idle_timeout' => 60]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'login_time' => now()->timestamp,
                'last_activity' => now()->subSeconds(50)->timestamp,
            ])
            ->getJson('/session/ping')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertAuthenticatedAs($user);
        $this->assertGreaterThanOrEqual(now()->subSecond()->timestamp, session('last_activity'));
    }

    public function test_the_session_is_rotated_after_the_refresh_interval_without_signing_out(): void
    {
        config([
            'session.idle_timeout' => 900,
            'session.regenerate_interval' => 60,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession([
                'login_time' => now()->timestamp,
                'last_activity' => now()->timestamp,
                'last_regenerated' => now()->subSeconds(61)->timestamp,
            ])
            ->get('/__tests/session-guarded')
            ->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertGreaterThanOrEqual(now()->subSecond()->timestamp, session('last_regenerated'));
    }

    public function test_the_browser_receives_the_same_numbers_the_server_enforces(): void
    {
        config([
            'session.idle_timeout' => 900,
            'session.idle_warning' => 30,
            'session.lifetime' => 120,
            'session.absolute_warning' => 60,
        ]);

        Route::middleware(['web', 'auth', 'check.session.timeout'])
            ->get('/__tests/inertia-props', fn () => inertia('TestPage'));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/__tests/inertia-props');

        $sessionConfig = $response->viewData('page')['props']['sessionConfig'];

        $this->assertSame(900000, $sessionConfig['idleTimeout']);
        $this->assertSame(30000, $sessionConfig['warningTime']);
        $this->assertSame(7200000, $sessionConfig['absoluteTimeout']);
        $this->assertSame(60000, $sessionConfig['absoluteWarning']);
        $this->assertNotNull($sessionConfig['startedAt']);
    }
}
