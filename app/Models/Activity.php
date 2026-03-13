<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    protected $table ='activities';

    public function activityData()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id')
        ->select('id','entry_date','title','project_id','type_of_work','email','institute','department','profession','budget','process_status','hierarchy_level','created_by','project_status','assign_by','assign_date','projectduration');
    }
    

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
        ->with(['createdByUser'])
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image');
        // ->where('status','1');
    }

    public function createdByUsers()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
        ->with(['createdUserP'])
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image');
        // ->where('status','1');
       
    }

    public function replies()
    {
        return $this->hasMany(ActivityReplies::class, 'activity_id', 'id')
        ->with(['createdByUser']);
    }

    public function repliesd()
    {
        return $this->hasMany(ActivityReplies::class, 'activity_id', 'id')
        ->with(['createdByUser']);
    }

    public function file()
    {
        return $this->hasMany(ActivityDocuments::class, 'activity_id')
        ->select('id','activity_id','files','original_name');
    }
}
