<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResubmittedForm extends Model
{
    use HasFactory;
    protected $table = 'resubmission_form';
    protected $fillable =[
        'project_id','date_of_rejected','date_of_submission','article_id','journal_name','review','created_by' 
    ];
    
    public function EntryProcess(){
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id');
    }
}