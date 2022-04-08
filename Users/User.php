<?php

namespace App\Models\Users;

use App\Models\Permission;
use App\Models\Permissionable;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use ApiResponser;

    protected $profil_images_folder = "images";
    protected $default_profile_image = "blank.png";
    protected $profile_image_field_name = "profile_image";
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'grade',
        'profile_image',
    ];



    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */

    protected $table = 'users';

    

    public function userable()
    {
        return $this->morphTo();
    }

    public function userHistoryRecordes()
    {
        return $this->hasMany(UserHistory::class);
    }

    //get user by it's type with extra data
    public function getUserWithExtraData()
    {
        return get_class($this->userable)::UserDataById($this->userable->id);
    }

    //get user by it's type without extra data
    public function getUsertypeDataOnly()
    {
        return get_class($this->userable)::find($this->userable->id);
    }

    public function buildUserApiWithExtraData()
    {
        $this['displayed_name'] = get_class($this->userable)::getDisplayedName();
        $this['extra_inputs_schema'] = $this->getUserWithExtraData()->getExtraFieldsFormSchema();
        $this['has_history'] = $this->userHistoryRecordes->count() > 0 ? true : false;
        return $this;
    }

    public static function scopeAllUsersData($query)
    {
        return self::with('userable')->get()->toArray();
    }

    public function createUserHistoryRecord(int $new_userable_id, string $new_userable_type)
    {
        UserHistory::create([
            'user_id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'grade' => $this->grade,
            'old_userable_id' => $this->userable_id,
            'old_userable_type' => $this->userable_type,
            'current_userable_id' => $new_userable_id,
            'current_userable_type' => $new_userable_type,
            'last_permissions' => json_encode($this->permissions->pluck('id')->toArray()),
        ]);
    }

    public function latestHistoryRecord()
    {
        return $this->userHistoryRecordes()->latest('created_at')->first();
    }

    public static function getAllUsers()
    {
        $users = self::all();
        unset($users['password']);
        foreach ($users as $key => $user) {
            $user['displayed_name'] = get_class($user->userable)::getDisplayedName();
        }
        
        return $users;
        
    }
    // $user_id should be given when using this method for update
    public static function sharedFieldsValidation(array $shared_fields,int $user_id = 0,array $validationRulesExcptions = [])
    {
        $messages = self::messages();

        return Validator::make($shared_fields, static::validationRules($validationRulesExcptions, $user_id), $messages);
    }

    public static function validationRules(array $rulesExceptions, int $user_id)
    {
        $rules = $user_id == 0 ? 
                [
                    'name' => 'required|string|min:4',
                    'username' => 'required|string|unique:users,username|min:4',
                    'profile_image'=> 'sometimes|required|mimes:jpeg,jpg,png',
                    'grade' => ['required','string','in:'.implode(',', getUserRanks())],
                    'password' => 'required',
                    'model_name' => 'required'
                ] : 
                [
                    'name' => 'required|string|min:4',
                    'username' => 'required|string|min:4|unique:users,username,'.$user_id,
                    'grade' => ['required','string','in:'.implode(',', getUserRanks())],
                    'profile_image'=> 'sometimes|required|mimes:jpeg,jpg,png'
                ];



        return array_diff_key($rules, array_flip($rulesExceptions));
    }

    /**
    * Set default permissions for each new created user, this method will be used in storeUser()
    *
    */
    public function setDefaultPermissions(){
        $this->permissions()->sync($this->getUsertypeDataOnly()->getDefaultPermissions());
    }

    /**
    * Set array of permissions for each new created user
    * @param  $permissions
    */
    public function setWithPermissions(array $permissions = []){
        $this->permissions()->sync($permissions);
    }

    /**
    * Get the error messages for the defined validation rules.
    *
    * @return array
    */
    public static function messages()
    {
        return [
            'model_name.required' => 'Le champ role est obligatoire.',
            'grade.in' => 'Erreur: Opération non autorisée'
        ];
    }

    // public function restoreToLatest()
    // {
    //     // Get the latest history record
    //     $user_latest_history_record = $this->latestHistoryRecord(); //This returns a UserHistory model
    //     $user_soft_deleted = 
    // }
    
    /**
    * This's the method in charge for creating the user, no matter what his role
    * It's in charge of form validation
    * This method might be useful in user update
    *
    * @param  $request should have ['model_name (encrypted)' , name, grade, username, password, and the extra fields if there's any]
    * @return @return \Illuminate\Http\JsonResponse
    */
    public static function storeUser(Request $request)
    {
         // Before storing the user we should separate the shared fields (the ones in the user's table) from the extra ones (each user might have some extra fields)
         $front_end_data = $request->all();
         $user = new User();
         // This will get all the fillable fields from the user model (the shared fields) -- Note: getFillable() is a Laravel method.
         $shared_fields = $user->getFillable();
         // Get the shared fields
         $shared_fields_data = array_intersect_key($front_end_data, array_flip($shared_fields));
         // Get the extra fields with the model_name
         $extra_fields_data = array_diff_key($front_end_data, array_flip($shared_fields));
         // Get the user model name to create the user object
         $model_name = $extra_fields_data['model_name'];
         // Decrypt the model_name
         $model_name = $model_name != null ? encryptDecryptUserModelName($model_name, true) : $model_name;
         // Check if model_name is a subclass of BaseUser
         if($model_name != null && strcmp(get_parent_class($model_name), "App\Models\Users\BaseUser") != 0 )
            return (new self)->error(['Error user can\'t be created'], 500); 
         // Add the $model_name to shared_fields_data for validation
         $shared_fields_data['model_name'] = $model_name;
         
         // Get the extra fields without the model_name
         unset($extra_fields_data['model_name']);
         // MessageBag to store validation errors
         $validations_errors = new MessageBag();
         // Validation of shared fields
         $shared_fields_validator = User::sharedFieldsValidation($shared_fields_data);
         if($shared_fields_validator->fails())
         {
             // merge the validation errors
             $validations_errors->merge($shared_fields_validator->errors());
         }
         // Validation of extra fields
         if(!empty($extra_fields_data))
         {
             $extra_fields_validator = $model_name::extraFieldsValidation($extra_fields_data, $model_name::getExtraValidationRules());
             if($extra_fields_validator->fails())
             {
                 // merge the validation errors
                 $validations_errors->merge($extra_fields_validator->errors());
             }
         }
         // Before creating the model object check if there's any validation errors
         if(count($validations_errors) > 0)
         {
             return (new self)->error(['Creation utilisateur: Données incorrectes'], 422, $validations_errors);
         }
         // Hash the password
         $shared_fields_data['password'] = Hash::make($shared_fields_data['password']);
         // unset the model_field from shared_fields_data before creating the user because we don't need it anymore
         unset($shared_fields_data['model_name']);
         // The creation of the user
         if($model_name::create($shared_fields_data, $extra_fields_data))
         {
            
            return (new self)->success([], 'Utilisateur a été créé');
         }
         else
         {
            return (new self)->error(['Error user can\'t be created'], 500);
         }
    }

    // This's the method in charge for updating the user, no matter what his role
    // It's in charge of form validation
    // $request should have [id (the one in User table), name, grade, username, and the extra fields if there's any]
    public static function updateUser(Request $request)
    {
        // find user to update
        $user_id = $request->id;
        // check if user has permission to update, or he's updating his own profile
        if(!checkPermission('modifier_utilisateur') && Auth::user()->id != (int)$user_id) return (new self)->error(['Accès Restreint'], 403);

        $user = static::find($user_id);
        // This will get all the fillable fields from the user model (the shared fields) -- Note: getFillable() is a Laravel method.
        $shared_fields = $user->getFillable();
        // Get the shared fields
        $shared_fields_data = array_intersect_key($request->all(), array_flip($shared_fields));
         // Get the extra fields
        $extra_fields = $user->userable->getFillable();

        $extra_fields_data = array_intersect_key($request->all(), array_flip($extra_fields));

        unset($shared_fields_data['password']);
        // Validation
        // MessageBag to store validation errors
        $validations_errors = new MessageBag();
        // Validation of shared fields
        $shared_fields_validator = User::sharedFieldsValidation($shared_fields_data, $user_id);//user_id for the user to update
        if($shared_fields_validator->fails())
        {
            // merge the validation errors
            $validations_errors->merge($shared_fields_validator->errors());
        }
        // Validation of extra fields
        if(!empty($extra_fields_data))
        {
            $extra_fields_validator = get_class($user->userable)::extraFieldsValidation($extra_fields_data, get_class($user->userable)::getExtraValidationRules());
            if($extra_fields_validator->fails())
            {
                // merge the validation errors
                $validations_errors->merge($extra_fields_validator->errors());
            }
        }
        // Before creating the model object check if there's any validation errors
        if(count($validations_errors) > 0)
        {
            return (new self)->error(['Creation utilisateur: Données incorrectes'], 422, $validations_errors);
        }

        foreach ($shared_fields as $field) {
            // if($request[$user->profile_image_field_name] == null) $user->resetToDefaultProfileImage();
            if($request->remove_profile_image == true) $user->resetToDefaultProfileImage();
            if ($request->$field) {
                if($field == $user->profile_image_field_name )
                {
                    $user->updateProfilImage($request, $request->file($user->profile_image_field_name));
                }
                else
                {
                    $user->$field = $request->$field;
                    $user->save();
                }
                
            }
        }

        if(!empty($extra_fields))
        {
            $user->userable()->update($extra_fields_data);
        }

        return (new self)->success([$user->buildUserApiWithExtraData()], 'Utilisateur a été modifié');

    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    /**
    * Toggle user profile blocking (blocked <-- --> unblocked)
    *
    * This method return an array that has two values, ['is_blocked'] store the new blocking state(if it's blocked or not), ['saved'] store if the save() was done successfuly.
    * @return array
    */
    public function toggleProfileBlocking()
    {
        
        $this->isBlocked = !$this->isBlocked;

        return [
            'is_blocked' => $this->isBlocked,
            'saved' => $this->save(),
        ];
    }

    // update image
    public function updateProfilImage(Request $request, $file){
        $filename = '';
        // should be changed
        if ($request->hasFile($this->profile_image_field_name)) {
            $image = $file;

            $extension = $image->getClientOriginalExtension();
            $filename = (string) Str::uuid() . '.' . $extension;
            

            if($filename != $this->default_profile_image){
                if($this->{$this->profile_image_field_name} != $this->default_profile_image){
                    Storage::disk('public')->delete('uploads/user/profils/'.$this->profil_images_folder.'/'.$this->{$this->profile_image_field_name});
                }
                if ($image->storeAs('public/uploads/user/profils/'.$this->profil_images_folder.'/', $filename)) {
                    $this->hasImage = true;
                };
            }else{
                $this->hasImage = false;
            }
        }else{
            $this->hasImage = true;
            $filename = $this->{$this->profile_image_field_name};
        }


        if ($this->hasImage && $filename != '') {
            $image = $filename;
        }
        elseif(!$this->hasImage){
            $image = $this->default_profile_image;
        }
        
        $this->{$this->profile_image_field_name} = $image;
        $this->save();
    }

    // reset default profile image
    public function resetToDefaultProfileImage()
    {
        if($this->{$this->profile_image_field_name} != $this->default_profile_image){
            Storage::disk('public')->delete('uploads/user/profils/'.$this->profil_images_folder.'/'.$this->{$this->profile_image_field_name});
            $this->{$this->profile_image_field_name} = $this->default_profile_image;
            $this->hasImage = false;

            $this->save();
        }
    }

    public function switchUserType(int $user_id, $model_name, array $extra_fields_data = [])
    {
        $validations_errors = new MessageBag();

        // Decrypt the model_name
        $model_name = $model_name != null ? encryptDecryptUserModelName($model_name, true) : $model_name;
        // Check if model_name is a subclass of BaseUser
        if($model_name != null && strcmp(get_parent_class($model_name), "App\Models\Users\BaseUser") != 0 || strcmp(get_class(self::find($user_id)->getUsertypeDataOnly()), $model_name) == 0 )
           return $this->error(['Error user can\'t be created'], 500); 
        // Add the $model_name to shared_fields_data for validation
        $shared_fields_data['model_name'] = $model_name;
        $shared_fields_validator = User::sharedFieldsValidation($shared_fields_data, 0, ['name', 'username', 'grade' , 'profile_image' ,'password']);
        if($shared_fields_validator->fails())
        {
            // merge the validation errors
            $validations_errors->merge($shared_fields_validator->errors());
        }
        // Validation of extra fields
        if(!empty($extra_fields_data))
        {
            $extra_fields_validator = $model_name::extraFieldsValidation($extra_fields_data, $model_name::getExtraValidationRules());
            if($extra_fields_validator->fails())
            {
                // merge the validation errors
                $validations_errors->merge($extra_fields_validator->errors());
            }
        }
        // Before creating the model object check if there's any validation errors
        if(count($validations_errors) > 0)
        {
            return $this->error(['Changement de role: Données incorrectes'], 422, $validations_errors);
        }

        return $this->getUsertypeDataOnly()->switchTo($model_name, $extra_fields_data) ? 
                $this->success([self::find($user_id)->buildUserApiWithExtraData()], 'Role a été modifier') :
                $this->error(['Operation échouée'], 404)  ;
    }

    public function destroyUser()
    {
        $user_history = UserHistory::where('user_id', $this->id);
        
        if($user_history->get()->count() == 0)
        {
            $user_role = $this->getUserWithExtraData();
            $user_role->forceDelete();

            return true;
        }
        else
        {
            
            foreach ($user_history->get() as $uh) {
                if($uh->getOldUserableType()::withTrashed()->find($uh->old_userable_id) != null)
                {
                    $uh->getOldUserableType()::withTrashed()->find($uh->old_userable_id)->forceDelete();
                }
            }
            $user_history->delete();
            $user_role = $this->getUserWithExtraData();
            $user_role->forceDelete();

            return true;

        }

        return false;

    }

    
}
