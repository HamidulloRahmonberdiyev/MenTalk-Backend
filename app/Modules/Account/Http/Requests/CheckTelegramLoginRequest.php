<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CheckTelegramLoginRequest extends FormRequest
{
    public function rules(): array
    {
        return ['secret' => ['required', 'string', 'size:64']];
    }
}
