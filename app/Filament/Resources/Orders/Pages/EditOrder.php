<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Mail\OrderShipped;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Order $order */
        $order = $this->record;

        return [
            Action::make('markShipped')
                ->label('Markera som skickad')
                ->icon('heroicon-m-truck')
                ->color('info')
                ->visible(fn () => in_array($order->status, [Order::STATUS_PAID, Order::STATUS_PENDING], true))
                ->form([
                    TextInput::make('tracking_number')
                        ->label('Spårningsnummer')
                        ->default(fn () => $order->tracking_number),
                    TextInput::make('tracking_url')
                        ->label('Spårnings-URL')
                        ->url()
                        ->default(fn () => $order->tracking_url),
                    Toggle::make('notify_customer')
                        ->label('Mejla kunden')
                        ->default(true),
                ])
                ->action(function (array $data) use ($order) {
                    $order->update([
                        'tracking_number' => $data['tracking_number'] ?? null,
                        'tracking_url' => $data['tracking_url'] ?? null,
                        'status' => Order::STATUS_SHIPPED,
                        'shipping_status' => 'shipped',
                        'shipped_at' => now(),
                    ]);

                    if (! empty($data['notify_customer'])) {
                        try {
                            Mail::to($order->email)->send(new OrderShipped($order->fresh()));
                            Notification::make()->title('Order markerad som skickad och mejl skickat')->success()->send();
                        } catch (\Throwable $e) {
                            report($e);
                            Notification::make()->title('Order markerad som skickad, men mejlet kunde inte skickas')->warning()->send();
                        }
                    } else {
                        Notification::make()->title('Order markerad som skickad')->success()->send();
                    }

                    $this->refreshFormData(['status', 'shipping_status', 'tracking_number', 'tracking_url', 'shipped_at']);
                }),

            Action::make('markDelivered')
                ->label('Markera som levererad')
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn () => $order->status === Order::STATUS_SHIPPED)
                ->requiresConfirmation()
                ->action(function () use ($order) {
                    $order->update([
                        'status' => Order::STATUS_DELIVERED,
                        'shipping_status' => 'delivered',
                        'delivered_at' => now(),
                    ]);
                    Notification::make()->title('Order markerad som levererad')->success()->send();
                    $this->refreshFormData(['status', 'shipping_status', 'delivered_at']);
                }),

            Action::make('cancelOrder')
                ->label('Avbryt order')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn () => ! in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_DELIVERED], true))
                ->requiresConfirmation()
                ->modalDescription('Detta avbryter ordern. Återbetalning hanteras manuellt i din betalleverantörs portal.')
                ->action(function () use ($order) {
                    $order->update([
                        'status' => Order::STATUS_CANCELLED,
                        'shipping_status' => 'not_shipped',
                    ]);
                    Notification::make()->title('Order avbruten')->success()->send();
                    $this->refreshFormData(['status', 'shipping_status']);
                }),

            DeleteAction::make(),
        ];
    }
}
