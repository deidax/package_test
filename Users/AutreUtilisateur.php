<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutreUtilisateur extends BaseUser
{
    use HasFactory;

    protected $table ='autreutilisateurs';

    protected $fillable = ['cin'];

    function __construct(array $attributes = array()) {
        parent::__construct($attributes);
    }

     // Method inherited from BaseUser model
    public static function getDisplayedName()
    {
        return 'Autre Utilisateur';
    }

     // Method inherited from BaseUser model
    //  public static function getExtraFieldsFormSchema()
    //  {
    //      return  [
 
    //                  [
    //                      'id' => 'input-cin',
    //                      'type' => 'text',
    //                      'label' => 'C.I.N',
    //                      'html_tag' => 'b-form-input',
    //                      'name' => 'cin',
    //                      'css_class' => 'form-control form-control-solid h-auto py-4 px-6 rounded-lg',
    //                      'placeholder' => 'CIN'
    //                  ],
                     
    //      ];
    //  }
 
     // Method inherited from BaseUser model
    //  public static function getExtraValidationRules()
    //  {
    //      return [
    //          'cin' => 'required',
    //      ];
    //  }
 
     // Method inherited from BaseUser model
    //  public static function messages()
    //  {
    //      return [
    //          'cin.required' => 'CIN est obligatoire',
    //      ];
    //  }
    
}
