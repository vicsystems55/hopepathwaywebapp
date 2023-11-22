<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\StaffSupervisionSchedule;
use App\Mail\AdminSupervisionReminderMail;
use App\Mail\StaffSupervisionReminderMail;

class CheckReminders extends Command
{
    protected $signature = 'reminders:check {no_days}';
    protected $description = 'Check for due reminders for supervision and DBS expiration';



    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $days = $this->argument('no_days');

        $allDueToday = StaffSupervisionSchedule::
        with('staff')
        ->where('staff_reminder', false)
        ->whereYear('next_supervision_date', Carbon::now()->year)
        ->whereDay('next_supervision_date', Carbon::today()->addDays($days))->get();

        // loop over the campaigsn
        foreach ($allDueToday as $dueToday) {
            # code...

            $datax =[
                'name' => $dueToday->staff->fullname,
                'no_days_time' =>  Carbon::today()->diffInDays($dueToday->next_supervision_date),
                'type' => 'Supervision Reminder'

            ];

            // send email to each staff
            try {
                //code...

            Mail::to($dueToday->staff->email)->send(new StaffSupervisionReminderMail($datax));

            // send email to admin

            Mail::to('admin@hopepathway.co.uk')->send(new AdminSupervisionReminderMail($datax));

            } catch (\Throwable $th) {
                //throw $th;
            }

            // notify admin
            Notification::create([
                'user_id' => 1,
                'subject' => 'Upcomming Staff Supervision',
                'msg' => $dueToday->staff->fullname.', is due for supervision in:  '.$datax['no_days_time'].' days.',
            ]);

            // notify staff

            Notification::create([
                'user_id' => $dueToday->id,
                'subject' => 'Upcomming Staff Supervision',
                'msg' => 'You are scheduled for supervision review in '.$datax['no_days_time'].' days.',
            ]);


            // update the status from unsent to sent
            $dueToday->update([
                'staff_reminder' => true,
                'admin_reminder' => true
            ]);
        }



        return 0;
    }
}
