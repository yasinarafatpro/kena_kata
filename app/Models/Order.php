<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['user_id', 'total_amount', 'currency', 'status', 'payment_method', 'transaction_id', 'shipping_address'];
    protected $casts = ['status' => OrderStatus::class, 'shipping_address' => 'array'];
    protected $appends = ['allowed_next'];

    public function user()            { return $this->belongsTo(User::class); }
    public function items()           { return $this->hasMany(OrderItem::class); }
    public function statusHistories() { return $this->hasMany(OrderStatusHistory::class)->latest('id'); }

    public function getAllowedNextAttribute(): array
    {
        return collect(OrderStatus::cases())
            ->filter(fn ($s) => $this->status->canTransitionTo($s))
            ->map(fn ($s) => $s->value)->values()->all();
    }

    public function amountMinor(): int
    {
        return (int) round(((float) $this->total_amount) * 100);
    }

    public function transitionTo(OrderStatus $to, ?string $note = null): void
    {
        if (! $this->status->canTransitionTo($to)) {
            throw new \DomainException("Cannot move order from {$this->status->value} to {$to->value}.");
        }
        $this->update(['status' => $to]);
        $this->statusHistories()->create(['status' => $to->value, 'note' => $note]);
    }
}
