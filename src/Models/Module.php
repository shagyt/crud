<?php

namespace Shagyt\lvcrud\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Exception;
use Log;
use DB;

use Shagyt\lvcrud\Models\Field;
use Shagyt\lvcrud\Models\FieldType;
use Shagyt\lvcrud\Helpers\crud\ObjectHelper;
use Shagyt\lvcrud\Helpers\crud\CustomHelper;
use Shagyt\lvcrud\User;
use Shagyt\lvcrud\Models\UserModule;

class Module extends Model
{
    // use SoftDeletes;
    
     /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'modules';
	
	protected $hidden = [
        
    ];

    protected $guarded = [];
    
    public $timestamps = false;
    
	// protected $dates = [];
    
    /**
	* Get Module by module name
	* $module = self::make($module_name);
	**/
	public static function make($module_name) {
		$module = null;
		if(is_int($module_name)) {
			$module = self::find($module_name);
		} else {
			$module = self::where('name', $module_name)->first();
		}
		
		if(isset($module)) {
            $crud = new ObjectHelper;
            $crud->setModule($module);
            return $crud;
		} else {
			return null;
		}
	}

    public static function custome_all_modules() {
        return self::whereNotIn('name', ['Users','Uploads','Permissions','Roles','Tests']);
    }

    /**
     * Get the corresponding Eloquent Model for the CrudController, as defined with the setModel() function;.
     *
     * @return [Eloquent Collection]
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set the route for this CRUD.
     * Ex: admin/article.
     *
     * @param [string] Route name.
     * @param [array] Parameters.
     */
    public function setRoute($route)
    {
        $this->route = $route;
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /**
     * This function handles Module Migration via "self::generate()" call from migrations file.
     * This creates all given Module fields into database.
     *
     * @param $module_name Module Name
     * @param $module_table Module Database name in lowercase and concatenated by underscore.
     * @param $represent_attr View Column of Module for Index Anchor purpose.
     * @param string $faIcon Module FontAwesome Icon "fa-smile-o"
     * @param $fields Array of Module fields
     * @throws Exception Throws exceptions if Invalid represent_attrumn_name provided.
     */
    public static function generate($module_name, $table_name, $represent_attr, $faIcon = "fa-smile-o", $fields)
    {
        
        $names = CustomHelper::generateModuleNames($module_name, $faIcon);
        $fields = self::format_fields($module_name, $fields);
        
        if(substr_count($represent_attr, " ") || substr_count($represent_attr, ".")) {
            throw new Exception("Unable to generate migration for " . ($names->module) . " : Invalid represent_attrumn_name. 'This should be database friendly lowercase name.'", 1);
        } else if(!self::validate_represent_attrumn($fields, $represent_attr)) {
            throw new Exception("Unable to generate migration for " . ($names->module) . " : represent_attrumn_name not found in field list.", 1);
        } else {
            // Check is Generated
            // $is_gen = false;
            // if(file_exists(base_path('app/Http/Controllers/' . ($names->controller) . ".php"))) {
            //     if(($names->model == "User" || $names->model == "Role" || $names->model == "Permission") && file_exists(base_path('app/' . ($names->model) . ".php"))) {
            //         $is_gen = true;
            //     } else if(file_exists(base_path('app/Models/' . ($names->model) . ".php"))) {
            //         $is_gen = true;
            //     }
            // }
            
            // Create Module if not exists
            $module = self::where('name', $names->module)->first();
            if(!isset($module->id)) {
                $module = new self;
                $module->name = $names->module;
                $module->label = $names->label;
                $module->table_name = $table_name;
                $module->represent_attr = $represent_attr;
                $module->model = $names->model;
                $module->controller = $names->controller;
                $module->icon = $faIcon;
                $module->save();
            }
            
            $ftypes = FieldType::getFTypes();
            //print_r($ftypes);
            //print_r($module);
            //print_r($fields);
            
            // Create Database Schema for table
            Schema::create($table_name, function (Blueprint $table) use ($fields, $module, $ftypes) {
                $table->increments('id');
                foreach($fields as $field) {
                    
                    $mod = Field::where('module_id', $module->id)->where('name', $field->name)->first();
                    if(!isset($mod->id)) {
                        if($field->field_type == "Multiselect" || $field->field_type == "Taginput") {
                            
                            if(is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
                                $field->defaultvalue = json_decode($field->defaultvalue, true);
                            }
                            
                            if(is_string($field->defaultvalue) || is_int($field->defaultvalue)) {
                                $dvalue = json_encode([$field->defaultvalue]);
                            } else {
                                $dvalue = json_encode($field->defaultvalue);
                            }
                        } else {
                            $dvalue = $field->defaultvalue;
                            if(is_string($field->defaultvalue) || is_int($field->defaultvalue)) {
                                $dvalue = $field->defaultvalue;
                            } else if(is_array($field->defaultvalue) && is_object($field->defaultvalue)) {
                                $dvalue = json_encode($field->defaultvalue);
                            }
                        }
                        
                        $pvalues = $field->json_values;
                        if(is_array($field->json_values) || is_object($field->json_values)) {
                            $pvalues = json_encode($field->json_values);
                        }
                        
                        // Create Module field Metadata / Context
                        $field_obj = Field::create([
                            'name' => $field->name,
                            'label' => $field->label,
                            'module_id' => $module->id,
                            'field_type_id' => $ftypes[$field->field_type],
                            'unique' => $field->unique,
                            'defaultvalue' => $dvalue,
                            'minlength' => $field->minlength,
                            'maxlength' => $field->maxlength,
                            'required' => $field->required,
                            'nullable_required' => $field->nullable_required,
                            'show_index' => $field->show_index,
                            'json_values' => $pvalues
                        ]);
                        $field->id = $field_obj->id;
                        $field->module_obj = $module;
                    }
                    
                    // Create Module field schema in database
                    if(isset($field->nullable_required) && !$field->nullable_required) {
                        self::create_field_schema($table, $field, false);
                    } else {
                        self::create_field_schema($table, $field);
                    }
                }
                
                // $table->string('name');
                // $table->string('designation', 100);
                // $table->string('mobile', 20);
                // $table->string('mobile2', 20);
                // $table->string('email', 100)->unique();
                // $table->string('gender')->default('male');
                // $table->integer('dept')->unsigned();
                // $table->integer('role')->unsigned();
                // $table->string('city', 50);
                // $table->string('address', 1000);
                // $table->string('about', 1000);
                // $table->date('date_birth');
                // $table->date('date_hire');
                // $table->date('date_left');
                // $table->double('salary_cur');
                if($module->table_name == "users") {
                    $table->rememberToken();
                }
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Method creates database table field via $table variable from Schema
     * @param $table
     * @param $field
     * @param bool $update
     * @param bool $isFieldTypeChange
     */
    public static function create_field_schema($table, $field, $nullable_required = true, $update = false, $isFieldTypeChange = false)
    {
        if(is_numeric($field->field_type)) {
            $ftypes = FieldType::getFTypes();
            $field->field_type = array_search($field->field_type, $ftypes);
        }
        if(!is_string($field->defaultvalue)) {
            $defval = json_encode($field->defaultvalue);
        } else {
            $defval = $field->defaultvalue;
        }
        // Log::debug('Module:create_field_schema ('.$update.') - '.$field->name." - ".$field->field_type
        // ." - ".$defval." - ".$field->maxlength);
        
        // Create Field in Database for respective Field Type
        switch($field->field_type) {
            // case 'Address':
            //     $var = null;
            //     if($field->maxlength == 0) {
            //         if($update) {
            //             $var = $table->text($field->name)->change();
            //         } else {
            //             $var = $table->text($field->name);
            //         }
            //     } else {
            //         if($update) {
            //             $var = $table->string($field->name, $field->maxlength)->nullable()->change();
            //         } else {
            //             $var = $table->string($field->name, $field->maxlength)->nullable();
            //         }
            //     }
            //     if($field->defaultvalue != "") {
            //         $var->default($field->defaultvalue);
            //     } else if($field->required) {
            //         $var->default("");
            //     }
            //     break;
            case 'Checkbox':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->string($field->name, 256)->change();
                    } else {
                        $var = $table->string($field->name, 256)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->string($field->name, 256);
                    } else {
                        $var = $table->string($field->name, 256)->nullable();
                    }
                }
                if(is_array($field->defaultvalue)) {
                    $field->defaultvalue = json_encode($field->defaultvalue);
                    $var->default($field->defaultvalue);
                } else if(is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
                    $var->default($field->defaultvalue);
                } else if($field->defaultvalue == "" || $field->defaultvalue == null) {
                    $var->default("[]");
                } else if(is_string($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    $var->default($field->defaultvalue);
                } else if(is_int($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    //echo "int: ".$field->defaultvalue;
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("[]");
                }
                break;
            case 'CKEditor':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->text($field->name)->change();
                        } else {
                            $var = $table->text($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->text($field->name);
                        } else {
                            $var = $table->text($field->name)->nullable();
                        }
                    }
                } else {
                    if($update) {
                        $var = $table->string($field->name, $field->maxlength)->nullable()->change();
                    } else {
                        $var = $table->string($field->name, $field->maxlength)->nullable();
                    }
                    if($field->defaultvalue != "") {
                        $var->default($field->defaultvalue);
                    } else if($field->required) {
                        $var->default("");
                    }
                }
                break;
            case 'Hidden':
                if($field->json_values == "") {
                    if(is_int($field->defaultvalue)) {
                        if($update) {
                            $var = $table->integer($field->name)->unsigned()->nullable()->change();
                        } else {
                            $var = $table->integer($field->name)->unsigned()->nullable();
                        }
                        $var->default($field->defaultvalue);
                        break;
                    } else if(is_string($field->defaultvalue)) {
                        if($update) {
                            $var = $table->string($field->name)->nullable()->change();
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                        $var->default($field->defaultvalue);
                        break;
                    } else {
                        $var = $table->string($field->name)->nullable();
                        $var->default($field->defaultvalue);
                        break;
                    }
                }
                $json_values = json_decode($field->json_values, true);
                if(starts_with($field->json_values, "@")) {
                    $foreign_table_name = str_plural(ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', str_replace("@", "", $field->json_values))), '_'));
                    if($update) {
                        $var = $table->integer($field->name)->nullable()->unsigned()->change();
                        if($field->defaultvalue == "" || $field->defaultvalue == "0") {
                            $var->default(NULL);
                        } else {
                            $var->default($field->defaultvalue);
                        }
                        $table->dropForeign($field->module_obj->table . "_" . $field->name . "_foreign");
                        $table->foreign($field->name)->references('id')->on($foreign_table_name)->onUpdate('cascade')->onDelete('cascade');
                    } else {
                        $var = $table->integer($field->name)->nullable()->unsigned();
                        if($field->defaultvalue == "" || $field->defaultvalue == "0") {
                            $var->default(NULL);
                        } else {
                            $var->default($field->defaultvalue);
                        }
                        $table->foreign($field->name)->references('id')->on($foreign_table_name)->onUpdate('cascade')->onDelete('cascade');
                    }
                } else if(is_array($json_values)) {
                    if($update) {
                        $var = $table->string($field->name)->nullable()->change();
                    } else {
                        $var = $table->string($field->name)->nullable();
                    }
                    if($field->defaultvalue != "") {
                        $var->default($field->defaultvalue);
                    } else if($field->required) {
                        $var->default("");
                    }
                } else if(is_object($json_values)) {
                    // ############### Remaining
                    if($update) {
                        $var = $table->integer($field->name)->nullable()->unsigned()->change();
                    } else {
                        $var = $table->integer($field->name)->nullable()->unsigned();
                    }
                    // if(is_int($field->defaultvalue)) {
                    //     $var->default($field->defaultvalue);
                    //     break;
                    // }
                }
                break;
            case 'Currency':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->double($field->name, 15, 2)->change();
                    } else {
                        $var = $table->double($field->name, 15, 2)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->double($field->name, 15, 2);
                    } else {
                        $var = $table->double($field->name, 15, 2)->nullable();
                    }
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("0.0");
                }
                break;
            case 'Date':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->date($field->name)->change();
                    } else {
                        $var = $table->date($field->name)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->date($field->name);
                    } else {
                        $var = $table->date($field->name)->nullable();
                    }
                }
                
                if($field->defaultvalue == NULL || $field->defaultvalue == "" || $field->defaultvalue == "NULL") {
                    $var->default(NULL);
                } else if($field->defaultvalue == "now()") {
                    $var->default(NULL);
                } else if($field->required) {
                    $var->default("1970-01-01");
                } else {
                    $var->default($field->defaultvalue);
                }
                break;
            case 'Datetime':
                if($update) {
                    // Timestamp Edit Not working - http://stackoverflow.com/questions/34774628/how-do-i-make-doctrine-support-timestamp-columns
                    // Error Unknown column type "timestamp" requested. Any Doctrine type that you use has to be registered with \Doctrine\DBAL\Types\Type::addType()
                    // $var = $table->timestamp($field->name)->change();
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->timestamp($field->name);
                    } else {
                        $var = $table->timestamp($field->name)->nullable();
                    }
                }
                // $table->timestamp('created_at')->useCurrent();
                if($field->defaultvalue == NULL || $field->defaultvalue == "" || $field->defaultvalue == "NULL") {
                    $var->default(NULL);
                } else if($field->defaultvalue == "now()") {
                    $var->default(DB::raw('CURRENT_TIMESTAMP'));
                } else if($field->required) {
                    $var->default("1970-01-01 01:01:01");
                } else {
                    $var->default($field->defaultvalue);
                }
                break;
            case 'Date_picker':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->date($field->name)->change();
                    } else {
                        $var = $table->date($field->name)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->date($field->name);
                    } else {
                        $var = $table->date($field->name)->nullable();
                    }
                }
                
