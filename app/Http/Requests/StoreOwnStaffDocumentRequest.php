<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreOwnStaffDocumentRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->role === User::ROLE_STAFF;
    }

    public function rules()
    {
        return [
            'type' => 'required|in:identity,dbs,qualification,course_certificate,training,other',
            'title' => 'required|string|max:255',
            'course_name' => 'nullable|required_if:type,course_certificate|string|max:255',
            'issued_on' => 'nullable|date',
            'expires_on' => 'nullable|date|after_or_equal:issued_on',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,doc,docx',
        ];
    }
}
