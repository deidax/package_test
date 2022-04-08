<?php

namespace App\Models;

use App\Models\Users\Administrateur;
use App\Models\Users\Analyste;
use App\Models\Users\AutreUtilisateur;
use App\Models\Users\BaseUser;
use App\Models\Users\Superadmin;
use App\Models\Users\User;
use App\Models\Users\Veilleur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $guarded = [];

    // public function users()
    // {
    //     return $this->morphedByMany(BaseUser::class, 'permissionable');
    // }
    public function users()
    {
        return $this->belongsToMany(User::class,'permission_user');
    }

    // public function users() {
    //     $types = BaseUser::getChildren();
    //     $sensors = collect();
    
    //     foreach($types as $type) {
    //         $sensors_of_type = $this->morphedByMany($type, 'permissionable')->getResults();
    
    //         foreach($sensors_of_type as $sensor) {
    //             $sensors->push($type::find($sensor['id']));
    //         }
    //     }
    
    //     return $sensors;
    // }

    public function categoriePermission(){
        return $this->belongsTo(CategoriePermission::class);
    }
}
