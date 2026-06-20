<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AfaOrder extends Model
{
    protected $table = 'afa_orders';

    protected $primaryKey = 'order_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'user_id',
        'afa_registration_id',
        'order_reference',
        'registration_name',
        'amount',
        'customer_name',
        'customer_email',
        'phone_number',
        'ghana_card_number',
        'location',
        'occupation',
        'status',
        'admin_notes',
        'ordered_at',
        'processing_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'processing_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function payment()
    {
        return $this->hasOne(AfaPayment::class, 'order_id', 'order_id');
    }
}