<?php

declare(strict_types=1);

namespace App\Modules\Bootstrap\Http\Controllers;

use App\Modules\Bootstrap\Services\BootstrapPayload;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;

final class BootstrapController extends Controller
{
    public function __invoke(BootstrapPayload $payload): JsonResponse
    {
        return response()->json($payload->get());
    }
}
