<?php

use App\Enums\UserRole;
use App\Models\Position;
use App\Models\SchoolClass;
use Database\Seeders\SchoolReferenceSeeder;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $this->seed(SchoolReferenceSeeder::class);

    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new students can register with a class', function () {
    $this->seed(SchoolReferenceSeeder::class);

    $studentPosition = Position::query()->where('code', Position::STUDENT)->firstOrFail();
    $schoolClass = SchoolClass::query()->firstOrFail();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'position_id' => $studentPosition->id,
        'class_id' => $schoolClass->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'role' => UserRole::Buyer->value,
        'position_id' => $studentPosition->id,
        'class_id' => $schoolClass->id,
    ]);
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Registrasi berhasil. Silakan masuk menggunakan akun yang baru dibuat.');
});

test('students must choose a class when registering', function () {
    $this->seed(SchoolReferenceSeeder::class);

    $studentPosition = Position::query()->where('code', Position::STUDENT)->firstOrFail();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'position_id' => $studentPosition->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertInvalid(['class_id']);
    $this->assertGuest();
});

test('teachers can register without a class', function () {
    $this->seed(SchoolReferenceSeeder::class);

    $teacherPosition = Position::query()->where('code', Position::TEACHER)->firstOrFail();

    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'position_id' => $teacherPosition->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertGuest();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'role' => UserRole::Buyer->value,
        'position_id' => $teacherPosition->id,
        'class_id' => null,
    ]);
    $response
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Registrasi berhasil. Silakan masuk menggunakan akun yang baru dibuat.');
});
