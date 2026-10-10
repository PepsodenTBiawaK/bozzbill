<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

test('login page can be rendered successfully', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
    $response->assertSee('Silahkan Masuk, Bozz !!');
    $response->assertSee('Aktivasi Akun');
});

test('active member can log in with valid credentials', function () {
    $response = $this->post(route('login.post'), [
        'phone' => '081111111111', // Apr Pinky
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticated();
});

test('members can log in with the remember option enabled', function () {
    $loginPage = $this->get(route('login'))->assertOk();
    $document = new DOMDocument;
    $document->loadHTML($loginPage->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $rememberCheckbox = (new DOMXPath($document))->query('//input[@name="remember" and @type="checkbox"]')->item(0);

    expect($rememberCheckbox)->toBeInstanceOf(DOMElement::class);

    $response = $this->post(route('login.post'), [
        'phone' => '081111111111',
        'password' => 'password',
        'remember' => $rememberCheckbox->hasAttribute('value') ? $rememberCheckbox->getAttribute('value') : 'on',
    ]);

    $response->assertRedirect(route('home'))
        ->assertSessionHasNoErrors()
        ->assertCookie(auth()->guard()->getRecallerName());
    $this->assertAuthenticated();
});

test('members can log in without remembering the device', function () {
    $loginPage = $this->get(route('login'))->assertOk();
    $document = new DOMDocument;
    $document->loadHTML($loginPage->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $uncheckedValue = (new DOMXPath($document))->query('//input[@name="remember" and @type="hidden"]')->item(0);

    expect($uncheckedValue)->toBeInstanceOf(DOMElement::class);

    $response = $this->post(route('login.post'), [
        'phone' => '081111111111',
        'password' => 'password',
        'remember' => $uncheckedValue->getAttribute('value'),
    ]);

    $response->assertRedirect(route('home'))
        ->assertSessionHasNoErrors()
        ->assertCookieMissing(auth()->guard()->getRecallerName());
    $this->assertAuthenticated();
});

test('login validation keeps an unchecked remember option unchecked', function () {
    $response = $this->from(route('login'))->post(route('login.post'), [
        'phone' => '081111111111',
        'password' => 'incorrect-password',
        'remember' => '0',
    ]);

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors('password')
        ->assertSessionHas('_old_input.remember', '0');
    $this->assertGuest();

    $loginPage = $this->get(route('login'))->assertOk();
    $document = new DOMDocument;
    $document->loadHTML($loginPage->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $rememberCheckbox = (new DOMXPath($document))->query('//input[@name="remember" and @type="checkbox"]')->item(0);

    expect($rememberCheckbox)->toBeInstanceOf(DOMElement::class);
    expect($rememberCheckbox->hasAttribute('checked'))->toBeFalse();
});

test('member cannot log in with invalid password', function () {
    $response = $this->post(route('login.post'), [
        'phone' => '081111111111',
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertGuest();
});

test('unactivated member login redirects to activation tab with warning', function () {
    $response = $this->post(route('login.post'), [
        'phone' => '087777777777', // Unactivated member
        'password' => 'any-password',
    ]);

    $response->assertRedirect(route('login', ['tab' => 'activation', 'phone' => '087777777777']));
    $response->assertSessionHas('status_warning');
    $this->assertGuest();
});

test('checking unregistered phone returns 404 error message', function () {
    $response = $this->postJson(route('activation.check'), [
        'phone' => '089999999999',
    ]);

    $response->assertStatus(404);
    $response->assertJson([
        'status' => 'not_found',
    ]);
});

test('checking unactivated phone returns ready status with member name', function () {
    $response = $this->postJson(route('activation.check'), [
        'phone' => '087777777777',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'ready',
        'user' => [
            'name' => 'Calon Member Bozz',
            'phone' => '087777777777',
            'handicap' => '3N',
        ],
    ]);
});

test('unactivated member can activate account by setting password and is logged in', function () {
    $unactivated = User::where('phone', '087777777777')->first();
    expect($unactivated->is_active)->toBeFalse();
    expect($unactivated->password)->toBeNull();

    $response = $this->post(route('activate.post'), [
        'phone' => '087777777777',
        'password' => 'newsecret123',
        'password_confirmation' => 'newsecret123',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($unactivated);

    $unactivated->refresh();
    expect($unactivated->is_active)->toBeTrue();
    expect($unactivated->password)->not->toBeNull();
});

test('authenticated user can log out', function () {
    $user = User::where('phone', '081111111111')->first();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
