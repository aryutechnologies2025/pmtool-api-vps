<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'mysql_medics_hrms';
    protected $table = 'employee_details';


    protected $fillable = [
        'email_address',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function getAuthIdentifierName()
    {
        return 'email_address';
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function createdByUser()
    {
        return $this->belongsTo(Roles::class, 'position', 'id');
    }


    public function createdUserP()
    {
        return $this->belongsTo(Roles::class, 'position', 'id');
    }
}
