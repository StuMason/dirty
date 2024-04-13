<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stack extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'env',
        'env_key',
        'bucket',
        'region',
        'account',
        'function_name_artisan',
        'function_name_web',
        'function_name_worker',
        'distribution_url',
        'queue_name',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'env_key',
    ];

    
    /**
     * Get the user that owns the stack.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
