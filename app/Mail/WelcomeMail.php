<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user; // make sure it's public so it is available in the view

    /**
     * Create a new message instance.
     *
     * @param \App\Models\User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user; // assign the passed user object
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        \Log::info('WelcomeMail user:', ['user' => $this->user]);
    
        return $this->subject('Welcome to ' . config('app.name'))
                    ->view('emails.welcome')
                    ->with(['user' => $this->user]);  // Explicitly share the user
    }

}
