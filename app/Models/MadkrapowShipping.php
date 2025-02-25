<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MadkrapowShipping extends Model
{
    use HasFactory;

    protected $table = 'madkrapow_shipping';
    protected $primaryKey = 'shipping_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'shipping_date',
        'delivery_method',
        'status',
        'shipping_address',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'shipping_date' => 'datetime',
    ];

    /**
     * Get the order that owns the shipping.
     */
    public function order()
    {
        return $this->belongsTo(MadkrapowOrder::class, 'order_id', 'order_id');
    }
}
