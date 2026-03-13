<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'is_gst',
        'invoice_no',
        'created_by',
        'created_date',
        'invoice_doc',
        'payment_status',
        'due_date',
    ];


    public function project()
    {
        return $this->belongsTo(EntryProcessModel::class, 'project_id');
    }
}
