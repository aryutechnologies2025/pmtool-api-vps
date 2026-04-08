<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProjectAssignDetails extends Model
{
    use HasFactory;

    protected $table = "project_assign_details";


    protected $fillable = [
        'project_id',
        'assign_user',
        'status',
        'type',
        'created_by',
        'is_deleted',
        'type_sme'
    ];


    public function projectData()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id')
            ->with([
                'writerData',
                'reviewerData',
                'statisticanData',
                'paymentProcess',
                'journalData',
                'journalPaymentDetails',
                'employeePaymentDetails',
                'tcData',
                'smeData',
                'projectAcceptStatust',
                'employee_rejected'
            ])
            ->select('id', 'entry_date', 'title', 'project_id', 'type_of_work', 'email', 'institute', 'department', 'profession', 'budget', 'process_status', 'hierarchy_level', 'created_by', 'project_status', 'assign_by', 'assign_date','client_name', DB::raw("CONCAT(DATEDIFF(projectduration, entry_date), ' days') AS projectduration"));
            // ->select('id', 'entry_date', 'title', 'project_id', 'type_of_work', 'email', 'institute', 'department', 'profession', 'budget', 'process_status', 'hierarchy_level', 'created_by', 'project_status', 'assign_by', 'assign_date','client_name', DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration"));
    }

    public function projectDatas()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id','id')
            
            ->select('id', 'entry_date', 'title', 'project_id', 'type_of_work', 'email', 'institute', 'department', 'profession', 'budget', 'process_status', 'hierarchy_level', 'created_by', 'project_status', 'assign_by', 'assign_date', 'projectduration','client_name')
            ->with ([
               
                'journalData',
                
            ]);
        }

    public function documents()
    {
        return $this->hasMany(EntryDocument::class, 'entry_process_model_id', 'project_id')
            ->select('id', 'entry_process_model_id', 'select_document', 'file', 'created_by');
    }


    public function paymentProcess()
    {
        return $this->hasMany(PaymentStatusModel::class, 'project_id', 'project_id')
            ->select('id', 'project_id', 'payment_status', 'created_by');
           
    }

   

  



    

    public function UserDate()
    {
        return $this->hasOne(User::class, 'id', 'assign_user')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        // ->where('status','1')
        ->with('createdByUser');
    }

    public function UserDateF()
    {
        return $this->hasOne(User::class, 'id', 'assign_user')
        ->where('employee_type','freelancers')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        // ->where('status','1')
        ->with('createdByUser');
    }

    public function employee_rejected()
    {
        return $this->hasMany(ProjectLogs::class, 'project_id', 'project_id');
                  
    }

    public function employeePaymentDetails()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'id');
            
    }

    public function rejectReasons()
{
    return $this->hasMany(RejectReason::class, 'project_id', 'project_id');
}

}




