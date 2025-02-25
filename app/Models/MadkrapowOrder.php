<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MadkrapowOrder extends Model
{
    use HasFactory;

    protected $table = 'madkrapow_order';
    protected $primaryKey = 'order_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'date_modified',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'date_modified' => 'datetime',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user()
    {
        return $this->belongsTo(MadkrapowUser::class, 'user_id', 'user_id');
    }

    /**
     * Get the order items for the order.
     */
    public function orderItems()
    {
        return $this->hasMany(MadkrapowOrderItem::class, 'order_id', 'order_id');
    }

    /**
     * Get the shipping for the order.
     */
    public function shipping()
    {
        return $this->hasOne(MadkrapowShipping::class, 'order_id', 'order_id');
    }

    /**
     * Get the payment for the order.
     */
    public function payment()
    {
        return $this->hasOne(MadkrapowPayment::class, 'order_id', 'order_id');
    }
}
