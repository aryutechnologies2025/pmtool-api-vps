<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentDetails extends Model
{
    use HasFactory;

    protected $guarded = [];


    protected $table = 'payment_details';
    // protected $casts = [
    //     'reference_number_file' => 'array',
    //     // 'reference_number' => 'array',
    // ];




    protected $fillable = [
        'payment_id',
        'payment',
        'payment_type',
        'payment_date',
        'reference_number',
        'reference_number_file'
    ];

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


    public function paymentProcess()
    {
        return $this->belongsTo(PaymentStatusModel::class, 'payment_id', 'id');
    }
}
