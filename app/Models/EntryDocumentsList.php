<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntryDocumentsList extends Model
{
    use HasFactory;

    protected $table ="entry_documents_list";


    protected $fillable = ['document_id', 'file'];

  
}
