<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Monolog\Handler\TestHandler;

uses(RefreshDatabase::class);

test('login attempts share a limit across equivalent Indonesian phone formats and recover after a minute', function () {
    $this->freezeTime();
    $member = User::factory()->create(['phone' => '081234567890', 'is_active' => true]);
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson(route('login.post'), ['phone' => $member->phone, 'password' => 'incorrect'])->assertUnprocessable();
    }

    $this->postJson(route('login.post'), ['phone' => '+62 812-3456-7890', 'password' => 'password'])
        ->assertTooManyRequests()->assertHeader('Retry-After');
    $this->assertGuest();
    $this->travel(61)->seconds();
    $this->post(route('login.post'), ['phone' => $member->phone, 'password' => 'password'])->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($member);
});

test('login limits also stop attempts that rotate through different account numbers', function () {
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $this->postJson(route('login.post'), ['phone' => '08120000'.str_pad((string) $attempt, 4, '0', STR_PAD_LEFT), 'password' => 'incorrect'])->assertUnprocessable();
    }

    $this->postJson(route('login.post'), ['phone' => '081299999999', 'password' => 'incorrect'])->assertTooManyRequests();
});

test('activation limits follow the account even when a client changes IP address', function () {
    $member = User::factory()->awaitingActivation()->create();
    for ($attempt = 1; $attempt <= 10; $attempt++) {
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.$attempt])
            ->postJson(route('activation.check'), ['phone' => $member->phone, 'activation_code' => '87654321'])->assertUnprocessable();
    }

    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.99'])
        ->postJson(route('activate.post'), ['phone' => $member->phone, 'activation_code' => '12345678', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->assertTooManyRequests();
    expect($member->fresh()->is_active)->toBeFalse();
});

test('malformed query parameters return validation errors instead of server errors', function (string $route, array $query) {
    $member = User::factory()->create(['role' => UserRole::Admin]);
    if ($route !== 'login') {
        $this->actingAs($member);
    }

    $this->getJson(route($route, $query))->assertUnprocessable();
})->with([
    ['login', ['phone' => ['unexpected']]],
    ['login', ['tab' => ['unexpected']]],
    ['roster', ['search' => ['unexpected']]],
    ['evaluating-hc.index', ['filter' => ['unexpected']]],
]);

test('protected screens require authentication and member accounts cannot access admin operations', function () {
    foreach (['home', 'profile', 'roster', 'fun-games.index', 'evaluating-hc.index', 'dashboard'] as $name) {
        $this->getJson(route($name))->assertUnauthorized();
    }
    $member = User::factory()->create();
    $target = User::factory()->create();
    $this->actingAs($member)->getJson(route('dashboard'))->assertForbidden();
    $this->putJson(route('roster.role', $target), ['role' => 'superadmin'])->assertForbidden();
    expect($target->fresh()->role)->toBe(UserRole::Member);
});

test('server errors in production hide exception details and stack traces', function () {
    config(['app.debug' => false]);
    Route::middleware('web')->get('/security-test-error', function (): never {
        throw new RuntimeException('private-database-password-and-internal-file-path');
    });

    $this->getJson('/security-test-error')->assertInternalServerError()->assertExactJson(['message' => 'Server Error']);
    $this->get('/security-test-error')->assertInternalServerError()->assertDontSee('private-database-password')->assertDontSee('RuntimeException');
});

test('sensitive actions are logged with identifiers without request credentials or activation codes', function () {
    $handler = new TestHandler;
    Log::channel('security')->getLogger()->setHandlers([$handler]);
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = User::factory()->create(['password' => null]);

    $response = $this->actingAs($admin)->post(route('roster.activation-code', $member), ['password' => 'never-log-this-password'])
        ->assertRedirect(route('roster.show', $member))->assertHeader('X-Request-ID');

    $records = $handler->getRecords();
    expect($records)->toHaveCount(1);
    expect($records[0]->message)->toBe('action.completed');
    expect($records[0]->context)->toMatchArray(['route' => 'roster.activation-code', 'actor_id' => $admin->id, 'subjects' => ['user' => $member->id]]);
    expect(json_encode($records))->not->toContain('never-log-this-password', session('issued_activation_code.code'), $member->phone);
});

test('user supplied text is escaped and roster search stays parameterized', function () {
    $member = User::factory()->create();
    $text = '<script>alert("unsafe")</script>';
    $this->actingAs($member)->post(route('drilling.store'), ['drill_name' => $text, 'success_count' => 1, 'fail_count' => 0, 'notes' => $text])->assertSessionHasNoErrors();

    $this->get(route('drilling'))->assertOk()->assertSee($text)->assertDontSee($text, false);
    $this->get(route('roster', ['search' => "' OR 1=1 --"]))->assertOk()->assertViewHas('totalMembers', 0);
});

test('unused private storage HTTP upload and download endpoints are disabled', function () {
    $this->get('/storage/private-security-test.txt')->assertNotFound();
    $this->putJson('/storage/private-security-test.txt', ['content' => 'unexpected'])->assertNotFound();
});

test('changing a password revokes other database sessions and remembered access while keeping the current device signed in', function () {
    config(['session.driver' => 'database']);
    $member = User::factory()->create(['is_active' => true]);
    $cookieName = Auth::guard()->getRecallerName();
    $login = $this->post(route('login.post'), ['phone' => $member->phone, 'password' => 'password', 'remember' => '1'])
        ->assertRedirect(route('home'))->assertCookie($cookieName);
    $oldCookie = $login->getCookie($cookieName);
    $oldToken = $member->fresh()->remember_token;
    $oldSession = session()->getId();
    DB::table('sessions')->insert(['id' => 'another-device-session', 'user_id' => $member->id, 'payload' => '', 'last_activity' => now()->getTimestamp()]);

    $this->put(route('profile.password'), ['current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
        ->assertRedirect(route('profile'))->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($member);
    expect(session()->getId())->not->toBe($oldSession);
    expect($member->fresh()->remember_token)->not->toBe($oldToken);
    $this->assertDatabaseMissing('sessions', ['id' => 'another-device-session']);
    $this->assertDatabaseMissing('sessions', ['id' => $oldSession]);
    Auth::forgetGuards();
    $this->get(route('home'))->assertOk();
    session()->invalidate();
    session()->save();
    Auth::forgetGuards();
    $this->withCookie($cookieName, $oldCookie->getValue())->get(route('home'))->assertRedirect(route('login'));
    $this->assertGuest();
});
