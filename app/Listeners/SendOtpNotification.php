<?php


namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\SmService;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOtpNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */


    public function handle(UserRegistered $event): void
    {
        $user = $event->user;
        $otp = $event->otp;


        if ($user->verification_method === 'mobile') {
            $smsService = new SmService();
            if ($smsService->isConfigured()) {
                $smsService->sendOtp($user->phone_number, $otp);
            }
        } else {
            Mail::to($user->email)->send(new SendOtpMail($otp));
        }



    }


}

