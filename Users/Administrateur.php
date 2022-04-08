<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Administrateur extends BaseUser
{
    use HasFactory;
    
    protected $table ='administrateurs';
    
    function __construct(array $attributes = array()) {
        parent::__construct($attributes);
        $this->permissions = range(1,30);
       
    }

     // Method inherited from BaseUser model
    public static function getDisplayedName()
    {
        return 'Administrateur';
    }   
    
}
