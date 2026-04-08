<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePaymentDetails extends Model
{
    use HasFactory;

    protected $table = 'employee_payment_details';

    protected $fillable = [
        'project_id',
        'employee_id',
        'payment',
        'payment_date',
        'status',
        'created_date',
        'payment_id',
        'type'
    ];

    public function UserDateF()
    {
        return $this->hasOne(User::class, 'id', 'employee_id')
        ->where('employee_type','freelancers')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1')
        ->with('createdByUser');
    }

    public function entryProcess()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id')
            ->select('id', 'entry_date', 'title', 'project_id', 'type_of_work','process_status');
            

    }

    public function projectData()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id')
            ->select('id', 'entry_date', 'title', 'project_id', 'type_of_work','process_status');
            

    }

  
  public function UserDate()
    {
        return $this->hasOne(User::class, 'id', 'employee_id')
        ->select('id','employee_name','employee_type','position')
        ->where('status','1')
        ->with('createdByUser');
    }

}
