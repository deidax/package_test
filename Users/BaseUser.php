<?php

namespace App\Models\Users;

use App\Models\Permission;
use App\Traits\ParentModelTrait;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\This;

abstract class BaseUser extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ParentModelTrait;

    protected $hidden = ['id', 'created_at', 'updated_at'];

    private static $root_folder = 'Users';
    
    // this is the default permissions for each type of users
    // this array will containe the ids of permissions in database
    // the values of this array will be set in each user type model class construct (Veilleur, Superadmin...)
    protected $permissions = [];

    //Overriding the create method
    public static function create(array $attributes = [], array $user_extra_attributes = [])
    {
        
        $model = static::query()->create($user_extra_attributes);
        
        if(!empty($attributes)){
            $model = $model->user()->create($attributes);
            $model->setDefaultPermissions();
        }
        
        
        return $model;
    }

    //Overriding the delete method
    public function delete()
    {
        $res = parent::delete();
        if($res == true)
        {
            if($this->user != null)
            {
                $relation = $this->user; // here get the relation data
                $relation->delete();
            }
        }
    }

    
    //Get all users data for any model that inheritates from BaseUser
    public static function AllUsersData()
    {
        return get_called_class()::with('user')->get();
    }

    //Get user data by it's Id for any model that inheritates from BaseUser
    public static function UserDataById(int $user_id)
    {
        // return get_called_class()::with('user')->find($user_id);
        return get_called_class()::with('user')->find($user_id);
    }

    //Get current user data for any model that inheritates from BaseUser
    // (exemple: 
    // $user = Veilleur::find(1);
    // $user_data = $user->getThisUserData();
    // )
    public function getThisUserData()
    {
        return get_called_class()::with('user')->find($this->id);
    }

    /**
    * Switch to a different model type.
    *
    * @param  object  $model_name_to_switch_to //this is the model that you want to switch to (exemple: App\Models\Users\Veilleur or Veilleur).
    * @param  array $attributes_for_the_new_model //if the model that you want to switch to has any new extra attributes you add them here.
    * @param  bool $restore //this a flag that tells if the switchTo function will be used by the restoreRecorde() method in the UserHistory model.
    * @param  int $old_userable_id //when the switchTo() method is used by the restoreRecorde() method in the UserHistory model it will take this attribute.
    * @param  array $old_permissions //old permissions in UserHistory.
    * Note:: You don't have to set the $restore and $old_userable_id params, they will be handled by the restoreRecorde() method in the UserHistory model.
    * @return bool
    */
    public function switchTo($model_name_to_switch_to, array $attributes_for_the_new_model = [], bool $restore = false, int $old_userable_id = null, array $old_permissions = [])
    {
        if(!$restore)
        {
            // Create the new model that we would like to switch to
            $model = $model_name_to_switch_to::create([], $attributes_for_the_new_model);
        }
        else
        {
            // Find the new model that we would like to switch to
            try 
            {
                $model = $model_name_to_switch_to::withTrashed()->find($old_userable_id);
                $model->restore();
                $model = $model->replicate();
                $model->save();
                $model_name_to_switch_to::withTrashed()->find($old_userable_id)->forceDelete();
                // $model->;
                // $model->restore();
            } 
            catch (\Throwable $th) 
            {
                throw $th;
            }
            
        }
        
        // Find the user that you want to switch
        $user = $this->getThisUserData()->user;
        // Record the history of the operation
        $user->createUserHistoryRecord($model->id, get_class($model));
        // Associate and save
        $user->userable()->associate($model)->save();
        // Set the new permissions for user
        $restore ? $user->setWithPermissions($old_permissions) : $user->setDefaultPermissions();
        // Soft delete to archive
        $this->delete();

        return true;
    }

    // Restore model to the latest recorde 
    public function restoreToLatest(bool $restoreHistoryRecorde = false, $old_history_recorde = null)
    {
        // Get the latest history record
        $user_latest_history_record = $this->getThisUserData()->user->latestHistoryRecord(); //This returns a UserHistory model
        if(is_null($user_latest_history_record))
            return false;

        // restore the latest recorde
        if(!$restoreHistoryRecorde)
        {
            // Get the old model type
            $old_model_name_to_switch_to = $user_latest_history_record->getOldUserableType();
            // Restore the old record
            $old_record_id  = $user_latest_history_record->getOldUserableId();
        }
        // restore a specific history recorde
        else
        {
            if(is_null($old_history_recorde))
                return false;
            $old_model_name_to_switch_to = $old_history_recorde->getOldUserableType();
            // Restore the old record
            $old_record_id  = $old_history_recorde->getOldUserableId();
        }
           
        // Switch to old
        $this->switchTo($old_model_name_to_switch_to, [], true, $old_record_id, json_decode($user_latest_history_record->last_permissions));

        return true;
    }
    

    public function user()
    {
        return $this->morphOne(User::class, 'userable');
    }

    // public function permissions()
    // {
    //     return $this->morphToMany(Permission::class, 'permissionable');
    // }
    
    public static function call_child_model_displayed_name()
    {
        return self::child_model_displayed_name(static::getDisplayedName());
    }

    public static function getDisplayedName()
    {
        return 'This\'s the BaseUser Model, it should be overriden in the children models';
    }

    public static function getExtraFieldsFormSchema()
    {
        // This will be the form schema to generate forms for extra fields automatically on the front-end
        // This static function should be overriden for any BaseUser child class that has extra fields
        return [];
    }

    // return the default permissions (array of permissions ids)
    public function getDefaultPermissions()
    {
        return $this->permissions;
    }

    /**
    * @param  array  $extra_fields // the extra fields in the BaseUser's child
    * @param  array $validation_rules // the validation rules for the extra fields
    */
    public static function extraFieldsValidation(array $extra_fields, array $validation_rules)
    {
        // This method is the validator for any extra fields
        // Any extra fields that exist in the BaseUser model children would be validated here
        
        // This is the customize error messages
        $messages = static::messages();
        return Validator::make($extra_fields, $validation_rules, $messages);
    }

    public static function getExtraValidationRules()
    {
        // This method would define the validation rules of the extra fields
        // This static function should be overriden for any BaseUser child class that has extra fields
        return [];
    }

    /**
    * Get the error messages for the defined validation rules.
    * This method should be overriden in the BaseUser child models in case you need customize error messages
    * @return array
    */
    public static function messages()
    {
        return [];
    }
    
}
