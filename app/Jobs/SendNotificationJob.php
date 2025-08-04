<?php

namespace App\Jobs;

use App\Mail\SendOtpMail;
use App\Models\User;
use App\Services\SmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $otp;
    protected $notificationType;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @param string $otp
     * @param string $notificationType
     * @return void
     */
    public function __construct(User $user, string $otp, string $notificationType = 'email')
    {
        $this->user = $user;
        $this->otp = $otp;
        $this->notificationType = $notificationType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Processing ' . $this->notificationType . ' notification via Laravel queue', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number
            ]);

            if ($this->notificationType === 'sms') {
                $smsService = new SmService();
                if ($smsService->isConfigured()) {
                    $result = $smsService->sendOtp($this->user->phone_number, $this->otp);
                    if ($result['success']) {
                        Log::info('SMS notification sent successfully via Laravel queue', [
                            'user_id' => $this->user->id,
                            'phone_number' => $this->user->phone_number
                        ]);
                    } else {
                        Log::error('Failed to send SMS via Laravel queue', [
                            'user_id' => $this->user->id,
                            'phone_number' => $this->user->phone_number,
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                        throw new \Exception('Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'));
                    }
                } else {
                    Log::error('SMS service not configured', [
                        'user_id' => $this->user->id,
                        'phone_number' => $this->user->phone_number
                    ]);
                    throw new \Exception('SMS service not configured');
                }
            } else {
                // Email notification
                Mail::to($this->user->email)->send(new SendOtpMail($this->otp));
                Log::info('Email notification sent successfully via Laravel queue', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send ' . $this->notificationType . ' notification via Laravel queue', [
                'user_id' => $this->user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
