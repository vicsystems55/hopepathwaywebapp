<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $guarded = [];


    public function documents()
    {
        # code...

        return $this->hasMany(PolicyDocument::class, 'policy_id', 'id');
    }
}
