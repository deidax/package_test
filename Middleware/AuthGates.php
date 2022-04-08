<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use App\Models\Users\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthGates
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // $user = User::find(1);
        if(!app()->runningInConsole() && $user){
            $permissions = Permission::all();
            
            foreach($permissions as $permission){
                // dd($permission->users());
                foreach($permission->users as $user_permission){
                    $permissionsArray[$permission->nom][] = $user_permission->id;
                }
            }
            
            if(!empty($permissionsArray)){
                foreach($permissionsArray as $nom => $users){
                    // foreach($users as $user_permission){
                    //     Gate::define($nom, function(User $user) use ($user_permission){
                    //         return $user->id === $user_permission;
                    //     });
                    // }
                    Gate::define($nom, function(User $user) use ($users){
                        // return $user->id === $user_permission;
                        return in_array($user->id, $users);
                    });
                }
            }
            // dd(Gate::abilities());
            // Gate::define('ajouter_acteur', function (User $user){
            //     return $user->id === 3;
            // });


           

        }
        
        return $next($request);

        /* 
            $user = \Auth::user();
            if(!app()->runningInConsole() && $user){
                $roles = Role::with('permissions')->get();
                foreach($roles as $role){
                    foreach($role->permissions as $permissions){
                        $permissionsArray[$permissions->title][] = $role->id;
                    }
                }

                foreach($permissionsArray as $title => $roles){
                    Gate::define($title, function(\App\User $user) use ($roles){
                        reurn count(array_intersect($user->roles->pluck('id')->toArray(), $roles)) > 0;
                    });
                }
            }
        */
    }
}
