<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MadkrapowReview extends Model
{
    use HasFactory;

    protected $table = 'madkrapow_review';
    protected $primaryKey = 'review_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'comment',
        'review_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rating' => 'integer',
        'review_date' => 'datetime',
    ];

    /**
     * Get the user that owns the review.
     */
    public function user()
    {
        return $this->belongsTo(MadkrapowUser::class, 'user_id', 'user_id');
    }

    /**
     * Get the product that owns the review.
     */
    public function product()
    {
        return $this->belongsTo(MadkrapowProduct::class, 'product_id', 'product_id');
    }
}
