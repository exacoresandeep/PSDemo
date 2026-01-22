<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\OutstandingPaymentCommitment;
use App\Models\InfluencerVisit;
use App\Models\Lead;
use App\Models\Dealer;
use App\Models\Employee;
use App\Services\FirebasePushService;
use Illuminate\Support\Facades\Log;

class PushNotificationCron extends Command
{
    protected $signature = 'app:push-notification-cron';
    protected $description = 'Send all follow-up push notifications';

    public function handle()
    {
        try {
            $today = Carbon::today()->toDateString();
            $pushService = new FirebasePushService();

            $this->sendPaymentCommitments($today, $pushService);
            $this->sendInfluencerFollowups($today, $pushService);
            $this->sendLeadFollowups($today, $pushService);

            $this->info('All push notifications processed successfully.');

        } catch (\Exception $e) {
            Log::error('PushNotificationCron Error: ' . $e->getMessage());
            $this->error('Push notification cron failed.');
        }
    }

    private function sendPaymentCommitments($today, $pushService)
    {
        $commitments = OutstandingPaymentCommitment::with(
            'outstandingPayment.dealer.assignRoute.employee'
        )
        ->whereDate('committed_date', $today)
        ->get();

        foreach ($commitments as $commitment) {

            $dealer = $commitment->outstandingPayment?->dealer;
            if (!$dealer) continue;

            /**
             * 1️⃣ Notify Employee (Route Owner)
             */
            $employee = $commitment->outstandingPayment?->dealer->assignEmpRoute?->employee;
            // dd($employee);
            if ($employee && $employee->fcm_token) {
                dd($employee->fcm_token);
                $pushService->sendNotification(
                    $employee->fcm_token,
                    'Payment Commitment Due',
                    'A dealer has a committed payment due today.',
                    'employees',
                    [
                        'commitment_id' => (string) $commitment->id,
                        'dealer_id'     => (string) $dealer->id,
                        'amount'        => (string) $commitment->committed_amount,
                    ]
                );
            }

            /**
             * 2️⃣ Notify Dealer (Who committed to pay)
             */
            if (!empty($dealer->fcm_token)) {
                $pushService->sendNotification(
                    $dealer->fcm_token,
                    'Payment Reminder',
                    'Your committed payment is due today. Please complete the payment.',
                    'dealers',
                    [
                        'commitment_id' => (string) $commitment->id,
                        'amount'        => (string) $commitment->committed_amount,
                    ]
                );
            }
        }
    }


    private function sendInfluencerFollowups($today, $pushService)
    {
        $visits = InfluencerVisit::whereDate('follow_up_date', $today)
            ->whereNotNull('created_by')
            ->get();

        foreach ($visits as $visit) {

            $employee = Employee::where('id', $visit->created_by)
                ->whereNotNull('fcm_token')
                ->first();

            if (!$employee) continue;

            $pushService->sendNotification(
                $employee->fcm_token,
                'Influencer Follow-Up Reminder',
                'You have an influencer visit follow-up today.',
                'employees',
                [
                    'visit_id' => (string) $visit->id,
                    'name' => (string) $visit->influencer_name,
                ]
            );

           
        }
    }

    private function sendLeadFollowups($today, $pushService)
    {
        $leads = Lead::with('createdBy')
            ->whereDate('follow_up_date', $today)
            ->whereNotNull('created_by')
            ->get();

        foreach ($leads as $lead) {

            $employee = $lead->createdBy;

            if (!$employee || !$employee->fcm_token) continue;

            $pushService->sendNotification(
                $employee->fcm_token,
                'Lead Follow-Up Reminder',
                'You have a lead follow-up scheduled for today.',
                'employees',
                [
                    'lead_id'  => (string) $lead->id,
                    'customer' => (string) $lead->customer_name,
                ]
            );
        }
    }

}
