<?php

use App\Mail\ContactRequestReceived;
use App\Models\ContactRequest;
use App\Models\Setting;
use App\Support\DefaultContent;
use App\Support\TurnstileVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.turnstile.secret_key' => 'test-secret']);
    Mail::fake();
    Setting::query()->create(['key' => Setting::Site, 'value' => [
        ...DefaultContent::site(),
        'notificationEmails' => ['manager@example.com', 'owner@example.com'],
    ]]);
});

function contactPayload(array $overrides = []): array
{
    return [
        'name' => 'Jordan Ellis',
        'phone' => '(614) 555-0100',
        'email' => 'jordan@example.com',
        'projectType' => 'Residential Remodeling',
        'address' => '43015',
        'message' => 'Kitchen remodel and a small addition.',
        'humanToken' => 'token',
        ...$overrides,
    ];
}

test('a verified request is saved and emailed to the manager', function () {
    Http::fake([TurnstileVerifier::VerifyUrl => Http::response(['success' => true])]);

    $this->postJson('/api/v1/contact', contactPayload())->assertOk()->assertExactJson(['ok' => true]);

    $request = ContactRequest::query()->sole();
    expect($request->name)->toBe('Jordan Ellis')
        ->and($request->project_type)->toBe('Residential Remodeling')
        ->and($request->status->value)->toBe('new');

    Mail::assertQueued(ContactRequestReceived::class, fn (ContactRequestReceived $mail) => $mail->hasTo('manager@example.com')
        && $mail->hasTo('owner@example.com')
        && $mail->hasReplyTo('jordan@example.com'));

    Http::assertSent(fn ($request) => $request['secret'] === 'test-secret' && $request['response'] === 'token');
});

test('a request with a failed human check is rejected and not saved', function () {
    Http::fake([TurnstileVerifier::VerifyUrl => Http::response(['success' => false])]);

    $this->postJson('/api/v1/contact', contactPayload())
        ->assertOk()
        ->assertJsonPath('ok', false)
        ->assertJsonStructure(['error']);

    expect(ContactRequest::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('requests are rejected when turnstile is not configured', function () {
    config(['services.turnstile.secret_key' => null]);
    Http::fake();

    $this->postJson('/api/v1/contact', contactPayload())->assertOk()->assertJsonPath('ok', false);

    Http::assertNothingSent();
    expect(ContactRequest::query()->count())->toBe(0);
});

test('invalid input returns one short message', function (array $overrides) {
    Http::fake([TurnstileVerifier::VerifyUrl => Http::response(['success' => true])]);

    $this->postJson('/api/v1/contact', contactPayload($overrides))
        ->assertOk()
        ->assertJsonPath('ok', false)
        ->assertJsonMissingPath('errors');

    expect(ContactRequest::query()->count())->toBe(0);
})->with([
    'short name' => [['name' => 'J']],
    'phone without enough digits' => [['phone' => '555-0100']],
    'invalid email' => [['email' => 'not-an-email']],
    'short message' => [['message' => 'Hi']],
    'missing human token' => [['humanToken' => '']],
    'message too long' => [['message' => str_repeat('a', 3001)]],
]);

test('a repeated request is rejected', function () {
    Http::fake([TurnstileVerifier::VerifyUrl => Http::response(['success' => true])]);

    $this->postJson('/api/v1/contact', contactPayload())->assertOk();
    $this->postJson('/api/v1/contact', contactPayload(['email' => 'JORDAN@example.com']))
        ->assertOk()
        ->assertJsonPath('ok', false);

    expect(ContactRequest::query()->count())->toBe(1);
});

test('there is no per-ip limit, the human check is the protection', function () {
    Http::fake([TurnstileVerifier::VerifyUrl => Http::response(['success' => true])]);

    foreach (range(1, 8) as $attempt) {
        $this->postJson('/api/v1/contact', contactPayload(['message' => "Request number {$attempt}"]))
            ->assertOk()
            ->assertJsonPath('ok', true);
    }

    expect(ContactRequest::query()->count())->toBe(8);
});
