<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Str;

class ForgotPasswordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $obj;

    private $token;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, $token)
    {
        $this->obj = $user;
        $this->token = $token;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Mailer $mailer)
    {
        $subject = 'Forgot Password';
        $email = $this->obj->email;

        $this->obj->url = "https://" . config('app.current_domain') . '.mobiato-msfa.com/auth/reset-password?token=' . $this->token . "&email=" . $email;
        
        $mailer->send('emails.forgot', ['data' => $this->obj], function ($message) use ($email, $subject) {
            $message->to($email)
          ->subject($subject);
        });
    }
}
