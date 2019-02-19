<?php

namespace Shagyt\lvcrud\Helpers\crud;

use Schema;
use Collective\Html\FormFacade as Form;
use DB;
use Shagyt\lvcrud\Models\Module;
use Shagyt\lvcrud\Models\FieldType;
use Shagyt\lvcrud\Models\Field;
use Shagyt\lvcrud\Models\Upload;
use Shagyt\lvcrud\Helpers\Inflect;

/**
 * Class FormBuilder
 *
 */
class FormBuilder
{
    /**
     * view input field enclosed within form.
     *
     * Uses blade syntax @input('name')
     *
     * @param $field_name Field Name for which input has be created
     */
    public static function input($crud, $field_name, $params = [], $default_val = null, $required2 = null, $class = 'form-control')
    {       

            $row = null;
            $field = [];
            $fields = $crud->fields;
            if(!isset($fields[$field_name]->name)) {
                return;
            }
            if(isset($crud->row)) {
                $row = $crud->row;
                $field['value'] = $fields[$field_name]->value = $crud->row->$field_name;
            } else {
                if(isset($default_val)) {
                    $field['value'] = $default_val;
                }
            }
            
            // echo "<pre>";
            // echo json_encode($fields, JSON_PRETTY_PRINT);
            // echo "</pre>";
            // return;
            // $field = $fields[$field_name];
            $field['name'] = $fields[$field_name]->name;
            if($fields[$field_name]->required) {
                $field['label'] = $fields[$field_name]->label." <span style='color:red;'>*</span>";
            } else {
                $field['label'] = $fields[$field_name]->label;
            }
            
            if(isset($fields[$field_name]->field_type->name)) {
                $field['type'] = strtolower($fields[$field_name]->field_type->name);
            } else {
                $field['type'] = strtolower($fields[$field_name]->field_type);
            }
            
            // return json_encode($fields[$field_name]->field_type->name);
            if(isset($fields[$field_name]->field_type) && $fields[$field_name]->field_type == "") {
                $field_type = "Text";
            } else if(isset($fields[$field_name]->field_type)) {
                if(isset($fields[$field_name]->field_type->name)) {
                    $field_type = $fields[$field_name]->field_type->name;
                } else {
                    $field_type = $fields[$field_name]->field_type;
                }
            }
            $label = $fields[$field_name]->label;
            
            $field['attributes'] = ['placeholder' => 'Enter ' . $label];
            $unique = $fields[$field_name]->unique;
            $field['default'] = $fields[$field_name]->defaultvalue;
            $minlength = $fields[$field_name]->minlength;
            $maxlength = $fields[$field_name]->maxlength;
            $required = $fields[$field_name]->required;
            if(isset($fields[$field_name]->json_values)) {
                if(is_array($fields[$field_name]->json_values)) {
                    $json_values = json_encode($fields[$field_name]->json_values);
                } else {
                    $json_values = $fields[$field_name]->json_values;
                }
            } else {
                $json_values = "";
            }
            
            if(isset($json_values) && !empty($json_values) && is_string($json_values) && starts_with($json_values, "@")) {

                $module = Module::where('name', str_replace("@", "", $json_values))->first();
                if(!isset($module->model)) {
                    $json_values_arr = explode('|',$json_values);
                    $module = (object)[];
                    $module->name = $module->model = collect(str_replace("@", "", $json_values_arr))->first();
                    $module->represent_attr = collect(str_replace("|", "", $json_values_arr))->last();
                }
            }
            
            if($required2 != null) {
                $required = $required2;
            }
            
            // $field_type = FieldType::find($field_type);
            $out = '';
            
            
            // $field = collect($field)->forget(['id','field_type','name','crud_id','field_type_id']);

            // echo "<pre>";
            // echo json_encode($field, JSON_PRETTY_PRINT);
            // echo "</pre>";
            // return ;
            $type = "";
            switch($field_type) {
                case 'Text':

                    $type = 'text';

                    break;
                case 'Email':

                    $type = 'email';

                    break;
                case 'Textarea':

                    $type = 'textarea';

                    break;
                case 'Number':

                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }

                    $type = 'number';

                    break;
                case 'Prefix_Number':
                
                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }

