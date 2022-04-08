<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckIfUserBlocked
{
    use ApiResponser;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        
        if(auth()->check() && auth()->user()->isBlocked){
            Auth::guard('web')->logout();

            $request->session()->invalidate();

            $request->session()->regenerateToken();

            // return redirect()->route('login')->with('error', 'Your Account is suspended, please contact Admin.');
            return $this->error(['Utilisateur a été bloqué par l\'administreur'], 423);

        }

        return $next($request);
    }
}
