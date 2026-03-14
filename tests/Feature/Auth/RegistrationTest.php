<?php

use App\Models\AllowedDomain;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyFeature(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    AllowedDomain::create(['domain' => 'example.com']);

    $response = $this->post(route('register.store'), [
        'name'                  => 'John Doe',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('users from disallowed domains cannot register', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'Hacker',
        'email'                 => 'hacker@evil.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
