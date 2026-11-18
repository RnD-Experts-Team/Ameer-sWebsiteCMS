<?php

namespace App\Console\Commands;

use App\Mail\TestEmail;
use App\Models\Setting;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

#[Signature('cms:test-mail {email? : Where to send the test email}')]
#[Description('Check the mail settings and send a real test email')]
class TestMailCommand extends Command
{
    public function handle(): int
    {
        $mailer = config('mail.default');
        $from = config('mail.from.address');
        $site = Setting::valueOf(Setting::Site);
        $recipients = array_values(array_filter((array) ($site['notificationEmails'] ?? [])));
        $queue = config('queue.default');

        $this->line("Mail driver: {$mailer}");
        $this->line("Sender: {$from}");
        $this->line('Notification emails: '.($recipients === [] ? '(none)' : implode(', ', $recipients)));
        $this->line("Queue: {$queue}");

        $warnings = 0;
        $warn = function (string $message) use (&$warnings): void {
            $warnings++;
            $this->warn($message);
        };

        if ($recipients === []) {
            $warn('No notification emails are set; requests go to the site email ('.($site['email'] ?? 'none').').');
        }

        if ($mailer === 'log') {
            $warn('MAIL_MAILER is "log": emails are only written to the log file and never delivered.');
        }

        if ($queue !== 'sync') {
            $waiting = $this->waitingJobs();
            $warn("Emails are queued ({$queue}): a worker must run \"php artisan queue:work\", or set QUEUE_CONNECTION=sync."
                .($waiting > 0 ? " {$waiting} email(s) are waiting in the queue." : ''));
        }

        if (str_ends_with((string) $from, '@example.com')) {
            $warn("The sender {$from} is a placeholder; set MAIL_FROM_ADDRESS to a real address.");
        }

        $to = $this->argument('email') ?: ($recipients[0] ?? ($site['email'] ?? null));

        if (blank($to)) {
            $this->error('No address to send to. Pass one: php artisan cms:test-mail you@example.com');

            return self::FAILURE;
        }

        try {
            Mail::to($to)->send(new TestEmail);
        } catch (Throwable $exception) {
            $this->error('The mail server refused the test email: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("The mail server accepted the test email for {$to}.".($warnings > 0 ? " ({$warnings} warning(s) above.)" : ''));

        return self::SUCCESS;
    }

    protected function waitingJobs(): int
    {
        try {
            return DB::table(config('queue.connections.database.table', 'jobs'))->count();
        } catch (Throwable) {
            return 0;
        }
    }
}
