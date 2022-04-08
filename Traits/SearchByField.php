<?php
namespace App\Traits;

use Illuminate\Http\Request;


trait SearchByField {


    public static function searchBy(Request $request, string $field){
        if(!in_array($field, self::$searchable_fields)) return (new self)->error(["$field est un champ invalide"], 500);
        if($request->keyword == null) return [];
        $data = self::where($field, 'LIKE','%'.$request->keyword.'%')->get();
        
        return response()->json(self::buildFieldSearchResult($data->unique($field)));
    }
    

}