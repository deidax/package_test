<?php

use App\Models\Sources\BaseSource;
use App\Models\Users\BaseUser;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

if (! function_exists('getMorphMaps')) {
    function getMorphMaps() {
        $base_user = BaseUser::generateMorphMap();
        $base_source = BaseSource::generateMorphMap();
        $morph_maps = array_merge($base_user, $base_source);

        return $morph_maps;
    }
}


if (! function_exists('getUserModelName')) {

    function getUserModelName(){
        $users = BaseUser::getChildren();
        $func = function($value) {
            return $value::call_child_model_displayed_name();
        };
        return array_map($func, $users);
    }
    
}



if(! function_exists('checkIfRankIsValide')){

    function checkIfRankIsValide(string $rank_name){

        return in_array($rank_name, getUserRanks());

    }

}


if(! function_exists('encryptDecryptUserModelName')){

    // This function encrypt and decrypt.
    // if the $decrypt arg. is false the function would encrypt, otherwise it would decrypt.
    function encryptDecryptUserModelName(string $modelName, bool $decrypt = false){

        if($modelName == '')
            return null;
        // Store the cipher method
        $ciphering = "AES-128-CTR";
        // Use OpenSSl Encryption method
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;

        // Non-NULL Initialization Vector for encryption
        $encryption_iv = '1234567891011121';
        // Store the encryption key
        $encryption_key = "alphabravo";

        if($decrypt == false)
            return openssl_encrypt($modelName, $ciphering, $encryption_key, $options, $encryption_iv);
        
        return openssl_decrypt($modelName, $ciphering, $encryption_key, $options, $encryption_iv);


    }

}


if(! function_exists('getImageFromPrivateFolder')){

    function getImageFromPrivateFolder(string $file,string $folder)
    {
        $path = '/private/'.$folder.'/'.$file;
        if(Storage::exists($path)){
            
            // Read image path, convert to base64 encoding
            $imageData = base64_encode(file_get_contents(Storage::path($path)));

            // Format the image SRC:  data:{mime};base64,{data};
            $src = 'data: ' . mime_content_type(Storage::path($path)) . ';base64,' . $imageData;

            
            return $src;
        }
        abort(404);
    }

}


if(! function_exists('checkPermission')){

    function checkPermission(string $permission)
    {
        return Gate::allows($permission,Auth::user());
    }

}

if(! function_exists('checkIfAnyMultiplePermissions')){

    //or
    function checkIfAnyMultiplePermissions(string $permissions)
    {       
        $permissions = explode('|', $permissions);

        return Gate::any($permissions,Auth::user());
    }

}

// if(! function_exists('checkifAllMultiplePermissions')){

//     // and
//     function checkifAllMultiplePermissions(string $permission)
//     {
//         return Gate::allows($permission,Auth::user());
//     }

// }
if(! function_exists('enleveAccents')){
    function enleveAccents($chaine)
    {
        $search  = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ð', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ');
        //Préférez str_replace à strtr car strtr travaille directement sur les octets, ce qui pose problème en UTF-8
        $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 'a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y');

        $varMaChaine = str_replace($search, $replace, $chaine);
        return $varMaChaine; //On retourne le résultat
    } 
}

if(! function_exists('formatBytes')){
    function formatBytes($size, $precision = 2)
    {
        if ($size > 0) {
            $size = (int) $size;
            $base = log($size) / log(1024);
            $suffixes = array(' bytes', ' KB', ' MB', ' GB', ' TB');

            return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
        } else {
            return $size;
        }
    }
}