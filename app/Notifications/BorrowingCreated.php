<?php

namespace App\Notifications;

use App\Models\ItemBorrowing;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells both sides of a loan that it happened: the borrower and the staff
 * member who released the items. Each side gets its own wording.
 */
class BorrowingCreated extends Notification
{
    public function __construct(
        public ItemBorrowing $borrowing,
        public string $audience,
        public string $counterpart,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage(array_merge($this->payload(), [
            'created_at' => now()->toIso8601String(),
        ]));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Borrowing {$this->borrowing->reference_code} — {$this->headline()}")
            ->view('emails.borrowing-created', [
                'user'        => $notifiable,
                'borrowing'   => $this->borrowing,
                'items'       => $this->borrowing->items,
                'audience'    => $this->audience,
                'counterpart' => $this->counterpart,
                'headline'    => $this->headline(),
                'intro'       => $this->message(),
            ]);
    }

    protected function payload(): array
    {
        $count = $this->borrowing->items->count();

        return [
            'title'             => $this->headline(),
            'notification_type' => 'borrowing_created',
            'reference_code'    => $this->borrowing->reference_code,
            'item_count'        => $count,
            'items'             => $this->borrowing->items
                ->map(fn ($line) => [
                    'id'              => $line->inventory_item_id,
                    'item_name'       => $line->inventoryItem?->item_name,
                    'property_number' => $line->inventoryItem?->property_number,
                ])
                ->values()
                ->all(),
            'message'           => $this->message(),
            'url'               => route('borrowings.index'),
        ];
    }

    protected function headline(): string
    {
        return match ($this->audience) {
            'borrower'    => 'Items borrowed',
            'released_by' => 'Items released',
            default       => 'Borrowing recorded',
        };
    }

    protected function message(): string
    {
        $count = $this->borrowing->items->count();
        $items = $count . ' item' . ($count === 1 ? '' : 's');
        $reference = $this->borrowing->reference_code;

        return match ($this->audience) {
            'borrower'    => "You borrowed {$items} from {$this->counterpart} — reference {$reference}.",
            'released_by' => "You released {$items} to {$this->counterpart} — reference {$reference}.",
            default       => "{$items} recorded under reference {$reference}.",
        };
    }
}

