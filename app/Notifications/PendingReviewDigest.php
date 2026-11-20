<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Monday summary for a supervisor: how many entries are waiting for review. */
class PendingReviewDigest extends Notification
{
    public function __construct(private int $pending, private int $oldestDays) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmail() ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->pending} logbook ".str('entry')->plural($this->pending).' waiting for you · '.config('app.name'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message())
            ->action('Review entries', route('logbooks.index', ['status' => 'pending']))
            ->salutation('Digital LogBook+ · Universiti Kebangsaan Malaysia');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return ['kind' => 'digest', 'message' => $this->message(), 'logbook_id' => null, 'week_no' => null];
    }

    private function message(): string
    {
        return "{$this->pending} ".str('entry')->plural($this->pending).' from your students '
            .($this->pending === 1 ? 'is' : 'are').' waiting for your review'
            .($this->oldestDays > 0 ? "; the oldest has waited {$this->oldestDays} ".str('day')->plural($this->oldestDays).'.' : '.');
    }
}
