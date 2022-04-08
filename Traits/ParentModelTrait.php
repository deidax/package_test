<?php
namespace App\Traits;


trait ParentModelTrait {

    public static function getChildren()
    {
        $result = [];
        foreach (self::getModels() as $model) {
            if(BaseUser::class !== $model)
            {
                if (is_subclass_of($model, self::class)) 
                    $result[] = $model;
            } 
        }

        return $result;
        $result;
    }

    

    public static function getMorphableType()
    {
        $class_name =  get_called_class();
        $explod_class_name = explode("\\",$class_name);
        $morphable_type = strtolower(end($explod_class_name));

        return $morphable_type;
    }

    // This methode generates the morphMap in AppServiceProvider.php
    public static function generateMorphMap()
    {
        $generated_morph_map = [];

        foreach (self::getChildren() as $key => $value) 
        {
            $el = [ $value::getMorphableType() => $value ];
            array_push($generated_morph_map, $el);
        }

        return array_merge(...array_values($generated_morph_map));

    }

    public static function getModels() {
        $out = [];
        // Important not: in case of using the app on a Unix system make sure to use this line of code instead
        // For Unix systems: 
        // $results = scandir(app_path()."//Models//".self::getRootFolder());

        // Important not: in case of using the app on a Windows system make sure to use this line of code instead
        // For Windows systems: 
        $results = scandir(app_path()."\\Models\\".self::getRootFolder());
        foreach ($results as $result) {
            if ($result === '.' or $result === '..') continue;
            $filename = app_path() . '\\' . $result;
            if (!is_dir($filename)) {
                // $out[] = "App\\Models\\".self::getRootFolder()."\\".str_replace("\\",'',str_replace(app_path(), "App\\Models\\".self::getRootFolder(), substr($filename,0,-4)));
                $out[] = str_replace(app_path(), "App\\Models\\".self::getRootFolder(), substr($filename,0,-4));
                
            }
        }
        return $out;
    }

    public static function getRootFolder()
    {
        return self::$root_folder;
    }

    public static function child_model_displayed_name($displayed_name)
    {
        // The encryptDecryptUserModelName is used to add an extra security to the api (the namespace to the model name will be encrypted on the api)
        return [ 'model_name' => encryptDecryptUserModelName(get_called_class()) , 'displayed_name' => $displayed_name];
    }
    
    

}