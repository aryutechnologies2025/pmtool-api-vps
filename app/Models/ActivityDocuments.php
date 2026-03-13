<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityDocuments extends Model
{
    use HasFactory;

    protected $table='activity_documents';

    public function entryProcessModel()
    {
        return $this->belongsTo(EntryProcessModel::class, 'entry_process_model_id')
        ->select('id','entry_date','title','project_id','type_of_work','email','institute','department','profession','budget','process_status','hierarchy_level','created_by','project_status','assign_by','assign_date','projectduration');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by', 'id')
        ->select('id','employee_name','employee_type','position','department','date_of_joining','phone_number','email_address','profile_image')
        ->where('status','1');
    }


    public function file()
    {
        return $this->hasMany(EntryDocumentsList::class, 'document_id')
        ->select('id','document_id','file','original_name')
        ->where('is_deleted', 0);
    }
}
