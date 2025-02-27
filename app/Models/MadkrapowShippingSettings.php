<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MadkrapowShippingSettings extends Model
{
    use HasFactory;

    protected $table = 'madkrapow_shipping_settings';
    
    protected $fillable = [
        'standard_delivery_days',
        'standard_delivery_cost',
        'express_delivery_days',
        'express_delivery_cost',
        'free_shipping_threshold',
        'handling_time',
        'same_day_available',
        'same_day_cutoff',
        'return_window_days',
        'free_returns',
        'return_policy'
    ];
}
