<?php

namespace App\Notifications;

use App\Models\Semester;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a student that this week's entry is due today and hasn't been written yet. */
class DeadlineReminder extends Notification
{
    public function __construct(private Semester $semester, private int $week) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmail() ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Reminder: week {$this->week} logbook is due today · ".config('app.name'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Your week {$this->week} logbook entry is due tonight ({$this->semester->dueAt($this->week)->format('l, j F Y')}).")
            ->line('Entries written after the deadline are marked "Late".')
            ->action('Write it now', route('logbooks.create'))
            ->salutation('Digital LogBook+ · Universiti Kebangsaan Malaysia');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'reminder',
            'message' => "Week {$this->week} is due tonight. Write your entry before midnight.",
            'logbook_id' => null,
            'week_no' => $this->week,
        ];
    }
}
