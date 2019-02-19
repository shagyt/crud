<?php

namespace Shagyt\lvcrud\Models;

use Illuminate\Database\Eloquent\Model;

class FieldType extends Model
{

     /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */
    
    protected $table = 'field_types';
	
	protected $hidden = [
        
    ];

    public $timestamps = false;
    
	protected $guarded = [];

	// protected $dates = ['deleted_at'];

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    // FieldType::getFTypes()
    public static function getFTypes()
    {
        $fields = FieldType::all();
        $fields2 = array();
        foreach($fields as $field) {
            $fields2[$field['name']] = $field['id'];
        }
        return $fields2;
    }
    
    // FieldType::getFTypes2()
    public static function getFTypes2()
    {
        $fields = FieldType::all();
        $fields2 = array();
        foreach($fields as $field) {
            $fields2[$field['id']] = $field['name'];
        }
        return $fields2;
    }
    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */
}
