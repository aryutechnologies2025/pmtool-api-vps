<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectLogs extends Model
{
    use HasFactory;

    protected $table='projectlogs';

    protected $fillable = [
        'project_id',  
        'employee_id',
        'assigned_date',
        'status',
        'status_date',
        'status_type',
        'created_by',
        'created_date',
        'assing_preview_id'
    ];

    public function entryProcess()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id')
        ->with([
            'writerData',
            'reviewerData',
            'statisticanData',
            'paymentProcess',
            'journalData',
        ])
        ->select('id','entry_date','title','project_id','type_of_work','email','institute','department','profession','budget','process_status','hierarchy_level','created_by','project_status','assign_by','assign_date','projectduration');
    }

    public function userData()
    {
        return $this->hasOne(User::class, 'id', 'employee_id')
            ->select('id', 'employee_name', 'employee_type', 'position', 'department', 'date_of_joining', 'phone_number', 'email_address', 'profile_image')
            ->where('status','1')
            ->with('createdByUser');
    }

    public function rejectReasons()
{
    return $this->hasMany(RejectReason::class, 'project_id', 'project_id');
}

}
