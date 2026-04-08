<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Profile;

class RegisterModel extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $table = 'registrations'; // Assuming your table is 'registrations'
    protected $fillable = ['full_name', 'email', 'password', 'role', 'confirm_password'];

    // Relationship with Profile
    public function profile()
    {
        return $this->hasOne(Profile::class, 'email', 'email');
    }
}
