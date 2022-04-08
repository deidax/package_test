<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponser;
use Closure;
use Illuminate\Http\Request;

class CheckIfAnyPermissions
{
    use ApiResponser;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $permissions)
    {
        if(!checkIfAnyMultiplePermissions($permissions)){
            return $this->error(['Accès Restreint'], 403);
        }

        return $next($request);
    }
}
