<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StartConversationRequest extends FormRequest
{
    public function rules(): array
    {
        return ['scenario_id' => ['required', 'string', 'max:24']];
    }
}
