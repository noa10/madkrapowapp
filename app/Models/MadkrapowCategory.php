<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MadkrapowCategory extends Model
{
    use HasFactory;

    protected $fillable = ['category_name'];

    public function products(): HasMany
    {
        return $this->hasMany(MadkrapowProduct::class, 'category_id');
    }
}
