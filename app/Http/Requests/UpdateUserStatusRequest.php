<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->hasPermission('users.manage');
    }

    public function rules()
    {
        return [
            'is_active' => 'required|boolean',
        ];
    }
}
