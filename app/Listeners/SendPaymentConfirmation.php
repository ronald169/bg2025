<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Mail\PaymentConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPaymentConfirmation implements ShouldQueue
{

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        Mail::to($payment->user->email)
            ->send(new PaymentConfirmationMail($payment));
    }
}
