<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Shared\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class LogoutController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
