<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('remembered login restores access after the session is lost and issues a thirty day cookie', function () {
    $this->freezeTime();
    $member = User::factory()->create(['is_active' => true]);
    $cookieName = Auth::guard()->getRecallerName();
    $response = $this->post(route('login.post'), [
        'phone' => $member->phone,
        'password' => 'password',
        'remember' => '1',
    ])->assertRedirect(route('home'))->assertCookie($cookieName);
    $cookie = $response->getCookie($cookieName);
    expect($cookie->getExpiresTime())->toBe(now()->addDays(30)->getTimestamp());
    session()->invalidate();
    session()->save();
    Auth::forgetGuards();

    $this->withCookie($cookieName, $cookie->getValue())->get(route('home'))->assertOk();

    $this->assertAuthenticatedAs($member);
    expect(Auth::viaRemember())->toBeTrue();
});

test('login without remembering the device requires login again after the session is lost', function () {
    $member = User::factory()->create(['is_active' => true]);
    $this->post(route('login.post'), [
        'phone' => $member->phone,
        'password' => 'password',
        'remember' => '0',
    ])->assertRedirect(route('home'))->assertCookieMissing(Auth::guard()->getRecallerName());
    session()->invalidate();
    session()->save();
    Auth::forgetGuards();

    $this->get(route('home'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('new activation can remember the device and restore access after the session is lost', function () {
    $this->freezeTime();
    $member = User::factory()->create(['password' => null]);
    $cookieName = Auth::guard()->getRecallerName();
    $response = $this->post(route('activate.post'), [
        'phone' => $member->phone,
        'password' => 'member-password',
        'password_confirmation' => 'member-password',
        'remember' => '1',
    ])->assertRedirect(route('home'))->assertSessionHasNoErrors()->assertCookie($cookieName);
    $cookie = $response->getCookie($cookieName);
    expect($cookie->getExpiresTime())->toBe(now()->addDays(30)->getTimestamp());
    $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => true]);
    session()->invalidate();
    session()->save();
    Auth::forgetGuards();

    $this->withCookie($cookieName, $cookie->getValue())->get(route('home'))->assertOk();

    $this->assertAuthenticatedAs($member);
    expect(Auth::viaRemember())->toBeTrue();
});

test('activation without remembering the device does not issue a persistent login cookie', function (?string $remember) {
    $member = User::factory()->create(['password' => null]);
    $payload = [
        'phone' => $member->phone,
        'password' => 'member-password',
        'password_confirmation' => 'member-password',
    ];
    if ($remember !== null) {
        $payload['remember'] = $remember;
    }

    $this->post(route('activate.post'), $payload)
        ->assertRedirect(route('home'))->assertSessionHasNoErrors()
        ->assertCookieMissing(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($member);
    $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => true]);
})->with(['unchecked' => ['0'], 'omitted' => [null]]);

test('an invalid remember selection cannot activate an account', function () {
    $member = User::factory()->create(['password' => null]);

    $this->post(route('activate.post'), [
        'phone' => $member->phone,
        'password' => 'member-password',
        'password_confirmation' => 'member-password',
        'remember' => 'unexpected-value',
    ])->assertSessionHasErrors('remember');

    $this->assertGuest();
    $this->assertDatabaseHas('users', ['id' => $member->id, 'is_active' => false, 'password' => null]);
});

test('logout invalidates the remembered login even if an old cookie is presented again', function () {
    $member = User::factory()->create(['is_active' => true]);
    $cookieName = Auth::guard()->getRecallerName();
    $response = $this->post(route('login.post'), [
        'phone' => $member->phone,
        'password' => 'password',
        'remember' => '1',
    ])->assertRedirect(route('home'));
    $cookie = $response->getCookie($cookieName);
    $this->post(route('logout'))->assertRedirect(route('login'));
    Auth::forgetGuards();

    $this->withCookie($cookieName, $cookie->getValue())->get(route('home'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('both login and activation forms offer the remember device choice with unchecked fallback', function () {
    $response = $this->get(route('login'))->assertOk();
    $document = new DOMDocument;
    $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($document);

    foreach (['login.post', 'activate.post'] as $routeName) {
        $form = $xpath->query('//form[@action="'.route($routeName).'"]')->item(0);
        expect($form)->toBeInstanceOf(DOMElement::class);
        $checkbox = $xpath->query('.//input[@type="checkbox" and @name="remember"]', $form)->item(0);
        $unchecked = $xpath->query('.//input[@type="hidden" and @name="remember"]', $form)->item(0);
        expect($checkbox)->toBeInstanceOf(DOMElement::class);
        expect($checkbox->getAttribute('value'))->toBe('1');
        expect($unchecked)->toBeInstanceOf(DOMElement::class);
        expect($unchecked->getAttribute('value'))->toBe('0');
    }
});
