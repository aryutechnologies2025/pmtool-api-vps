<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeModel extends Model
{
    use HasFactory;
    protected $table = 'add_staffs';
    protected $Fillable = [
        'first_name',
        'last_name',
        'emailID',
        'phone',
        'address',
        'role',
        'username',
        'password',
        'status',
        'created_by',
        'is_deleted'
    ];
}
