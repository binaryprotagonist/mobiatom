<?php

namespace App\Jobs;

use App\Model\InvoiceReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Mail\Mailer;


class ReminderInvoiceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $obj;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(InvoiceReminder $invoice_reminder)
    {
        $this->obj = $invoice_reminder;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Mailer $mailer)
    {
        $subject = 'Reminder Invoice () from ' . $this->obj->user->getName();
        $email = $this->obj->user->email;
        
        $mailer->send('emails.reminder_invoice', ['data' => $this->obj], function ($message) use ($email, $subject) {
            $message->to($email)
          ->subject($subject);
        });
    }
}
