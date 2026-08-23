<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LinkStaffAccountRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->hasPermission('users.manage');
    }

    public function rules()
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    return $query->where('role', User::ROLE_STAFF);
                }),
            ],
        ];
    }
}
