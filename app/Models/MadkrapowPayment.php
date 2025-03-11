<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MadkrapowPayment extends Model
{
    use HasFactory;

    protected $table = 'madkrapow_payments';
    protected $primaryKey = 'payment_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'payment_date',
        'amount',
        'payment_method',
        'status',
        'reference_id',
        'transaction_details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
        'transaction_details' => 'array',
    ];

    /**
     * Get the order that owns the payment.
     */
    public function order()
    {
        return $this->belongsTo(MadkrapowOrder::class, 'order_id', 'order_id');
    }
}
