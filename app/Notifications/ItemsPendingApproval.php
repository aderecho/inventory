<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ItemsPendingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $items,
        public string $category,
        public string $documentType,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage(array_merge($this->payload(), [
            'created_at' => now()->toIso8601String(),
        ]));
    }

    public function toMail($notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject("Pending Item Acknowledgement — {$this->documentType} {$this->category}")
            ->view('emails.items-pending-approval', [
                'user' => $notifiable,
                'items' => array_slice($this->items, 0, 10),
                'totalItems' => count($this->items),
                'category' => $this->category,
                'documentType' => $this->documentType,
            ]);
    }

    protected function payload(): array
    {
        $count = count($this->items);

        return [
            'title'             => 'Pending Item Acknowledgement',
            'notification_type' => 'items_pending_approval',
            'category'          => $this->category,
            'document_type'     => $this->documentType,
            'item_count'        => $count,
            'items'             => $this->items,
            'message'           => "You have {$count} item(s) pending your acknowledgement under {$this->documentType} {$this->category}.",
        ];
    }
}