                if($field->defaultvalue == NULL || $field->defaultvalue == "" || $field->defaultvalue == "NULL") {
                    $var->default(NULL);
                } else if($field->defaultvalue == "now()") {
                    $var->default(NULL);
                } else if($field->required) {
                    $var->default("1970-01-01");
                } else {
                    $var->default($field->defaultvalue);
                }
                break;
            case 'Datetime_picker':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->dateTime($field->name)->change();
                    } else {
                        $var = $table->dateTime($field->name)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->dateTime($field->name);
                    } else {
                        $var = $table->dateTime($field->name)->nullable();
                    }
                }
                // $table->timestamp('created_at')->useCurrent();
                if($field->defaultvalue == NULL || $field->defaultvalue == "" || $field->defaultvalue == "NULL") {
                    $var->default(Null);
                } else if($field->defaultvalue == "now()") {
                    $var->default(DB::raw('CURRENT_TIMESTAMP'));
                } else if($field->required) {
                    $var->default("1970-01-01 01:01:01");
                } else {
                    $var->default($field->defaultvalue);
                }
                break;
            case 'Date_range':
                if($update) {
                    // Timestamp Edit Not working - http://stackoverflow.com/questions/34774628/how-do-i-make-doctrine-support-timestamp-columns
                    // Error Unknown column type "timestamp" requested. Any Doctrine type that you use has to be registered with \Doctrine\DBAL\Types\Type::addType()
                    // $var = $table->timestamp($field->name)->change();
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->string($field->name);
                    } else {
                        $var = $table->string($field->name)->nullable();
                    }
                }
                // $table->timestamp('created_at')->useCurrent();
                if($field->defaultvalue == NULL || $field->defaultvalue == "" || $field->defaultvalue == "NULL") {
                    $var->default(NULL);
                } else if($field->defaultvalue == "now()") {
                    $var->default(DB::raw('CURRENT_TIMESTAMP'));
                } else if($field->required) {
                    $var->default("1970-01-01 01:01:01");
                } else {
                    $var->default($field->defaultvalue);
                }
                break;
            case 'Decimal':
                $var = null;
                if($update) {
                    $var = $table->decimal($field->name, 15, 3)->change();
                } else {
                    $var = $table->decimal($field->name, 15, 3);
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("0.0");
                }
                break;
            case 'Select2':
                if($field->json_values == "") {
                    if(is_int($field->defaultvalue)) {
                        if($update) {
                            $var = $table->integer($field->name)->unsigned()->nullable()->change();
                        } else {
                            $var = $table->integer($field->name)->unsigned()->nullable();
                        }
                        $var->default($field->defaultvalue);
                        break;
                    } else if(is_string($field->defaultvalue)) {
                        if($update) {
                            $var = $table->string($field->name)->nullable()->change();
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                        $var->default($field->defaultvalue);
                        break;
                    }
                }
                $json_values = json_decode($field->json_values, true);
                if(starts_with($field->json_values, "@")) {
                    $foreign_table_name = str_plural(ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', str_replace("@", "", $field->json_values))), '_'));
                    if($update) {
                        $var = $table->integer($field->name)->nullable()->unsigned()->change();
                        if($field->defaultvalue == "" || $field->defaultvalue == "0") {
                            $var->default(NULL);
                        } else {
                            $var->default($field->defaultvalue);
                        }
                        $table->dropForeign($field->module_obj->table . "_" . $field->name . "_foreign");
                        $table->foreign($field->name)->references('id')->on($foreign_table_name)->onUpdate('cascade')->onDelete('cascade');
                    } else {
                        $var = $table->integer($field->name)->nullable()->unsigned();
                        if($field->defaultvalue == "" || $field->defaultvalue == "0") {
                            $var->default(NULL);
                        } else {
                            $var->default($field->defaultvalue);
                        }
                        $table->foreign($field->name)->references('id')->on($foreign_table_name)->onUpdate('cascade')->onDelete('cascade');
                    }
                } else if(is_array($json_values)) {
                    if($update) {
                        if(isset($field->json_values) && is_array(json_decode($field->json_values))) {
                            $var = $table->enum($field->name, json_decode($field->json_values))->change();
                        } else {
                            $var = $table->string($field->name)->change();
                        }
                    } else {
                        if(isset($field->json_values) && is_array(json_decode($field->json_values))) {
                            $var = $table->enum($field->name, json_decode($field->json_values))->nullable();
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                    }
                    if(isset($field->json_values) && is_array(json_decode($field->json_values))) {
                        if(isset($field->defaultvalue) && $field->defaultvalue != "" && is_string($field->defaultvalue)) {
                            $var->default($field->defaultvalue);
                        } else if($field->required && isset($field->defaultvalue) && $field->defaultvalue != "") {
                            $var->default();
                        }
                    } else {
                        if($field->defaultvalue != "") {
                            $var->default($field->defaultvalue);
                        } else if($field->required) {
                            $var->default("");
                        }
                    }
                } else if(is_object($json_values)) {
                    // ############### Remaining
                    if($update) {
                        $var = $table->integer($field->name)->nullable()->unsigned()->change();
                    } else {
                        $var = $table->integer($field->name)->nullable()->unsigned();
                    }
                    // if(is_int($field->defaultvalue)) {
                    //     $var->default($field->defaultvalue);
                    //     break;
                    // }
                }
                break;
            case 'Select2_from_array':
                $json_values = json_decode($field->json_values, true);
                if(is_array($json_values)) {
                    if(isset($field->input_type) && ($field->input_type == "enum" || $field->input_type == "ENUM")) {
                        if($update) {
                            if($field->required && $nullable_required) {
                                $var = $table->enum($field->name, $json_values)->change();
                            } else {
                                $var = $table->enum($field->name, $json_values)->nullable()->change();
                            }
                        } else {
                            if($field->required && $nullable_required) {
                                $var = $table->enum($field->name, $json_values);
                            } else {
                                $var = $table->enum($field->name, $json_values)->nullable();
                            }
                        }
                        if($field->defaultvalue != "" && in_array($field->defaultvalue, $json_values)) {
                            $var->default($field->defaultvalue);
                        } else if($field->required) {
                            $var->default(NULL);
                        }
                    } else {
                        if($update) {
                            if($field->required && $nullable_required) {
                                $var = $table->string($field->name)->change();
                            } else {
                                $var = $table->string($field->name)->nullable()->change();
                            }
                        } else {
                            if($field->required && $nullable_required) {
                                $var = $table->string($field->name);
                            } else {
                                $var = $table->string($field->name)->nullable();
                            }
                        }
                        if($field->defaultvalue != "") {
                            $var->default($field->defaultvalue);
                        } else if($field->required) {
                            $var->default("");
                        }
                    }
                } else if(is_object($json_values)) {
                    // ############### Remaining
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->integer($field->name)->unsigned()->change();
                        } else {
                            $var = $table->integer($field->name)->nullable()->unsigned()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->integer($field->name)->unsigned();
                        } else {
                            $var = $table->integer($field->name)->nullable()->unsigned();
                        }
                    }
                    // if(is_int($field->defaultvalue)) {
                    //     $var->default($field->defaultvalue);
                    //     break;
                    // }
                }
                break;
            case 'Select':
                if($field->json_values == "") {
                    if(is_int($field->defaultvalue)) {
                        if($update) {
                            $var = $table->integer($field->name)->unsigned()->nullable()->change();
                        } else {
                            $var = $table->integer($field->name)->unsigned()->nullable();
                        }
                        $var->default($field->defaultvalue);
                        break;
                    } else if(is_string($field->defaultvalue)) {
                        if($update) {
                            $var = $table->string($field->name)->nullable()->change();
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                        $var->default($field->defaultvalue);
                        break;
                    }
                }
                $json_values = json_decode($field->json_values, true);
                if(is_array($json_values)) {
                    if($update) {
                        $var = $table->string($field->name)->nullable()->change();
                    } else {
                        $var = $table->string($field->name)->nullable();
                    }
                    if($field->defaultvalue != "") {
                        $var->default($field->defaultvalue);
                    } else if($field->required) {
                        $var->default("");
                    }
                } else if(is_object($json_values)) {
                    // ############### Remaining
                    if($update) {
                        $var = $table->integer($field->name)->nullable()->unsigned()->change();
                    } else {
                        $var = $table->integer($field->name)->nullable()->unsigned();
                    }
                    // if(is_int($field->defaultvalue)) {
                    //     $var->default($field->defaultvalue);
                    //     break;
                    // }
                }
                break;
            case 'Select_from_array':
                $json_values = json_decode($field->json_values, true);
                if(is_array($json_values)) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name)->change();
                        } else {
                            $var = $table->string($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name);
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                    }
                    if($field->defaultvalue != "") {
                        $var->default($field->defaultvalue);
                    } else if($field->required) {
                        $var->default("");
                    }
                } else if(is_object($json_values)) {
                    // ############### Remaining
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->integer($field->name)->unsigned()->change();
                        } else {
                            $var = $table->integer($field->name)->nullable()->unsigned()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->integer($field->name)->unsigned();
                        } else {
                            $var = $table->integer($field->name)->nullable()->unsigned();
                        }
                    }
                    // if(is_int($field->defaultvalue)) {
                    //     $var->default($field->defaultvalue);
                    //     break;
                    // }
                }
                break;
            case 'Email':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, 100)->change();
                        } else {
                            $var = $table->string($field->name, 100)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, 100);
                        } else {
                            $var = $table->string($field->name, 100)->nullable();
                        }
                    }
                } else {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength)->change();
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength);
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable();
                        }
                    }
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'File':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->integer($field->name)->change();
                    } else {
                        $var = $table->integer($field->name)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->integer($field->name);
                    } else {
                        $var = $table->integer($field->name)->nullable();
                    }
                }
                if($field->defaultvalue != "" && is_numeric($field->defaultvalue)) {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default(NULL);
                }
                break;
            case 'Files':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->string($field->name, 256)->change();
                    } else {
                        $var = $table->string($field->name, 256)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->string($field->name, 256);
                    } else {
                        $var = $table->string($field->name, 256)->nullable();
                    }
                }
                if(is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
                    $var->default($field->defaultvalue);
                } else if(is_array($field->defaultvalue)) {
                    $var->default(json_encode($field->defaultvalue));
                } else {
                    $var->default("[]");
                }
                break;
            case 'Float':
                if($update) {
                    $var = $table->float($field->name)->change();
                } else {
                    $var = $table->float($field->name);
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("0.0");
                }
                break;
            case 'HTML':
                if($update) {
                    $var = $table->string($field->name, 10000)->nullable()->change();
                } else {
                    $var = $table->string($field->name, 10000)->nullable();
                }
                if($field->defaultvalue != null) {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'Image':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->integer($field->name)->change();
                    } else {
                        $var = $table->integer($field->name)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->integer($field->name);
                    } else {
                        $var = $table->integer($field->name)->nullable();
                    }
                }
                if($field->defaultvalue != "" && is_numeric($field->defaultvalue)) {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default(NULL);
                } else {
                    $var->default(NULL);
                }
                break;
            case 'Json':
                if($update) {
                    $var = $table->string($field->name, 256)->change();
                } else {
                    $var = $table->string($field->name, 256);
                }
                if(is_array($field->defaultvalue)) {
                    $field->defaultvalue = json_encode($field->defaultvalue);
                    $var->default($field->defaultvalue);
                } else if(is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
                    $var->default($field->defaultvalue);
                } else if($field->defaultvalue == "" || $field->defaultvalue == null) {
                    $var->default("[]");
                } else if(is_string($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    $var->default($field->defaultvalue);
                } else if(is_int($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("[]");
                }
                break;
            case 'Number':
                $var = null;
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->integer($field->name, false)->change();
                    } else {
                        $var = $table->integer($field->name, false)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->integer($field->name, false);
                    } else {
                        $var = $table->integer($field->name, false)->nullable();
                    }
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else {
                    $var->default("0");
                }
                break;
            case 'Phone':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name)->change();
                        } else {
                            $var = $table->string($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name);
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                    }
                } else {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength)->change();
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength);
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable();
                        }
                    }
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'Multiselect':
                if($update) {
                    $var = $table->string($field->name, 256)->change();
                } else {
                    $var = $table->string($field->name, 256);
                }
                if(is_array($field->defaultvalue)) {
                    $field->defaultvalue = json_encode($field->defaultvalue);
                    $var->default($field->defaultvalue);
                } else if(is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
                    $var->default($field->defaultvalue);
                } else if($field->defaultvalue == "" || $field->defaultvalue == null) {
                    $var->default("[]");
                } else if(is_string($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    $var->default($field->defaultvalue);
                } else if(is_int($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    //echo "int: ".$field->defaultvalue;
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("[]");
                }
                break;
            case 'Select2_multiple':
                if($update) {
                    $var = $table->string($field->name, 256)->change();
                } else {
                    $var = $table->string($field->name, 256);
                }
                if(is_array($field->defaultvalue)) {
                    $field->defaultvalue = json_encode($field->defaultvalue);
                    $var->default($field->defaultvalue);
                } else if(is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
                    $var->default($field->defaultvalue);
                } else if($field->defaultvalue == "" || $field->defaultvalue == null) {
                    $var->default("[]");
                } else if(is_string($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    $var->default($field->defaultvalue);
                } else if(is_int($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    //echo "int: ".$field->defaultvalue;
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("[]");
                }
                break;
            case 'Name':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        $var = $table->string($field->name)->change();
                    } else {
                        $var = $table->string($field->name);
                    }
                } else {
                    if($update) {
                        $var = $table->string($field->name, $field->maxlength)->change();
                    } else {
                        $var = $table->string($field->name, $field->maxlength);
                    }
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'Password':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name)->change();
                        } else {
                            $var = $table->string($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name)->nullable();
                        } else {
                            $var = $table->string($field->name);
                        }
                    }
                } else {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength)->change();
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength);
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable();
                        }
                    }
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'Radio':
                $var = null;
                if($field->json_values == "") {
                    if(is_int($field->defaultvalue)) {
                        if($update) {
                            $var = $table->integer($field->name)->unsigned()->change();
                        } else {
                            $var = $table->integer($field->name)->unsigned();
                        }
                        $var->default($field->defaultvalue);
                        break;
                    } else if(is_string($field->defaultvalue)) {
                        if($update) {
                            if($field->required && $nullable_required) {
                                $var = $table->string($field->name)->change();
                            } else {
                                $var = $table->string($field->name)->nullable()->change();
                            }
                        } else {
                            if($field->required && $nullable_required) {
                                $var = $table->string($field->name);
                            } else {
                                $var = $table->string($field->name)->nullable();
                            }
                        }
                        $var->default($field->defaultvalue);
                        break;
                    }
                } else if(is_string($field->json_values) && starts_with($field->json_values, "@")) {
                    if($update) {
                        $var = $table->integer($field->name)->unsigned()->change();
                    } else {
                        $var = $table->integer($field->name)->unsigned();
                    }
                    break;
                }
                $json_values = json_decode($field->json_values, true);
                if(is_array($json_values)) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name)->change();
                        } else {
                            $var = $table->string($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name);
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                    }
                    if($field->defaultvalue != "") {
                        $var->default($field->defaultvalue);
                    } else if($field->required) {
                        $var->default("");
                    }
                } else if(is_object($json_values)) {
                    // ############### Remaining
                    if($update) {
                        $var = $table->integer($field->name)->unsigned()->change();
                    } else {
                        $var = $table->integer($field->name)->unsigned();
                    }
                    // if(is_int($field->defaultvalue)) {
                    //     $var->default($field->defaultvalue);
                    //     break;
                    // }
                }
                break;
            case 'Text':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name)->change();
                        } else {
                            $var = $table->string($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name);
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                    }
                } else {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength)->change();
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength);
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable();
                        }
                    }
                }
                if($field->defaultvalue != null) {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'Taginput':
                $var = null;
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->string($field->name, 1000)->change();
                    } else {
                        $var = $table->string($field->name, 1000)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->string($field->name, 1000);
                    } else {
                        $var = $table->string($field->name, 1000)->nullable();
                    }
                }
                if(is_string($field->defaultvalue) && starts_with($field->defaultvalue, "[")) {
                    $field->defaultvalue = json_decode($field->defaultvalue, true);
                }
                
                if(is_string($field->defaultvalue)) {
                    $field->defaultvalue = json_encode([$field->defaultvalue]);
                    //echo "string: ".$field->defaultvalue;
                    $var->default($field->defaultvalue);
                } else if(is_array($field->defaultvalue)) {
                    $field->defaultvalue = json_encode($field->defaultvalue);
                    //echo "array: ".$field->defaultvalue;
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'Textarea':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->text($field->name)->change();
                        } else {
                            $var = $table->text($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->text($field->name);
                        } else {
                            $var = $table->text($field->name)->nullable();
                        }
                    }
                } else {
                    if($update) {
                        $var = $table->string($field->name, $field->maxlength)->nullable()->change();
                    } else {
                        $var = $table->string($field->name, $field->maxlength)->nullable();
                    }
                    if($field->defaultvalue != "") {
                        $var->default($field->defaultvalue);
                    } else if($field->required) {
                        $var->default("");
                    }
                }
                break;
            case 'TextField':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name)->change();
                        } else {
                            $var = $table->string($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name);
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                    }
                } else {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength)->change();
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength);
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable();
                        }
                    }
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'URL':
                $var = null;
                if($field->maxlength == 0) {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name)->change();
                        } else {
                            $var = $table->string($field->name)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name);
                        } else {
                            $var = $table->string($field->name)->nullable();
                        }
                    }
                } else {
                    if($update) {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength)->change();
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable()->change();
                        }
                    } else {
                        if($field->required && $nullable_required) {
                            $var = $table->string($field->name, $field->maxlength);
                        } else {
                            $var = $table->string($field->name, $field->maxlength)->nullable();
                        }
                    }
                }
                if($field->defaultvalue != "") {
                    $var->default($field->defaultvalue);
                } else if($field->required) {
                    $var->default("");
                }
                break;
            case 'Week':
                break;
            case 'Month':
                if($update) {
                    if($field->required && $nullable_required) {
                        $var = $table->date($field->name)->change();
                    } else {
                        $var = $table->date($field->name)->nullable()->change();
                    }
                } else {
                    if($field->required && $nullable_required) {
                        $var = $table->date($field->name);
                    } else {
                        $var = $table->date($field->name)->nullable();
                    }
                }
                
                if($field->defaultvalue == NULL || $field->defaultvalue == "" || $field->defaultvalue == "NULL") {
                    $var->default(NULL);
                } else if($field->defaultvalue == "now()") {
                    $var->default(NULL);
                } else if($field->required) {
                    $var->default("1970-01");
                } else {
                    $var->default($field->defaultvalue);
                }
                break;
            case 'Browse':
                $var = $table->string($field->name, $field->maxlength)->nullable();
                $var->default(NULL);
                
                break;
            default:
                $var = $table->string($field->name, $field->maxlength)->nullable();
                $var->default(NULL);

        }
        
        // set column unique
        if($update) {
            if($isFieldTypeChange) {
                if($field->unique && $var != null && $field->maxlength < 256) {
                    // $table->unique($field->name);
                }
            }
        } else {
            if($field->unique && $var != null && $field->maxlength < 256) {
                // $table->unique($field->name);
            }
        }
    }

    /**
     * Validates if given view_column_name exists in fields array
     *
     * @param $fields Array of fields from migration file
     * @param $view_col View Column Name
     * @return bool returns true if view_column_name found in fields otherwise false
     */
    public static function validate_represent_attrumn($fields, $view_col)
    {
        $found = false;
        foreach($fields as $field) {
            if($field->name == $view_col) {
                $found = true;
                break;
            }
        }
        return $found;
    }

    /**
     * This method process and alters user created migration fields array to fit into standard field Context / Metedata
     *
     * Note: field array type change
     * Earlier we were taking sequential array for fields, but from version 1.1 we are using different format
     * with associative array. It also supports old sequential array. This step is taken to accommodate "show_index"
     * which allows field to be listed in index/listing table. This step will also allow us to take more Metadata about
     * field.
     *
     * @param $module_name Module Name
     * @param $fields Fields Array
     * @return array Returns Array of Field Objects
     * @throws Exception Throws exception if field missing any details like name, label, field_type
     */
    public static function format_fields($module_name, $fields)
    {
        $out = array();
        foreach($fields as $field) {
            // Check if field format is New
            if(CustomHelper::is_assoc_array($field)) {
                $obj = (object)$field;
                
                if(!isset($obj->name)) {
                    throw new Exception("Migration " . $module_name . " -  Field does not have name", 1);
                } else if(!isset($obj->label)) {
                    throw new Exception("Migration " . $module_name . " -  Field does not have label", 1);
                } else if(!isset($obj->field_type)) {
                    throw new Exception("Migration " . $module_name . " -  Field does not have field_type", 1);
                }
                if(!isset($obj->unique)) {
                    $obj->unique = 0;
                }
                if(!isset($obj->defaultvalue)) {
                    $obj->defaultvalue = '';
                }
                if(!isset($obj->minlength)) {
                    $obj->minlength = 0;
                }
                if(!isset($obj->maxlength)) {
                    $obj->maxlength = 0;
                } else {
                    // Because maxlength above 256 will not be supported by Unique
                    if($obj->unique) {
                        $obj->maxlength = 250;
                    } else {
                        $obj->maxlength = $obj->maxlength;
                    }
                }
                if(!isset($obj->required)) {
                    $obj->required = 0;
                }
                if(isset($obj->nullable_required) && $obj->nullable_required == false) {
                    $obj->nullable_required = false;
                } else {
                    $obj->nullable_required = true;
                }
                if(!isset($obj->show_index)) {
                    $obj->show_index = 1;
                } else {
                    if($obj->show_index == true) {
                        $obj->show_index = 1;
                    } else {
                        $obj->show_index = 0;
                    }
                }
                
                if(!isset($obj->json_values)) {
                    $obj->json_values = "";
                } else {
                    if(is_array($obj->json_values)) {
                        $obj->json_values = json_encode($obj->json_values);
                    } else {
                        $obj->json_values = $obj->json_values;
                    }
                }
                // var_dump($obj);
                $out[] = $obj;
            } else {
                // Handle Old field format - Sequential Array
                $obj = (Object)array();
                $obj->name = $field[0];
                $obj->label = $field[1];
                $obj->field_type = $field[2];
                
                if(!isset($field[3])) {
                    $obj->unique = 0;
                } else {
                    $obj->unique = $field[3];
                }
                if(!isset($field[4])) {
                    $obj->defaultvalue = '';
                } else {
                    $obj->defaultvalue = $field[4];
                }
                if(!isset($field[5])) {
                    $obj->minlength = 0;
                } else {
                    $obj->minlength = $field[5];
                }
                if(!isset($field[6])) {
                    $obj->maxlength = 0;
                } else {
                    // Because maxlength above 256 will not be supported by Unique
                    if($obj->unique) {
                        $obj->maxlength = 250;
                    } else {
                        $obj->maxlength = $field[6];
                    }
                }
                if(!isset($field[7])) {
                    $obj->required = 0;
                } else {
                    $obj->required = $field[7];
                }
                $obj->show_index = 1;
                
                if(!isset($field[8])) {
                    $obj->json_values = "";
                } else {
                    if(is_array($field[8])) {
                        $obj->json_values = json_encode($field[8]);
                    } else {
                        $obj->json_values = $field[8];
                    }
                }
                $out[] = $obj;
            }
        }
        return $out;
    }
    
    /**
     * Create Validations rules array for Laravel Validations using Module Field Context / Metadata
     * 
     * This generates array of validation rules for whole Module
     *
     * @param $module_name Module Name
     * @param $request \Illuminate\Http\Request Object
     * @param bool $isEdit Is this a Update or Store Request
     * @return array Returns Array to validate given Request
     */
    public static function validateRules($module_name, $request, $isEdit = false)
    {
        if(isset($module_name) && !empty($module_name)) {
            if(is_string($module_name)) {
                $module = self::where('name',$module_name)->first();
            } elseif(is_object($module_name)) {
                $module = $module_name;
            } else {
                $module = false;
            }
        } else {
            $module = false;
        }
        $rules = [];
        if(isset($module)) {
            $ftypes = FieldType::getFTypes2();
            $add_from = true;
            foreach($module->fields as $field) {
                if($isEdit && !isset($request->{$field->name})) {
                    $add_from = false;
                } else {
                    $add_from = true;
                }
                if($add_from) {
                    $col = "";
                    if($field->required) {
                        $col .= "required|";
                    }
                    if(isset($field->field_type["id"]) && in_array($ftypes[$field->field_type["id"]], array("Currency", "Decimal"))) {
                        // No min + max length
                    } else {
                        if($field->minlength != 0) {
                            $col .= "min:" . $field->minlength . "|";
                        }
                        if($field->maxlength != 0) {
                            $col .= "max:" . $field->maxlength . "|";
                        }
                    }
                    if($field->unique && !$isEdit) {
                        $col .= "unique:" . $module->table_name.','.$field->name.','.$request->{$field->name};
                    } else if($isEdit && $field->unique) {
                        $col .= "unique:" . $module->table_name.','.$field->name.','.$request->segment(3). ',_id';
                    }
                    // 'name' => 'required|unique|min:5|max:256',
                    // 'author' => 'required|max:50',
                    // 'price' => 'decimal',
                    // 'pages' => 'integer|max:5',
                    // 'genre' => 'max:500',
                    // 'description' => 'max:1000'
                    if($col != "") {
                        $rules[$field->name] = trim($col, "|");
                    }
                }
            }
            
            // echo "<pre>";
            // echo json_encode($rules, JSON_PRETTY_PRINT);
            // echo "</pre>";
            // exit;
            return $rules;
        } else {
            return $rules;
        }
    }
    
    /**
     * Get Specific Module Access for login user or specific user ($user_id)
     *
     * self::hasAccess($module_id, $access_type, $user_id);
     *
     * @param $module_id Module ID / Name
     * @param string $access_type Access Type - list / view / create / edit / delete
     * @param int $user_id User id for which Access will be checked
     * @return bool Returns true if access is there or false
     */
    public static function hasAccessRole($module_id, $access_type = "list", $user_id = false)
    {
        return true;
        if(\Auth::user()->isSuperAdmin()) {
            return true;
        }

        $roles = array();
        
        if(is_string($module_id)) {
            $module = self::make($module_id);
            $module_id = $module->module->id;
        }
        
        if($access_type == null || $access_type == "") {
            $access_type = "view";
        }
        
        if($user_id) {
            $user = \App\User::find($user_id);
            if(isset($user->id)) {
                $roles = $user->roles();
            }
        } else {
            $roles = \Auth::user()->roles();
        }
        foreach($roles->get() as $role) {
            $module_permission = DB::table('role_modules')->where('role_id', $role->id)->where('module_id', $module_id)->first();
            if(isset($module_permission->id)) {
                if(isset($module_permission->{$access_type}) && $module_permission->{$access_type} == 1) {
                    return true;
                } else {
                    continue;
                }
            } else {
                continue;
            }
        }
        return false;
    }

    /**
     * Get Specific Module Access for login user or specific user ($user_id)
     *
     * self::hasAccess($module_id, $access_type, $user_id);
     *
     * @param $module_id Module ID / Name
     * @param string $access_type Access Type - list / view / create / edit / delete
     * @param int $user_id User id for which Access will be checked
     * @return bool Returns true if access is there or false
     */
    public static function hasAccess($module_id, $access_type = "list", $user_id = false)
    {
        if(\Auth::user()->isAdmin()) {
            return true;
        }
        
        $users = array();
        
        if(is_string($module_id)) {
            $module = self::make($module_id);
            $module_id = isset($module->module->id) ? $module->module->id : null;
        }
        
        // if(\Auth::user()->isAdmin() && !(isset($module->module->name) && in_array($module->module->name, ['ProductCategories','ItemMasters','SpecificationMasters','CertificateOfAnalyses']))) {
        //     return true;
        // }

        if($access_type == null || $access_type == "") {
            $access_type = "list";
        }
        
        if($user_id) {
            $user = \App\User::find($user_id);
            if(isset($user->id)) {
                $user = $user;
            } else {
                $user = [];
            }
        } else {
            $user = \Auth::user();
        }
        if(isset($user->id)) {
            $module_permission = UserModule::where('user_id', $user->id)->where('module_id', $module_id)->first();
            if(isset($module_permission->id)) {
                if(isset($module_permission->{$access_type}) && $module_permission->{$access_type} == 1) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Get Module Access for all users or specific user
     *
     * $user_accesses = self::getUserAccess($id);
     *
     * @param $module_id Module ID
     * @param int $specific_user Specific user id
     * @return array Array of Users with accesses
     */
    public static function getUserAccess($module_id, $specific_user = 0)
    {
        $module = self::find($module_id);
        $module = self::make($module->name);
        
        if($specific_user) {
            $users_arr = User::where('_id', $specific_user)->get();
        } else {
            $users_arr = User::all();
        }
        $users = array();
        
        // $arr_field_access = array(
        //     'invisible' => 0,
        //     'readonly' => 1,
        //     'write' => 2
        // );
        
        foreach($users_arr as $user) {
            // get Current Module permissions for this user
            $module_perm = UserModule::where('user_id', $user->id)->where('module_id', $module->module->id)->first();
            if(isset($module_perm->id)) {
                // set db values
                $user->list = $module_perm->list;
                $user->view = $module_perm->view;
                $user->create = $module_perm->create;
                $user->review = $module_perm->review;
                $user->approve = $module_perm->approve;
                $user->edit = $module_perm->edit;
                $user->delete = $module_perm->delete;
            } else {
                $user->list = false;
                $user->view = false;
                $user->create = false;
                $user->review = false;
                $user->approve = false;
                $user->edit = false;
                $user->delete = false;
            }

            $users[] = $user;
        }
        return collect($users);
    }

    /**
     * Get Module Access for all roles or specific role
     *
     * $role_accesses = self::getRoleAccess($id);
     *
     * @param $module_id Module ID
     * @param int $specific_role Specific role id
     * @return array Array of Roles with accesses
     */
    public static function getRoleAccess($module_id, $specific_role = 0)
    {
        $module = self::find($module_id);
        $module = self::make($module->name);
        
        if($specific_role) {
            $roles_arr = Role::where('id', $specific_role)->get();
        } else {
            $roles_arr = Role::all();
        }
        $roles = array();
        
        $arr_field_access = array(
            'invisible' => 0,
            'readonly' => 1,
            'write' => 2
        );
        
        foreach($roles_arr as $role) {
            // get Current Module permissions for this role
            
            $module_perm = DB::table('role_modules')->where('role_id', $role->id)->where('module_id', $module->module->id)->first();
            if(isset($module_perm->id)) {
                // set db values
                $role->view = $module_perm->view;
                $role->create = $module_perm->create;
                $role->edit = $module_perm->edit;
                $role->delete = $module_perm->delete;
            } else {
                $role->view = false;
                $role->create = false;
                $role->edit = false;
                $role->delete = false;
            }

            // get Current Module Fields permissions for this role
            
            $role->fields = array();
            foreach($module->module->fields as $field) {
                // find role field permission
                // $field_perm = DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field['id'])->first();
                
                // if(isset($field_perm->id)) {
                //     $field['access'] = $arr_field_access[$field_perm->access];
                // } else {
                //     $field['access'] = 0;
                // }
                // $role->fields[$field['id']] = $field;
                //$role->fields[$field['id']] = $field_perm->access;
            }
            $roles[] = $role;
        }
        return collect($roles);
    }

    /**
     * Get list of Columns to display in Index Page for a particular Module
     * Also Filters the columns for Access control
     *
     * self::getListingColumns('Employees')
     *
     * @param $module_id_name Module Name / ID
     * @param bool $isObjects Whether you want just Names of Columns or Column Field Objects
     * @return array Array of Columns Names/Objects
     */
    public static function getListingColumns($module_id_name, $isObjects = false)
    {
        $module = null;
        if(is_int($module_id_name)) {
            $module = self::make($module_id_name)->module;
        } else {
            $module = self::where('name', $module_id_name)->first();
        }
        $show_indexs = Field::where('module_id', $module->id)->where('show_index', 1)->get()->toArray();
        
        if($isObjects) {
            $id_col = array('label' => 'id', 'name' => 'id');
        } else {
            $id_col = 'id';
        }
        $show_indexs_temp = array($id_col);
        foreach($show_indexs as $col) {
            // if(self::hasFieldAccess($module->id, $col['id'])) {
                if($isObjects) {
                    $show_indexs_temp[] = $col;
                } else {
                    $show_indexs_temp[] = $col['name'];
                }
            // }
        }
        return $show_indexs_temp;
    }
    
    /**
     * Set Default Access for given Module and Role
     * Helps to set Full Module Access for Super Admin
     *
     * self::setDefaultRoleAccess($module_id, $role_id);
     *
     * @param $module_id Module ID
     * @param $role_id Role ID
     * @param string $access_type Access Type - full / readonly
     */
    public static function setDefaultRoleAccess($module_id, $role_id, $access_type = "readonly", $delete = true)
    {
        $module = self::find($module_id);
        // $module = self::make($module->name);
        
        // Log::debug('Module:setDefaultRoleAccess ('.$module_id.', '.$role_id.', '.$access_type.')');
        
        $role = Role::find($role_id);
        
        $access_view = 0;
        $access_create = 0;
        $access_edit = 0;
        $access_delete = 0;
        $access_fields = "invisible";
        
        if($access_type == "full") {
            $access_view = 1;
            $access_create = 1;
            $access_edit = 1;
            $access_delete = $delete;
            // $access_fields = "write";
            
        } else if($access_type == "readonly") {
            $access_view = 1;
            $access_create = 0;
            $access_edit = 0;
            $access_delete = 0;
            
            // $access_fields = "readonly";
        }
        
        $now = date("Y-m-d H:i:s");
        
        // 1. Set Module Access
        
        if($role->modules()->where('module_id', $module->id)->get()->count() == 0) {
            $role->modules()->attach($module->id, ['view' => $access_view, 'create' => $access_create, 'edit' => $access_edit, 'delete' => $access_delete]);
        } else {
            $role->modules()->updateExistingPivot($module->id, ['view' => $access_view, 'create' => $access_create, 'edit' => $access_edit, 'delete' => $access_delete]);
        }
        
        // 2. Set Module Fields Access
        
        // foreach($module->fields as $field) {
        //     // find role field permission
        //     $field_perm = DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field['id'])->first();
        //     if(!isset($field_perm->id)) {
        //         DB::insert('insert into role_module_fields (role_id, field_id, access, created_at, updated_at) values (?, ?, ?, ?, ?)', [$role->id, $field['id'], $access_fields, $now, $now]);
        //     } else {
        //         DB::table('role_module_fields')->where('role_id', $role->id)->where('field_id', $field['id'])->update(['access' => $access_fields]);
        //     }
        // }
    }

    /**
     * Get the fields of this module.
     */
    public function get_field($name)
    {
        return $this->fields->where('name',$name)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */
    
    /**
     * Get the fields of this module.
     */
    public function fields()
    {
        return $this->hasMany('Shagyt\lvcrud\Models\Field', 'module_id', 'id');
    }

     /**
     * The users that belong to the role.
     */
    public function users()
    {
        return $this->belongsToMany('App\User');
    }
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
