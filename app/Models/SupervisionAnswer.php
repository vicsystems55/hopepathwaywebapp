<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupervisionAnswer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function questions(){

        return $this->belongsTo(SupervisionQuestion::class, 'supervision_question_id', 'id');
    }
}
