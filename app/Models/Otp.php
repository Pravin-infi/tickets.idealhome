<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class Otp extends Model
{
    use HasFactory, Prunable;

    protected $guarded = [];

    protected $primaryKey = null;

    // Disable auto-incrementing of primary key
    public $incrementing = false;


    /**
     * Get the prunable model query.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->daily());
    }
}
