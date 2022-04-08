<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryPermissionResource;
use App\Http\Resources\PermissionResource;
use App\Models\CategoriePermission;
use App\Models\Users\User;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryPermissionController extends Controller
{
    use ApiResponser;

    public function __construct()
    {
        // $this->middleware('auth:sanctum');
    }

    public function index(){
        
        $category_permissions = CategoriePermission::all();
        return CategoryPermissionResource::collection($category_permissions); 

    }
}
