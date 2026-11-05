<?php

declare(strict_types=1);

namespace App\Modules\Account\Http\Controllers;

use App\Modules\Account\Actions\DeleteAccount;
use App\Shared\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class DeleteAccountController extends Controller
{
    public function __invoke(Request $request, DeleteAccount $delete): Response
    {
        $delete($request->user());

        return response()->noContent();
    }
}
