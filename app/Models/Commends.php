<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commends extends Model
{
    use HasFactory;

    protected $table='comments';

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1');
    }

    public function assignByUser()
    {
        return $this->belongsTo(User::class, 'assignee', 'id')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1');
    }

}
