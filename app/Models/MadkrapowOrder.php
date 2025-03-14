<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MadkrapowOrder extends Model
{
    use HasFactory;

    protected $table = 'madkrapow_orders';
    protected $primaryKey = 'order_id'; // Changed from 'id' to 'order_id'
    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'order_date',
        'total_amount',
        'status',
        'shipping_cost',
        'points_awarded',
        'points_awarded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'order_date' => 'datetime',
        'points_awarded' => 'boolean',
        'points_awarded_at' => 'datetime',
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
        return $this->hasMany(MadkrapowOrderItem::class, 'order_id', 'order_id'); // Changed from 'id' to 'order_id'
    }

    /**
     * Get the shipping for the order.
     */
    public function shipping()
    {
        return $this->hasOne(MadkrapowShipping::class, 'order_id', 'order_id'); // Changed from 'id' to 'order_id'
    }

    /**
     * Get the payment for the order.
     */
    public function payment()
    {
        return $this->hasOne(MadkrapowPayment::class, 'order_id', 'order_id'); // Changed from 'id' to 'order_id'
    }
}
