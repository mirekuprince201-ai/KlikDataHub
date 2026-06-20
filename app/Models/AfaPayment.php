<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfaPayment extends Model
{
    protected $table = 'afa_payments';

    protected $primaryKey = 'payment_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = [
        'amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'webhook_received_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(AfaOrder::class, 'order_id', 'order_id');
    }
}