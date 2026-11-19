<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ImportSupervisionArchiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === User::ROLE_ADMIN && $this->user()->hasPermission('supervision.manage');
    }

    public function rules(): array
    {
        return ['archive' => 'required|file|max:5120'];
    }
}
