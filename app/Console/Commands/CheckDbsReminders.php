<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Notification;
use App\Models\StaffDbsRenewal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\AdminSupervisionReminderMail;
use App\Mail\StaffSupervisionReminderMail;

class CheckDbsReminders extends Command
{
    protected $signature = 'reminders:dbscheck';
    protected $description = 'Check for due DBS expiration';
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

        $allDueThisYear = StaffDbsRenewal::
        with('staff')
        ->where('staff_reminder', false)
        ->whereYear('dbs_renewal_date', Carbon::now()->year)
        ->whereDay('dbs_renewal_date', Carbon::today())->get();

        // loop over the campaigsn
        foreach ($allDueThisYear as $thisYear) {
            # code...

            $datax =[
                'name' => $thisYear->fullname,
                'no_days_time' =>  Carbon::today()->diffInDays($thisYear->next_supervision_date),
                'type' => 'DBS Reminder'

            ];

            // send email to each staff

            Mail::to($thisYear->email)->send(new StaffSupervisionReminderMail($datax));

            // send email to admin

            Mail::to('admin@hopepathway.co.uk')->send(new AdminSupervisionReminderMail($datax));


            // notify admin
            Notification::create([
                'user_id' => 1,
                'subject' => 'DBS Renewal Notice',
                'msg' => $thisYear->fullname.', is due for renewal in:  '.$datax['no_days_time'].'.',
            ]);

            // notify staff

            Notification::create([
                'user_id' => $thisYear->id,
                'subject' => 'Upcomming Staff DBS Renwal',
                'msg' => 'Your DBS data should be reviewed in '.$datax['no_days_time'].'.',
            ]);


            // update the status from unsent to sent
            $thisYear->update([
                'reminder_staff' => true,
                'admin_reminder' => true
            ]);
        }

        return 0;
    }
}
