<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Restrict a route to a set of role slugs.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_if(! $user || ! in_array($user->primaryRole()?->slug, $roles, true), 403, 'You do not have permission to access this page.');

        return $next($request);
    }
}