                    if(isset($fields[$field_name]['prefix']) && $fields[$field_name]['prefix'] != "") {
                        $field['prefix'] = $fields[$field_name]['prefix'];
                    } else {
                        $field['prefix'] = '<i class="fa fa-user"></i>';
                    }
                    
                    $type = 'number';

                    break;
                case 'Suffix_Number':
                
                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }

                    if(isset($fields[$field_name]['suffix']) && $fields[$field_name]['suffix'] != "") {
                        $field['suffix'] = $fields[$field_name]['suffix'];
                    } else {
                        $field['suffix'] = '<i class="fa fa-user"></i>';
                    }
                    
                    $type = 'number';

                    break;
                case 'Prefix_Suffix_Number':
                
                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }
                    
                
                    if(isset($fields[$field_name]['prefix']) && $fields[$field_name]['prefix'] != "") {
                        $field['prefix'] = $fields[$field_name]['prefix'];
                    } else {
                        $field['prefix'] = '<i class="fa fa-user"></i>';
                    }

                    if(isset($fields[$field_name]['suffix']) && $fields[$field_name]['suffix'] != "") {
                        $field['suffix'] = $fields[$field_name]['suffix'];
                    } else {
                        $field['suffix'] = '<i class="fa fa-user"></i>';
                    }
                    
                    $type = 'number';

                    break;
                case 'Float':
                
                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }
                    
                
                    $field['attributes'] = array_merge($field['attributes'],['step' => 0.01]);
                    
                    $type = 'number';

                    break;
                case 'Prefix_Float':
                
                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }
                    
                    if(isset($fields[$field_name]['prefix']) && $fields[$field_name]['prefix'] != "") {
                        $field['prefix'] = $fields[$field_name]['prefix'];
                    } else {
                        $field['prefix'] = '<i class="fa fa-user"></i>';
                    }
                    $field['attributes'] = array_merge($field['attributes'],['step' => 0.01]);
                    
                    $type = 'number';

                    break;
                case 'Suffix_Float':
                
                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }
                    
                    if(isset($fields[$field_name]['suffix']) && $fields[$field_name]['suffix'] != "") {
                        $field['suffix'] = $fields[$field_name]['suffix'];
                    } else {
                        $field['suffix'] = '<i class="fa fa-user"></i>';
                    }
                    $field['attributes'] = array_merge($field['attributes'],['step' => 0.01]);
                    
                    $type = 'number';

                    break;
                case 'Prefix_Suffix_Float':
                
                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }
                    
                    if(isset($fields[$field_name]['prefix']) && $fields[$field_name]['prefix'] != "") {
                        $field['prefix'] = $fields[$field_name]['prefix'];
                    } else {
                        $field['prefix'] = '<i class="fa fa-user"></i>';
                    }

                    if(isset($fields[$field_name]['suffix']) && $fields[$field_name]['suffix'] != "") {
                        $field['suffix'] = $fields[$field_name]['suffix'];
                    } else {
                        $field['suffix'] = '<i class="fa fa-user"></i>';
                    }
                    $field['attributes'] = array_merge($field['attributes'],['step' => 0.01]);
                    
                    $type = 'number';

                    break;
                case 'Currency':
                
                    if(isset($minlength) && $minlength) {
                        $params['min'] = $minlength;
                    }
                    if(isset($maxlength) && $maxlength) {
                        $params['max'] = $maxlength;
                    }

                    $field['attributes'] = array_merge($field['attributes'],['step' => 0.01]);
            
                    $type = 'number';

                    break;
                case 'Password':

                    $type = "password";

                    break;
                case 'Phone':

                    $type = "phone";

                    break;
                case 'Radio':
                    $field['inline'] = 1;

                    $class = "flat-green";
                    if(!starts_with($json_values, "@") && is_array(json_decode($json_values))) {
                        $arr = [];
                        $collection = collect(json_decode($json_values));
                        foreach ($collection as $key => $value) {
                            $arr[$value] = $value;
                        }
                        $field['options'] = $arr;
                    } else {
                        $field['options'] = ['No', 'Yes'];
                    }

                    $type = "radio";

                    break;
                case 'Checkbox':
                    
                    $class = "";
                    $field['inline'] = 1;
                    
                    if(!starts_with($json_values, "@") && is_array(json_decode($json_values))) {
                        $arr = [];
                        $collection = collect(json_decode($json_values));
                        foreach ($collection as $key => $value) {
                            $arr[$value] = $value;
                        }
                        $field['options'] = $arr;
                    } else {
                        $field['options'] = ['Yes', 'No'];
                    }
                    $type = "checkbox";
                    
                    break;
                case 'CKEditor':

                    if(isset($params['only_button']) && is_array($params['only_button'])) {
                        $field['attribute'] = $params['only_button'];
                    }
                    unset($params['only_button']);
                    
                    if(isset($required) && $required) {
                        $class = "form-control ckeditor_required";
                    } else {
                        $class = "form-control";
                    }
                    $type = "ckeditor";

                    break;
                case 'Hidden':

                    $type = "hidden";

                    break;
                case 'Week':

                    $type = "week";

                    break;
                case 'Month':

                    $class = "form-control month_combodate";
                    $type = "month";

                    break;
                case 'Date':

                    $type = "date";

                    break;
                case 'Date_picker':

                    $type = "date_picker";

                    break;
                case 'Datetime':

                    $type = "datetime";

                    break;
                case 'Datetime_picker':

                    $type = "datetime_picker";

                    break;
                case 'Date_range':
                    
                    // $field['default'] = json_encode(['start'=> \Carbon::now(),'end'=>\Carbon::now()->addMonth()]);

                    $type = "date_range";

                    break;
                case 'Address':

                    $type = "address";

                    break;
                case 'Select':
                    if(isset($module) && isset($module->model)) {
                        $class = "form-control";
                        if($module->name == "Users") {
                            $field['model'] = "App\\".$module->model;
                        } else {
                            $field['model'] = "App\Models\\".$module->model;
                        }
                        if(isset($params['attribute']) && is_array($params['attribute'])) {
                            $field['attribute'] = $params['attribute'];
                        } else {
                            $field['attribute'] = $module->represent_attr;
                        }
                        $field['options'] = $json_values;
                        if(isset($required) && $required) {
                            $field['allows_null'] = false;
                        } else {
                            $field['allows_null'] = true;
                        }
                    } else {
                        $arr = [];
                        $collection = collect(json_decode($json_values));
                        foreach ($collection as $key => $value) {
                            $arr[$value] = $value;
                        }
                        $class = "form-control";
                        $field['options'] = $arr;
                        if(isset($required) && $required) {
                            $field['allows_null'] = false;
                        } else {
                            $field['allows_null'] = true;
                        }
                    }
                    $type = "select";

                    break;
                case 'Multiselect':
                    if(isset($module)) {
                        if($module->name == "Users") {
                            $field['model'] = "App\\".$module->model;
                        } else {
                            $field['model'] = "App\Models\\".$module->model;
                        }

                        if(isset($params['attribute']) && is_array($params['attribute'])) {
                            $field['attribute'] = $params['attribute'];
                        } else {
                            $field['attribute'] = $module->represent_attr;
                        }
                        $field['options'] = $json_values;
                    } else {
                        $arr = [];
                        $collection = collect(json_decode($json_values));
                        foreach ($collection as $key => $value) {
                            $arr[$value] = $value;
                        }
                        $class = "form-control select2_multiple";
                        $field['options'] = $arr;
                    }
                    
                    if(isset($required) && $required) {
                        $field['allows_null'] = false;
                    } else {
                        $field['allows_null'] = true;
                    }
                    $type = "multiselect";

                    break;
                case 'Select_from_array':
                    $arr = [];
                    $collection = collect(json_decode($json_values));
                    foreach ($collection as $key => $value) {
                        $arr[$value] = $value;
                    }
                    
                    $field['options'] = $arr;
                    if(isset($required) && $required) {
                        $field['allows_null'] = false;
                    } else {
                        $field['allows_null'] = true;
                    }
                    $type = "select_from_array";

                    break;
                case 'Select2':
                    if(isset($module) && isset($module->model)) {
                        $class = "form-control select2_field";
                        
                        if($module->name == "Users") {
                            $field['model'] = "App\\".$module->model;
                        } else {
                            $field['model'] = "App\Models\\".$module->model;
                        }
                        if(isset($params['attribute']) && is_array($params['attribute'])) {
                            $field['attribute'] = $params['attribute'];
                        } else {
                            $field['attribute'] = $module->represent_attr;
                        }
                        $field['options'] = $json_values;
                        if(isset($required) && $required) {
                            $field['allows_null'] = false;
                        } else {
                            $field['allows_null'] = true;
                        }
                    } else {
                        $arr = [];
                        $collection = collect(json_decode($json_values));
                        foreach ($collection as $key => $value) {
                            $arr[$value] = $value;
                        }
                        $class = "form-control select2_field";
                        $field['options'] = $arr;
                        if(isset($required) && $required) {
                            $field['allows_null'] = false;
                        } else {
                            $field['allows_null'] = true;
                        }
                    }

                    $type = "select2";

                    break;
                case 'Select2_multiple':

                    $class = "form-control select2_multiple";
                    $field['attributes'] = ['placeholder' => 'Select ' . $label];
                    if(isset($module)) {
                        
                        if($module->name == "Users") {
                            $field['model'] = "App\\".$module->model;
                        } else {
                            $field['model'] = "App\Models\\".$module->model;
                        }
                        if(isset($params['attribute']) && is_array($params['attribute'])) {
                            $field['attribute'] = $params['attribute'];
                        } else {
                            $field['attribute'] = $module->represent_attr;
                        }
                        $field['options'] = $json_values;
                    } else {
                        $arr = [];
                        $collection = collect(json_decode($json_values));
                        foreach ($collection as $key => $value) {
                            $arr[$value] = $value;
                        }
                        $class = "form-control select2_multiple";
                        $field['options'] = $arr;
                    }
                    
                    if(isset($required) && $required) {
                        $field['allows_null'] = false;
                    } else {
                        $field['allows_null'] = true;
                    }
                    $type = "select2_multiple";

                    break;
                case 'Select2_from_array':
                    $arr = [];
                    $collection = collect(json_decode($json_values));
                    foreach ($collection as $key => $value) {
                        $arr[$value] = $value;
                    }
                    $class = "form-control select2_from_array";
                    $field['options'] = $arr;
                    if(isset($required) && $required) {
                        $field['allows_null'] = false;
                    } else {
                        $field['allows_null'] = true;
                    }
                    $type = "select2_from_array";

                    break;
                case 'Select2_from_array_multiple':
                    $arr = [];
                    $collection = collect(json_decode($json_values));
                    foreach ($collection as $key => $value) {
                        $arr[$value] = $value;
                    }
                    $class = "form-control select2_from_array";
                    $field['options'] = $arr;
                    if(isset($required) && $required) {
                        $field['allows_null'] = false;
                    } else {
                        $field['allows_null'] = true;
                    }
                    $type = "select2_from_array_multiple";

                break;
                case 'Select2_from_ajax_multiple':

                    $type = "select2_from_ajax_multiple";

                    break;
                case 'Browse':

                    $type = "browse";

                    break;
                case 'File':
                    
                    // $field['filename'] = Null;
                    // $field['aspect_ratio'] = 1; // set to 0 to allow any aspect ratio
                    // $field['crop'] = true; // set to true to allow cropping, false to disable
                    // $field['src'] = Null; 
                    if(isset($params['file_type'])) {
                        $field['file_type'] = $params['file_type']; 
                        unset($params['file_type']);
                    }
                    $type = "file";

                    break;
                case 'Files':
                    
                    if(isset($params['file_type'])) {
                        $field['file_type'] = $params['file_type']; 
                        unset($params['file_type']);
                    }
                    // $field['filename'] = Null;
                    // $field['aspect_ratio'] = 1; // set to 0 to allow any aspect ratio
                    // $field['crop'] = true; // set to true to allow cropping, false to disable
                    // $field['src'] = Null; 
                    $type = "files";

                    break;
                case 'Image':
                    
                    // $field['filename'] = Null;
                    // $field['aspect_ratio'] = 1; // set to 0 to allow any aspect ratio
                    // $field['crop'] = true; // set to true to allow cropping, false to disable
                    // $field['src'] = Null; 
                    $type = "image";

                    break;
                case 'Json':
                    
                    $arr = [];
                    $collection = collect(json_decode($json_values));
                    foreach ($collection as $key => $value) {
                        $arr[$value] = $value;
                    }
                    
                    $field['options'] = $arr;
                    $params['input_type'] = 'text';

                    $type = "json";

                    break;
                case 'Base64_image':
                    
                    $field['filename'] = Null;
                    $field['aspect_ratio'] = 1; // set to 0 to allow any aspect ratio
                    $field['crop'] = true; // set to true to allow cropping, false to disable
                    $field['src'] = Null; 
                    $type = "base64_image";

                    break;
            }
            
            if(isset($params) && count($params)) {
                $params = collect($params)->except(['attribute'])->all();
            }
            
            if(!isset($params['class'])) {
                $params['class'] = $class;
            }
            
            if(!isset($params['placeholder'])) {
                if(isset($field['attributes']['placeholder'])) {
                    $params['placeholder'] = $field['attributes']['placeholder'];
                }
            }

            if($unique && !isset($params['unique'])) {
                $params['data-rule-unique'] = "true";
                $params['field_id'] = $fields[$field_name]['id'];
                $params['adminRoute'] = config('aquaspade.base.route_prefix');
                if(!isset($params['isEdit']) && !isset($params['row_id'])) {
                    if(isset($row)) {
                        $params['isEdit'] = true;
                        $params['row_id'] = $row->id;
                    } else {
                        $params['isEdit'] = false;
                        $params['row_id'] = 0;
                    }
                }
                // $out .= '<input type="hidden" name="_token_' . $module->fields[$field_name]['id'] . '" value="' . csrf_token() . '">';
            }
            
            if($required && !isset($params['required'])) {
                $params['required'] = $required;
            }
            
            // $field = collect($field)->diffAssoc($params)->all();
            $result=array_diff_assoc($params,$field['attributes']);
            $result2=array_intersect($params,$field['attributes']);
            $field['attributes'] = array_merge($result, $result2);

            // echo "<pre>";
            // echo json_encode($field, JSON_PRETTY_PRINT);
            // echo "</pre>";
            if(isset($type) && $type != "") {
                $out .= view("crud.fields.".$type, array("field" => $field, "fields" => $fields, "crud" => $crud))->render();
            }
            
            return $out;
    }
    
    /**
     * Display field is CRUDs View show.blade.php with Label
     *
     * Uses blade syntax @display('name')
     *
     * @param $crud Module Object
     * @param $field_name Field Name for which display has be created
     * @param string $class Custom css class. Default would be bootstrap 'form-control' class
     * @return string This return html string with field display with Label
     */
    public static function display($crud, $field_name, $class = 'form-group', $labaleclass = "col-md-4 col-sm-6 col-xs-6", $valueclass = 'col-md-8 col-sm-6 col-xs-6')
    {
        // Check Field View Access
        // if(Module::hasFieldAccess($crud->id, $crud->fields[$field_name]['id'], $access_type = "view")) {
            $fields = $crud->fields;
            if(!isset($fields[$field_name]->name)) {
                return;
            }
            $label = $fields[$field_name]->label;

            $row = null;
            if(isset($crud->row)) {
                $row = $crud->row;
            }
            
            $out = '<div class="'.$class.'">';
            $out .= '<label for="' . $field_name . '" class="'.$labaleclass.'">' . $label . ' </label>';
            
            $value = $row->$field_name;

            $value = self::get_field_value($crud, $field_name, $value);

            $out .= '<div class="'.$valueclass.' fvalue">' . $value . '</div>';
            $out .= '</div>';
            return $out;
        // } else {
        //     return "";
        // }
    }
    
    public static function get_field_value($crud, $field_name, $value)
    {
        $fields = $crud->fields;
        $fieldObj = $fields[$field_name];
        if(isset($fields[$field_name]->field_type->name)) {
            $field_type = $fields[$field_name]->field_type->name;
        } else {
            $field_type = $fields[$field_name]->field_type;
        }
        switch($field_type) {
            case 'Address':

                break;
            case 'Checkbox':
                $data = "";
                if(isset($value) && is_array(json_decode($value))) {
                    foreach(json_decode($value) as $val) {
                        $data .= '<span class="label large bg-purple mr5">'.$val.'</span>';
                    }
                }
                $value = $data;

                break;
            case 'CKEditor':
                $value = html_entity_decode($value);
                
                break;
            case 'Currency':

                break;
            case 'Date':
                $value = \CustomHelper::date_format($value);

                break;
            case 'Date_picker':
                $value = \CustomHelper::date_format($value);

                break;
            case 'Date_range':
                if(isset($value) && is_array(json_decode($value))) {
                    $value = \CustomHelper::date_format(json_decode($value)->start).' - '.\CustomHelper::date_format(json_decode($value)->end);
                }
                break;
            case 'Datetime':
                $value = \CustomHelper::date_format($value, 'field_show_with_time');

                break;
            case 'Datetime_picker':
                $value = \CustomHelper::date_format($value, 'field_show_with_time');

                break;
            case 'Email':
        
                $value = '<a href="mailto:'.$value.'">'.$value.'</a>';

                break;
            case 'File':
                $img = "";
                if((isset($value) && $value)) {
                    $upload = Upload::find($value);
                    $img = "<div class='uploaded_files'>";
                    $url_file = url("files/" . $upload->hash . DIRECTORY_SEPARATOR . $upload->name);
                    $img .= "<a class='uploaded_file2 view' title='".$upload->name."' upload_id='".$upload->id."' target='_blank' href='".$url_file."'>";
    
                    $image = '';
                    if(in_array($upload->extension, ["jpg", "jpeg", "png", "gif", "bmp"])) {
                        $url_file .= "?s=30";
                        $image = '<img src="'.$url_file.'">';
                    } else {
                        switch ($upload->extension) {
                            case "pdf":
                            $image = '<i class="fa fa-file-pdf-o"></i>';
                            break;
                        case "xls":
                            $image = '<i class="fa fa-file-excel-o"></i>';
                            break;
                        case "docx":
                            $image = '<i class="fa fa-file-word-o"></i>';
                            break;
                        case "xlsx":
                            $image = '<i class="fa fa-file-excel-o"></i>';
                            break;
                        case "csv":
                            $image += '<span class="fa-stack" style="color: #31A867 !important;">';
                            $image += '<i class="fa fa-file-o fa-stack-2x"></i>';
                            $image += '<strong class="fa-stack-1x">CSV</strong>';
                            $image += '</span>';
                            break;
                        default:
                            $image = '<i class="fa fa-file-text-o"></i>';
                            break;
                        }
                    }
                    
                    $img .= "<span id='img_icon'>$image</span>";
                    $img .= "</a>";
                    $img .= "</div>";
                }
                
                $value = $img;

                break;
            case 'Files':
                
                $img = "";
                if((isset($value) && is_array(json_decode($value)) && count(json_decode($value)))) {
                    $uploads = Upload::whereIn('_id',json_decode($value))->get();
                    $img = "<div class='uploaded_files'>";
                    foreach ($uploads as $key => $upload) {
                        $url_file = url("files/" . $upload->hash . DIRECTORY_SEPARATOR . $upload->name);
                        $img .= "<a class='uploaded_file2 view' title='".$upload->name."' upload_id='".$upload->id."' target='_blank' href='".$url_file."'>";
        
                        $image = '';
                        if(in_array($upload->extension, ["jpg", "jpeg", "png", "gif", "bmp"])) {
                            $url_file .= "?s=30";
                            $image = '<img src="'.$url_file.'">';
                        } else {
                            switch ($upload->extension) {
                                case "pdf":
                                $image = '<i class="fa fa-file-pdf-o"></i>';
                                break;
                            case "xls":
                                $image = '<i class="fa fa-file-excel-o"></i>';
                                break;
                            case "docx":
                                $image = '<i class="fa fa-file-word-o"></i>';
                                break;
                            case "xlsx":
                                $image = '<i class="fa fa-file-excel-o"></i>';
                                break;
                            case "csv":
                                $image += '<span class="fa-stack" style="color: #31A867 !important;">';
                                $image += '<i class="fa fa-file-o fa-stack-2x"></i>';
                                $image += '<strong class="fa-stack-1x">CSV</strong>';
                                $image += '</span>';
                                break;
                            default:
                                $image = '<i class="fa fa-file-text-o"></i>';
                                break;
                            }
                        }
                        
                        $img .= "<span id='img_icon'>$image</span>";
                        $img .= "</a>";
                    }
                    $img .= "</div>";
                }
                $value = $img;

                break;
            case 'Hidden':
                if(starts_with($fields[$field_name]->json_values, "@")) {
                    $module = Module::where('name',substr($fields[$field_name]->json_values, 1))->first();
                    if(!isset($module->model)) {
                        $json_values_arr = explode('|',$fields[$field_name]->json_values);
                        $module = (object)[];
                        $module->name = $module->model = collect(str_replace("@", "", $json_values_arr))->first();
                        $module->table_name = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', str_replace('\\', '', Inflect::pluralize($module->model)))), '_');
                        $module->represent_attr = collect(str_replace("|", "", $json_values_arr))->last();
                    }
                    $represent_attr = $module->represent_attr;
                    if(isset($value) && !empty($value)) {
                        $value = DB::table($module->table_name)->where('_id', $value)->first()[$represent_attr];
                    }
                }

                break;
            case 'Image':
                if($value != 0 && $value != "0") {
                    $upload = Upload::find($value);
                    if(isset($upload->id)) {
                        $value = '<a class="preview" title="'.$upload->name.'" target="_blank" href="' . url("files/" . $upload->hash . DIRECTORY_SEPARATOR . $upload->name) . '"><img src="' . url("files/" . $upload->hash . DIRECTORY_SEPARATOR . $upload->name) . '?s=50"></a>';
                    } else {
                        $value = 'Uploaded image not found.';
                    }
                } else {
                    $value = 'No Image';
                }

                break;
            case 'Json':
            
                break;
            case 'Month':
                $value = \CustomHelper::date_format($value, 'month_save');

                break;
            case 'Multiselect':
                $data = "";
                if(isset($value) && is_array(json_decode($value))) {
                    foreach(json_decode($value) as $val) {
                        $data .= '<span class="label large bg-purple mr5">'.$val.'</span>';
                    }
                }
                $value = $data;
                break;
            case 'Number':
            
                break;
            case 'Password':
            
                break;
            case 'Phone':
            
                break;
            case 'Radio':
            
                break;
            case 'Select':
            
                break;
            case 'Select2':
            
                if(starts_with($fields[$field_name]->json_values, "@")) {
                    $module = Module::where('name',substr($fields[$field_name]->json_values, 1))->first();
                    
                    if(!isset($module->model)) {
                        $json_values_arr = explode('|',$fields[$field_name]->json_values);
                        $module = (object)[];
                        $module->name = $module->model = collect(str_replace("@", "", $json_values_arr))->first();
                        $module->table_name = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', str_replace('\\', '', Inflect::pluralize($module->model)))), '_');
                        $module->represent_attr = collect(str_replace("|", "", $json_values_arr))->last();
                    }
                    $represent_attr = $module->represent_attr;
                    if(isset($value) && !empty($value)) {
                        //$value = DB::table($module->table_name)->where('_id', $value)->first()->$represent_attr;
                        $value = DB::table($module->table_name)->where('_id', $value)->first()[$represent_attr];
                    }
                }

                break;
            case 'Select2_multiple':
                $data = "";
                
                if(starts_with($fields[$field_name]->json_values, "@")) {
                    if(isset($value) && is_array(json_decode($value))) {
                        foreach(json_decode($value) as $val) {
                            $module = Module::where('name',substr($fields[$field_name]->json_values, 1))->first();
                            if(!isset($module->model)) {
                                $json_values_arr = explode('|',$fields[$field_name]->json_values);
                                $module = (object)[];
                                $module->name = $module->model = collect(str_replace("@", "", $json_values_arr))->first();
                                $module->table_name = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', str_replace('\\', '', Inflect::pluralize($module->model)))), '_');
                                $module->represent_attr = collect(str_replace("|", "", $json_values_arr))->last();
                            }
                            $represent_attr = $module->represent_attr;
                            $row_val = DB::table($module->table_name)->where('_id', $val)->first();
                            
                            if(isset($row_val[$represent_attr])) {
                                $data .= '<span class="label large bg-purple mr5">'.$row_val[$represent_attr].'</span>';
                            }
                        }
                    }
                } else {
                    if(isset($value) && is_array(json_decode($value))) {
                        foreach(json_decode($value) as $val) {
                            $data .= '<span class="label large bg-purple mr5">'.$val.'</span>';
                        }
                    }
                }
                    
                $value = $data;
                break;
            case 'Text':
            
                break;
            case 'Textarea':
            
                break;
        }
        return $value;
    }

    /**
     * Print complete add/edit form for Module
     *
     * Uses blade syntax @la_form($employee_crud_object)
     *
     * @param $crud Module for which add/edit form has to be created.
     * @param array $fields List of Module Field Names to customize Selective Fields for Form
     * @return string returns HTML for complete Module Add/Edit Form
     */
    public static function displayAll($crud, $field_names = [], $input_attr = [], $only_required_field = false)
    {
        return self::form($crud, $field_names, $input_attr, $only_required_field, 'display');
    }

    /**
     * Print complete add/edit form for Module
     *
     * Uses blade syntax @la_form($employee_crud_object)
     *
     * @param $crud Module for which add/edit form has to be created.
     * @param array $fields List of Module Field Names to customize Selective Fields for Form
     * @return string returns HTML for complete Module Add/Edit Form
     */
    public static function form($crud, $field_names = [], $input_attr = [], $fuction = 'input', $only_required_field = false)
    {
        if(isset($crud->view) && $crud->view == "Edit") {
            $fields = $crud->fields;
        } else {
            $fields = $crud->fields;
        }
        if(count($field_names) == 0 && !$only_required_field) {
            $field_names = array_keys($fields);
        } else if($only_required_field) {
            foreach($fields as $field) {
                if($field->required) {
                    $field_names[] = $field->name;
                }
            }
        }
        // return $field_names;
        $out = '';
        if(count($input_attr) == 0) {
            foreach($field_names as $field) {
                
                $out .= "<div class='row'><div class='col-lg-12 col-md-12 col-sm-12 col-xs-12'>";
                if($fuction == "input") {
                    $out .= self::input($crud, $field);
                } else {
                    $out .= self::display($crud, $field);
                }
                $out .= "</div></div>";
            }
        } else {
            $hiden_count = 0;
            foreach($field_names as $key => $field) {
                if(isset($crud->fields[$field]->id)) {
                    $field_type_name = Field::find($crud->fields[$field]->id)->field_type->name;
                } else {
                    $field_type_name = $crud->fields[$field]->field_type;
                }
                if(isset($field_type_name) && $field_type_name == 'Hidden' && $fuction == "display") {
                    if($fuction == "input") {
                        $out .= self::input($crud, $field);
                    } else {
                        $out .= self::display($crud, $field);
                    }
                    // $hiden_count++;
                } else {
                    // $key = $key + $hiden_count;
                    if($hiden_count % 2 == 0){
                        $out .= "<div class='row'>";
                    }
                    $attrs = array_keys($input_attr);
                    $out .= '<div ';
                    foreach ($attrs as $value) {
                        $out .= $value.'="'.$input_attr[$value].'"';
                    }
                    $out .= '>';
                    if($fuction == "input") {
                        $out .= self::input($crud, $field);
                    } else {
                        $out .= self::display($crud, $field);
                    }
                    $out .= '</div>';
                    
                    if(($hiden_count % 2 != 0) || ($key == count($field_names)-1)){
                        $out .= "</div>";
                    }
                    $hiden_count++;
                }
            }
        }
        return $out;
    }
    
    /**
     * Check Whether User has Module Access
     * Work like @if blade directive of Laravel
     *
     * @param $crud_id Module Id for which Access will be checked
     * @param string $access_type Access type like - view / create / edit / delete
     * @param int $user_id User id for which access is checked. By default it takes logged-in user
     * @return bool return whether access for this Module is true / false
     */
    public static function access($crud_id, $access_type = "list", $user_id = 0)
    {
        // Check Module access by hasAccess method
        return Module::hasAccess($crud_id, $access_type, $user_id);
    }
    
    /**
     * Check Whether User has Module Field Access
     *
     * Work like @if blade directive of Laravel
     *
     * @param $crud_id Module Id for which Access will be checked
     * @param $field_id Field Id / Name for which Access will be checked
     * @param string $access_type Field Access type like - view / write
     * @param int $user_id User id for which access is checked. By default it takes logged-in user
     * @return bool return whether access for this Module Field is true / false
     */
    public static function la_field_access($crud_id, $field_id, $access_type = "view", $user_id = 0)
    {
        // Check Module Field access by hasFieldAccess method
        return Module::hasFieldAccess($crud_id, $field_id, $access_type, $user_id);
    }
}