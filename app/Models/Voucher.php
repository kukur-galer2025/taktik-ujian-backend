<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    public function calculateDiscount(int $amount): int
    {
        if ($amount < $this->min_purchase) return 0;
        
        if ($this->discount_type === 'percentage') {
            return (int) round($amount * $this->discount_value / 100);
        }
        return min($this->discount_value, $amount);
    }
}
