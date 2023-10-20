<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Office extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function parent(){
        return $this->belongsTo(Office::class,'parent_id', 'id');
    }

    public function officer(){
        return $this->belongsTo(User::class,'user_id', 'id');
    }
}
