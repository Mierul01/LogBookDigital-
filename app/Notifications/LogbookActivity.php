<?php

namespace App\Notifications;

use App\Models\Logbook;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when an entry is submitted, approved, sent back or resent: the bell in the top bar,
 * plus an email for users who have an address and haven't turned emails off.
 */
class LogbookActivity extends Notification
{
    public function __construct(
        private Logbook $logbook,
        private string $kind,
        private string $message,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $notifiable->wantsEmail() ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->kind) {
            'approved' => "Week {$this->logbook->week_no} approved",
            'revision' => "Week {$this->logbook->week_no} needs fixing",
            'resubmitted' => "Week {$this->logbook->week_no} resent for review",
            default => "New logbook entry: week {$this->logbook->week_no}",
        };

        $mail = (new MailMessage)
            ->subject($subject.' · '.config('app.name'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->message);

        if ($this->kind === 'revision' && $this->logbook->supervisor_comment) {
            $mail->line('Supervisor\'s comment: "'.$this->logbook->supervisor_comment.'"');
        }

        return $mail
            ->action('Open the entry', route('logbooks.show', $this->logbook))
            ->salutation('Digital LogBook+ · Universiti Kebangsaan Malaysia');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'message' => $this->message,
            'logbook_id' => $this->logbook->id,
            'week_no' => $this->logbook->week_no,
        ];
    }
}
