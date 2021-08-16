<?php

namespace App\Jobs;

use App\Model\Verification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\User;
use App\Objects\InviteUser;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Str;

class NewUserRegisterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $obj;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->obj = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Mailer $mailer)
    {
        
        $subject = 'New User Register';
        $email = $this->obj->email;
        $token = Str::random(60);
        $final_token = hash('sha256', $token);

        $verification = new Verification;
        $verification->email = $email;
        $verification->token = $final_token;
        $verification->code = rand(100000,999999);
        $verification->save();
        $this->obj->url = "https://" . config('app.current_domain') . '.mobiato-msfa.com/settings/organization?token=' . $final_token;

        
        $mailer->send('emails.registerUser', ['data' => $this->obj], function ($message) use ($email, $subject) {
            $message->to($email)
          ->subject($subject);
        });
    }
}
