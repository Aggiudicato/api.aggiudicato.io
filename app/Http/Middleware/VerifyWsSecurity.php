<?php

namespace App\Http\Middleware;

use App\Services\WsSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate POST /pvp/service behind WS-Security signature verification.
 *
 * Runs before the controller on inbound ministry SOAP calls. On failure,
 * returns a SOAP fault envelope (codice=0) instead of throwing a 401 —
 * the Ministry expects SOAP-shaped responses, not HTTP-level errors.
 *
 * When the ministry public certificate is not configured
 * (PVP_MINISTRY_CERT_PATH empty), validation is skipped and requests pass
 * through. This is intentional for local dev and CI, which cannot replay
 * real ministry signatures — it is NOT a fallback for production.
 */
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
