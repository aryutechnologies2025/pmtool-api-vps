<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PaymentDetails;
use App\Models\EntryProcessModel;
use Illuminate\Support\Facades\DB;

class PaymentStatusModel extends Model
{
    use HasFactory;
    protected $table = 'payment_processes';
    // protected $casts = [
    //     'reference_number_file' => 'array',
    //     // 'reference_number' => 'array',
    // ];

    public function setReferenceNumberFileAttribute($value)
{
    if (is_array($value)) {
        $this->attributes['reference_number_file'] = json_encode($value);
        return;
    }

    if (is_string($value)) {
        $this->attributes['reference_number_file'] = json_encode([$value]);
        return;
    }

    $this->attributes['reference_number_file'] = json_encode([]);
}

public function getReferenceNumberFileAttribute($value)
{
    if (!$value) {
        return [];
    }

    if (is_array($value)) {
        return $value;
    }

    $decoded = json_decode($value, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    return [$value];
}


    public function paymentData()
    {
        return $this->hasMany(PaymentDetails::class, 'payment_id', 'id')
            ->select('id', 'payment_id', 'payment','payment_type', 'payment_date','reference_number','reference_number_file')
            ->where('is_deleted', 0);
    }

    public function paymentData1()
    {
        return $this->hasMany(PaymentStatusModel::class, 'project_id', 'id')
            ->select('id', 'project_id', 'payment_status', 'created_by');
            
    }

    public function paymentLData()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'payment_id', 'id')
            ->select('id', 'project_id', 'payment_id', 'employee_id', 'payment', 'payment_date', 'type', 'status', 'created_date');
    }

    public function paymentWEmpData()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'payment_id', 'id')
        ->select('id', 'project_id', 'payment_id', 'employee_id', 'payment', 'payment_date', 'type', 'status', 'created_date')
        ->where('type','writer');
    }
    public function paymentREmpData()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'payment_id', 'id')
        ->select('id', 'project_id', 'payment_id', 'employee_id', 'payment', 'payment_date', 'type', 'status', 'created_date')
        ->where('type','reviewer');
    }

    public function paymentSEmpData()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'payment_id', 'id')
        ->select('id', 'project_id', 'payment_id', 'employee_id', 'payment', 'payment_date', 'type', 'status', 'created_date')
        ->where('type','statistican');
    }

    public function paymentJEmpData()
    {
        return $this->hasMany(EmployeePaymentDetails::class, 'payment_id', 'id')
        ->select('id', 'project_id', 'payment_id', 'employee_id', 'payment', 'payment_date', 'type', 'status', 'created_date')
        ->where('type','publication_manager');
    }


    public function paymentLog()
    {
        return $this->hasMany(PaymentLogs::class, 'payment_id', 'id')->select('id', 'project_id', 'payment_id', 'payment_status','created_by','created_date');
    }

    public function projectData()
    {
        return $this->hasOne(EntryProcessModel::class, 'id', 'project_id')
        //  ->with(['institute','department','profession'])
        ->select('id','entry_date','title','project_id','contact_number','type_of_work','email','client_name','institute','department','profession','budget','process_status','hierarchy_level','created_by','project_status','assign_by','assign_date','is_deleted',DB::raw("CONCAT(DATEDIFF(projectduration, created_at), ' days ', MOD(TIMESTAMPDIFF(HOUR, created_at, projectduration), 24), ' hrs') AS projectduration"))
            ->with(['writerData', 'reviewerData', 'statisticanData','journalData','instituteInfo','departmentInfo','professionInfo'])
            ->where('is_deleted', 0);
    }

    public function userData()
    {
        return $this->hasOne(User::class, 'id', 'created_by')
            ->select('id', 'employee_name', 'employee_type', 'position', 'department', 'date_of_joining', 'phone_number', 'email_address', 'profile_image')
            ->where('status','1')
            ->with('createdByUser');
    }

    public function projectData1()
    {
        return $this->hasOne(EntryProcessModel::class, 'id', 'project_id')
        ->with(['userData1',])
        ->select('id','entry_date','title','project_id','contact_number','type_of_work','email','client_name','institute','department','profession','budget','process_status','hierarchy_level','created_by','project_status','assign_by','assign_date','projectduration');
    }

}
