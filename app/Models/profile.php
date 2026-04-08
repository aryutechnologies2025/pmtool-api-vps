<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profile';
    protected $fillable = [
      
        'full_name',
        'email',
        'password',
        'dob',
        'permanent_address',
        'present_address',
        'city',
        'post_code',
        'country',
        'profile_image'
    ];

    public function user()
    {
        return $this->belongsTo(RegisterModel::class, 'email', 'email');
    }
}
