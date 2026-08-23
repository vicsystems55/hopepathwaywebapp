<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SubmitOwnSupervisionRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->role === User::ROLE_STAFF;
    }

    public function rules()
    {
        return [
            'answers' => 'required|array|min:1',
            'answers.*.supervision_question_id' => 'required|integer|distinct|exists:supervision_questions,id',
            'answers.*.answer' => 'required|string|max:10000',
        ];
    }
}
