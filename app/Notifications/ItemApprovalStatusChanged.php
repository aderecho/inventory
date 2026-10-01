<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ItemApprovalStatusChanged extends Notification
{
    public function __construct(
        public array $items,
        public string $status,
        public string $approvedBy,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Inventory Approvals',
            'status' => $this->status,
            'approved_by' => $this->approvedBy,
            'item_count' => count($this->items),
            'items' => $this->items,
            'notification_type' => 'approval_status_changed',
            'message' => "{$this->approvedBy} {$this->status} " . count($this->items) . " inventory item(s).",
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            'title' => 'Inventory Approvals',
            'status' => $this->status,
            'approved_by' => $this->approvedBy,
            'item_count' => count($this->items),
            'items' => $this->items,
            'notification_type' => 'approval_status_changed',
            'message' => "{$this->approvedBy} {$this->status} " . count($this->items) . " inventory item(s).",
            'created_at' => now()->toIso8601String(),
        ]);
    }
}
