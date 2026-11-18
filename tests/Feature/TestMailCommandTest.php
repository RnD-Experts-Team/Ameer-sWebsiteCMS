<?php

use App\Mail\TestEmail;
use App\Models\Setting;
use App\Support\DefaultContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('it sends a test email to the given address', function () {
    Mail::fake();
    Setting::query()->create(['key' => Setting::Site, 'value' => [...DefaultContent::site(), 'notificationEmails' => []]]);

    $this->artisan('cms:test-mail', ['email' => 'homeameer62@gmail.com'])
        ->expectsOutputToContain('Notification emails: (none)')
        ->assertSuccessful();

    Mail::assertSent(TestEmail::class, fn (TestEmail $mail) => $mail->hasTo('homeameer62@gmail.com'));
});

test('it warns about the log driver', function () {
    Mail::fake();
    config(['mail.default' => 'log']);

    $this->artisan('cms:test-mail', ['email' => 'a@example.com'])
        ->expectsOutputToContain('"log"')
        ->assertSuccessful();
});
