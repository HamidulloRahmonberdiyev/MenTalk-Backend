<?php

declare(strict_types=1);

namespace App\Modules\Progress\Http\Controllers;

use App\Modules\Progress\Services\ActivityCalendar;
use App\Shared\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CalendarController extends Controller
{
    public function __invoke(Request $request, ActivityCalendar $calendar): JsonResponse
    {
        $request->validate(['month' => ['nullable', 'date_format:Y-m', 'after_or_equal:2020-01']]);

        return response()->json(['data' => $calendar->month($request->user(), $request->query('month'))]);
    }
}
