<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    use HasFactory;
    protected $table = 'project_status';

    protected $fillable = [
        'project_id', 
        'assign_id', 
        'status'
    ];

    public function writer()
    {
        return $this->hasOne(User::class, 'id', 'writer_id')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1')
        ->with('createdByUser');
    }

    public function reviewer()
    {
        return $this->hasOne(User::class, 'id', 'reviewer_id')->with('createdByUser')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1');
    }

    public function journal()
    {
        return $this->hasOne(User::class, 'id', 'journal_id')->with('createdByUser')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1'); 
    }

    public function statistican()
    {
        return $this->hasOne(User::class, 'id', 'statistican_id')->with('createdByUser')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1');
    }

    public function userData()
    {
        return $this->hasOne(User::class, 'id', 'assign_id')->with('createdByUser')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1');
    }

   public function rejectReasons()
{
    return $this->hasMany(RejectReason::class, 'project_id', 'project_id');
}


}
