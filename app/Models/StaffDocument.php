<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffDocument extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = [
        'file_path',
    ];

    protected $casts = [
        'issued_on' => 'date:Y-m-d',
        'expires_on' => 'date:Y-m-d',
        'file_size' => 'integer',
    ];

    public function staffRecord()
    {
        return $this->belongsTo(StaffRecord::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
