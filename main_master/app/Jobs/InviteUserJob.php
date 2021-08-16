<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\User;
use App\Objects\InviteUser;
use Illuminate\Contracts\Mail\Mailer;
use URL;

class InviteUserJob implements ShouldQueue
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

        $subject = 'Invited User';
        $email = $this->obj->email;

        $url = str_replace('application-backend/public', '', URL::to('/account/password-change?uuid='.$this->obj->uuid.'&email=' . $this->obj->email));

        $this->obj->url = $url;

        $mailer->send('emails.invite_user', ['data' => $this->obj], function ($message) use ($email, $subject) {
            $message->to($email)
          ->subject($subject);
        });
    }
}
