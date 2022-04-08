<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Users\Administrateur;
use App\Models\Users\BaseUser;
use App\Models\Users\User;
use App\Models\Users\Veilleur;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use ApiResponser;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::getAllUsers();
        $users_with_extra_fields = $users->map(function($user, $key) {
            return $user->buildUserApiWithExtraData();
        });

        return $this->success(UserResource::collection($users_with_extra_fields), $message = 'All users list', $code = 200);

    }

     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getAllUsersByTypeAndExtraFiledsData()
    {
        $users = User::getAllUsers();
        $users_by_type = $users->map(function($user, $key) {
            $user = $user->getUserWithExtraData();
            $user['extra_inputs_schema'] = $user->getExtraFieldsFormSchema();
            $user['user']['displayed_name'] = $user->getDisplayedName();
            return $user;
         });
        // return $users_by_type;
        return $this->success($users_by_type, $message = 'All users list by type', $code = 200);

    }


    public function getUsersByRole()
    {
        $role = "App\\Models\\Users\\Administrateur";
        $users = $role::AllUsersData();

        return $this->success(UserResource::collection($users), $message = 'This\'s getUsersByRoleTest', $code = 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // The storeUser method is in charge of validating and storing the new users 
        return User::storeUser($request);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // this will take care of user updating including form validation
        // unset extra_inputs_schema key
        unset($request['extra_inputs_schema']);
        unset($request['isBlocked']);
        return User::updateUser($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        if ($request->isMethod('delete')) {
            // try {
                $auth_user = Auth::user();
                if($auth_user->id != $user->id){
                    // $user_role = $user->getUserWithExtraData();
                    // // dd($user->destroyUser());
                    // $user_role->forceDelete();
                    $user->destroyUser();
                    return $this->success($user, 'L\'utilisateur a été supprimée');
                }
                return $this->error(["Impossible de supprimer l'utilisateur"],404);
    
            // } catch (\Throwable $th) {
            //     //throw $th;
            // }
           
            
        }
    }

    public function call_user_model_name()
    {
        // Helper function to get user model names
        return $this->success(getUserModelName(), 'User Model Names', 200);
    }

    public function call_user_ranks()
    {
        // call the military ranks from Helper function
        return $this->success(getUserRanks(), 'Liste des grades', 200);
    }

    public function call_extra_fields_form_schema(Request $request)
    {
        return $this->success(encryptDecryptUserModelName($request->model_name, true)::getExtraFieldsFormSchema(), 'Extra form fields for '.encryptDecryptUserModelName($request->model_name, true)::getDisplayedName().' model', 200);
    }

    public function setUserPermissions(Request $request)
    {
        $user = User::find($request->data['user_id']);
        
        $user->permissions()->sync($request->data['permissions']);
        
        return $this->success($user->permissions, 'Permissions ajouter');

        // if(checkPermission('modifier_les_permissions'))
        // {
        //     $user = User::find($request->data['user_id']);
        
        //     $user->permissions()->sync($request->data['permissions']);
            
        //     return $this->success($user->permissions, 'Permissions ajouter');
        // }
        // else
        // {
        //     return $this->error(['Accès Restreint'], 403);
        // }
        
        
    }

    public function blockUser(User $user)
    {
        if(Auth::user()->id != $user->id)
        {
            $output = $user->toggleProfileBlocking();
            if($output['saved'])
            {
                $blocking_state_text = $output['is_blocked'] ? 'bloqué' : 'débloqué';
                
                return $this->success($user, 'Utilisateur a été '.$blocking_state_text);
            }

            return $this->error(['Operation échouée'], 404);
        }

        return $this->error(['Operation échouée'], 404);
        
    }

    public function switchUser(Request $request)
    {
        return User::find($request->id)->switchUserType($request->id, $request->model_name, $request->extra_data);
    }

    /**
     * Change the current password
     * @param Request $request
     * @return Renderable
    */
    public function changePassword(Request $request)
    {       
        // dd($request->data);
        $user = User::find($request->data['user_id']);

        if(Auth::user()->id == $user->id)
        {
            $userPassword = $user->password;
        
            $request->validate([
                'data.current_password' => 'required',
                'data.password' => 'required|same:data.confirm_password|min:6',
                'data.confirm_password' => 'required',
            ]);

            if (!Hash::check($request->data['current_password'], $userPassword)) {
                return $this->error(['Operation échouée. Mot de passe incorrect'], 403);
            }

            $user->password = Hash::make($request->data['password']);

            $user->save();

            return $this->success([], 'Mot de passe modifier avec succès');
        }
        else
        {
        
            $request->validate([
                'data.password' => 'required|same:data.confirm_password|min:6',
                'data.confirm_password' => 'required',
            ]);


            $user->password = Hash::make($request->data['password']);

            $user->save();

            return $this->success([], 'Mot de passe modifier avec succès');
        }
        
        
    }

    public function restoreUserTolatest(Request $request)
    {
        return User::find($request->data['user_id'])->getUsertypeDataOnly()->restoreToLatest() ?
            $this->success([User::find($request->data['user_id'])->buildUserApiWithExtraData()], 'Role a été modifier') :
            $this->error(['Operation échouée'], 404)  ;
    }
}


