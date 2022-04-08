<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Models\Users\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(){
        $permissions = Auth::user()->permissions;
        // return PermissionResource::collection($permissions);
        return $permissions;
    }
}
