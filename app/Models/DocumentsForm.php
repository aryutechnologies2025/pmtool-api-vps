<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentsForm extends Model
{
    use HasFactory;
    protected $table ='document_author_forms';
    protected $fillable = [
        'project_id', 'title', 'file' , 'created_by','is_deleted'
    ];
    
    public function EntryProcess(){
        return $this->belongsTo(EntryProcessModel::class, 'project_id', 'id');
    }
    
    public function files(){
        return $this->hasMany(DocumentFile::class, 'document_id');
    }
}