<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Support\Facades\Mail;

/**
 * Builds a per-user, isolated SMTP mailer at send time, using that account's
 * own connected Gmail credentials (never a shared/admin one). Each call
 * registers a uniquely-named runtime mailer config so concurrent or
 * back-to-back sends for different users in the same queue worker process
 * never leak one user's credentials into another's send.
 */
class UserMailer
{
    /**
     * Register a runtime mailer for this profile's own email account and
     * return its name. Caller MUST call Mail::purge($name) after sending to
     * release the credentials from Laravel's resolved-mailer cache.
     */
    public function mailerFor(Profile $profile): string
    {
        if (!$profile->hasMailCredentials()) {
            throw new \RuntimeException('This account has not connected an email sender yet. Add a Gmail App Password in Settings.');
        }

        $name = 'user_' . $profile->id;

        config(["mail.mailers.{$name}" => [
            'transport' => 'smtp',
            'scheme'    => 'smtp',
            'host'      => 'smtp.gmail.com',
            'port'      => 587,
            'username'  => $profile->mail_username,
            'password'  => $profile->mail_password,
        ]]);

        // Gmail rejects sends where the From address doesn't match the
        // authenticated account, so these must always match.
        config(['mail.from' => [
            'address' => $profile->mail_username,
            'name'    => $profile->mail_from_name ?: ($profile->full_name ?: 'Application'),
        ]]);

        return $name;
    }

    /** Release a runtime mailer's cached transport/credentials. */
    public function release(string $name): void
    {
        Mail::purge($name);
    }
}
