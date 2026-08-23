<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOwnStaffProfileRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->role === User::ROLE_STAFF;
    }

    public function rules()
    {
        return [
            'address' => 'nullable|string|max:500',
            'phone_number' => 'nullable|string|max:40',
        ];
    }
}
