<?php

namespace App\Http\Controllers;

use App\Models\CategoriePermission;
use App\Models\Dossier;
use App\Models\Permission;
use App\Models\Permissionable;
use App\Models\Users\Administrateur;
use App\Models\Users\AutreUtilisateur;
use App\Models\Users\BaseUser;
use App\Models\Users\MyUserTest;
use App\Models\Users\Superadmin;
use App\Models\Users\User;
use App\Models\Users\Veilleur;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    use ApiResponser;

    public function __construct()
    {
        // $this->middleware('auth:sanctum');
    }
    
    // public function register(Request $request)
    // {
    //     $attr = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|unique:users,email',
    //         'password' => 'required|string|min:6|confirmed'
    //     ]);

    //     $user = User::create([
    //         'name' => $attr['name'],
    //         'password' => bcrypt($attr['password']),
    //         'email' => $attr['email']
    //     ]);

    //     return $this->success([
    //         'token' => $user->createToken('API Token')->plainTextToken
    //     ]);
    // }

    public function login(Request $request)
    {
        // $a = Veilleur::create(['name'=>'Said','username'=>'v5@v5.com','password'=>Hash::make(123456), 'grade'=>getUserRanks()[12] ], ['number_of_publications' => 1, 'matricule' => 12000]);
        // $b = Veilleur::create(['name'=>'Merzougi','username'=>'v1@v1.com','password'=>Hash::make(123456), 'grade' => getUserRanks()[10] ], ['number_of_publications' => 4, 'matricule' => 15844]);
        // $c = Administrateur::create(['name'=>'Admin','username'=>'admin@admin.com','password'=>Hash::make(123456), 'grade' => getUserRanks()[9] ]);
        // $d = Superadmin::create(['name'=>'Super Admin','username'=>'ad2@ad2.com','password'=>Hash::make(123456), 'grade' => getUserRanks()[8] ]);
        // $e = AutreUtilisateur::create(['name'=>'User','username'=>'au@au.com','password'=>Hash::make(123456), 'grade' => getUserRanks()[7] ]);
        // // // dd();
        // // // $dossier = Dossier::create(['id' => 1,'nom' =>'Plateforme veille','user_id'=> 3, 'parent_id' => 0, 'path' => '1']);

        // $p= CategoriePermission::create(['nom' => 'Systeme']);
        // $p2= CategoriePermission::create(['nom' => 'Source']);
        // $p3= CategoriePermission::create(['nom' => 'Publication']);
        // $p5= CategoriePermission::create(['nom' => 'Acteur']);

        // $p= Permission::create(['nom' => 'ajouter_utilisateur' , 'categorie_permission_id' => 1]);
        // $p2= Permission::create(['nom' => 'ajouter_publication', 'categorie_permission_id' => 3]);
        // $p3= Permission::create(['nom' => 'bloquer_utilisateur', 'categorie_permission_id' => 1]);
        // $p5= Permission::create(['nom' => 'analyser_publication', 'categorie_permission_id' => 3]);

        // dd();
        
        // Presse::create(['nom'=>'facebook', 'localisation'=>'test localisation', 'autre_informations'=> 'nothing here'], ['logo'=> './media/logo.png', 'directeur_publication'=> 'alpha', 'photo_directeur_publication'=> './media/directeur_logo.png', 'redacteur_end_chef'=>'bravo', 'photo_redacteur_en_chef'=> './media/redacteur_photo.png]');
        // dd("ok");
        $attributes = $request->validate([
            'username' => ['required'],
            'password' => ['required']
        ]);
        

        if (Auth::attempt($attributes)) {
            // $user = Auth::user();
            // $user = User::all();
            
            // $pable1= $user->permissions()->create(['permission_id' => 7, 'permissionable_type' => Auth::user()->userable_type, 'permissionable_id' => Auth::user()->userable_id]);
            // $user->userable->getThisUserData()->permissions()->attach($permission);
            // dd($user->userable->permissions()->attach($permission));
            // dd($user->userable->permissions);
            // dd($user->userable->getModels());
            // dd($permission->users()[0]->getThisUserData()->user->userable_type);
            // dd(BaseUser::generateMorphMap());
            // dd($user->userable()->getThisUserData());

            $cat = CategoriePermission::find(1);
            // dd($cat->permissions->pluck('nom','id')->toArray());
            // dd($user->map(function ($item, $key) {
            //     return $item;
            // })->toArray());
            // dd($user->map('userable_id','userable_type')->toArray());

            // dd($permission->users->id);
            // $user->permissions()->attach(4);    
            // if (! Gate::allows('access_plateforme', $user)) {
            //     dd('no');
            // }
            
            return response()->json(Auth::user(), 200);
            // dd(Gate::abilities());
        }

        throw ValidationException::withMessages([
            'auth_errors' => $this->error(['Les informations d\'identification fournies sont incorrectes.'], 422)
        ]);
        
    }

    public function logout()
    {
        Auth::logout();
    }

    public function username()
    {
        return "username";
    }

    public function checkAuth()
    {
        return response()->json(Auth::check(), 200);
    }
}
