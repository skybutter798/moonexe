<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailChangedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $oldEmail;
    public $newEmail;

    /**
     * Create a new message instance.
     *
     * @param string $oldEmail
     * @param string $newEmail
     */
    public function __construct(string $oldEmail, string $newEmail)
    {
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        \Log::info('EmailChangedNotification:', [
            'from' => $this->oldEmail,
            'to' => $this->newEmail
        ]);

        return $this->subject('Your Email Address Has Been Updated')
                    ->view('emails.email_changed')
                    ->with([
                        'oldEmail' => $this->oldEmail,
                        'newEmail' => $this->newEmail,
                    ]);
    }
}