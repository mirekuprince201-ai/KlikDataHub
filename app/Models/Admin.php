<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $table = 'admins';

    protected $primaryKey = 'admin_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'admin_id',
        'admin_code',
        'first_name',
        'last_name',
        'other_name',
        'username',
        'email',
        'phone',
        'profile_image',
        'password',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($admin) {

            if (empty($admin->admin_id)) {
                $admin->admin_id = (string) Str::uuid();
            }

            if (empty($admin->admin_code)) {

                $lastAdmin = self::count() + 1;

                $admin->admin_code = 'KDH-ADM-' .
                    now()->format('y') .
                    str_pad($lastAdmin, 4, '0', STR_PAD_LEFT);
            }
        });
    }
}