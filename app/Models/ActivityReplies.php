<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityReplies extends Model
{
    use HasFactory;

    protected $table='activity_replies';

    // Relationship to Activity
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id')->with(['activityData']);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1')
        ->with(['createdByUser']);
    }
}
