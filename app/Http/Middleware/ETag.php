<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ETag
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->setEtag(md5((string) $response->getContent()))->setPublic()->setMaxAge(300);
        $response->isNotModified($request);

        return $response;
    }
}
