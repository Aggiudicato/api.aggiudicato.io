<?php

namespace App\Http\Middleware;

use App\Services\WsSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyWsSecurity
{
    public function __construct(private WsSecurityService $wsSecurity) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->wsSecurity->validateIncomingSignature($request->getContent())) {
            return response()
                ->view('service.response_error', [
                    'codice' => '0',
                    'descrizione' => 'WS-Security validation failed',
                ])
                ->header('Content-Type', 'text/xml');
        }

        return $next($request);
    }
}
