<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class staff extends Authenticatable
{
    use HasApiTokens;

    protected $fillable =
        [
            'name',
            'email',
            'password',
            'phone',
            'position',
            'department',
        ];

    protected $hidden =
        [
            'password',
            'remember_token',
        ];

    protected $casts =
        [
            'password' => 'hashed',
        ];
}
