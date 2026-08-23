<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateStaffLoginRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->hasPermission('users.manage');
    }

    public function rules()
    {
        return [];
    }
}
