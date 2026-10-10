<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\DeleteAccount;
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
