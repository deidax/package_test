<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Superadmin extends BaseUser
{
    use HasFactory;

    
    
    function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->permissions = range(1,44);
    }
    
     // Method inherited from BaseUser model
    public static function getDisplayedName()
    {
        return 'Super Admin';
    }



    
}
