<?php

namespace App\Notifications;

use App\Models\Logbook;
use Illuminate\Notifications\Notification;

/**
 * In-app message (the bell in the top bar) when an entry is submitted, approved or sent back.
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
        return ['database'];
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
