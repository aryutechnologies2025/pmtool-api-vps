<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentLogs extends Model
{
    use HasFactory;

    protected $table ='payment_logs';
  

    protected $guarded = [];
    protected $casts = [
    'reference_number_file' => 'array',
    'reference_number' => 'array',
    ];

}
