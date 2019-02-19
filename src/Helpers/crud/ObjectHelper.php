<?php

namespace Shagyt\lvcrud\Helpers\crud;

use Shagyt\lvcrud\Helpers\crud\Traits\Create;
use Shagyt\lvcrud\Helpers\crud\Traits\Update;
use Shagyt\lvcrud\Helpers\crud\Traits\Delete;
use Shagyt\lvcrud\Helpers\crud\Traits\Fields;
use Shagyt\lvcrud\Helpers\crud\Traits\Access;

class ObjectHelper
{
    use Create;
    use Update;
    use Delete;
    use Fields;
    use Access;
    // --------------
    // CRUD Object
    // --------------
    
    public $module = [];
    public $model;
    public $table_name;
    public $controller;
    public $represent_attr;
    public $icon;
    public $route;
    
    public $access = [];

    public $columns = [];
    public $column_names = [];
    public $fields = [];

    public $entry;
    public $buttons;

    public $label;
    public $name;
    public $labelPlural;
    // push css and js of filed type array.
    public $CssJsApply = [];

    /**
     * This function binds the CRUD to its corresponding Model (which extends Eloquent).
     * All Create-Read-Update-Delete operations are done using that Eloquent Collection.
     *
     * @param [string] Full model namespace. Ex: App\Models\Article
     */
    public function setModel($model_namespace)
    {
        if (! class_exists($model_namespace)) {
            throw new \Exception('This model does not exist.', 404);
        }

        $this->model = new $model_namespace();
        $this->initButtons();
        if(true || isset(\Auth::user()->id) && \Auth::user()->hasRole('super_admin')) {
            $this->access = ['list', 'create', 'update', 'delete', 'show'/* 'revisions', reorder', 'details_row' */];
        }
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
     * set module info; 
     *
     * @return [Eloquent Collection]
     */
    public function setModule($module)
    {
        $this->module = clone $module;

        $this->table_name = $module->table_name;
        $this->name = $module->name;
        $this->controller = $module->controller;
        $this->represent_attr = $module->represent_attr;
        $this->icon = $module->icon;
        if(isset($module->fields)) {
            $this->setFields($module->fields);
        }

        if($module->name == "Users") {
            $this->setModel("App\\".$module->model);
            $this->setColumnNames('users', ['name','email']);
        } else {
            $this->setModel("App\\Models\\".$module->model);
            $this->setColumnNames($module->table_name);
        }

        $this->setRoute(config('aquaspade.base.route_prefix') . '/'.$module->table_name);
        if(isset($module->fields)) {
            $this->setColumns($module->fields);
        }

        $this->setEntityNameStrings(\Inflect::singularize($module->label),$module->label);
    }
    
    /**
     * Get the number of rows that should be show on the table page (list view).
     */
    public function getDefaultPageLength()
    {
        // return the custom value for this crud panel, if set using setPageLength()
        // if ($this->default_page_length) {
        //     return $this->default_page_length;
        // }

        // otherwise return the default value in the config file
        if (config('aquaspade.crud.default_page_length')) {
            return config('aquaspade.crud.default_page_length');
        }

        return 25;
    }

    /**
     * set fields; 
     *
     * @return [Eloquent Collection]
     */
    public function setFields($fields)
    {
        foreach ($fields as $key => $value) {
            if(!is_object($value)) {
                $value = (object) $value;
            }
            $this->fields[$value->name] = $value;
        }
    }
    
    /**
     * set columns; 
     *
     * @return [Eloquent Collection]
     */
    public function setColumns($fields)
    {
        foreach ($fields as $key => $value) {
            if(!is_object($value)) {
                $type = $value['field_type'];
                $value = (object) $value;
            } else {
                $type = strtolower($value->field_type->name);
            }
            if($value->show_index) {
                $this->columns[$value->name] = [
                    'name'  => $value->name,
                    'label' => $value->label,
                    'type'  => $type,
                ];
            }
        }
    }

    /**
     * Add a button to the CRUD table view auto.
     */    
    public function initButtons()
    {
        $this->buttons = collect();

        // line stack
        // $this->addButton('line', 'preview', 'view', 'crud.buttons.preview', 'end');
        $this->addButton('line', 'update', 'view', 'crud.buttons.update', 'end');
        $this->addButton('line', 'delete', 'view', 'crud.buttons.delete', 'end');
        $this->addButton('line', 'restore', 'view', 'crud.buttons.restore', 'end');

        // top stack
        $this->addButton('top', 'create', 'view', 'crud.buttons.create');
        $this->addButton('top', 'deleted_data', 'view', 'crud.buttons.deleted_data');
    }

    /**
     * Add a button to the CRUD table view.
     */
    public function addButton($stack, $name, $type, $content, $position = false)
    {
        if ($position == false) {
            switch ($stack) {
                case 'line':
                    $position = 'beginning';
                    break;

                default:
                    $position = 'end';
                    break;
            }
        }

        switch ($position) {
            case 'beginning':
                $this->buttons->prepend((object)['stack' => $stack, "name" => $name, "type" => $type, "content" => $content]);
                break;

            default:
                $this->buttons->push((object)['stack' => $stack, "name" => $name, "type" => $type, "content" => $content]);
                break;
        }
    }

    public function removeButton($name)
    {
        $this->buttons = $this->buttons->reject(function ($button) use ($name) {
            return $button->name == $name;
        });
    }

    public function onlyButton($name)
    {
        if(!is_array($name)) {
            $name = [$name];
        }
        // echo json_encode($this->buttons);
        $this->buttons = $this->buttons->whereIn('name', $name)->values();
    }

    public function removeAllButtons()
    {
        $this->buttons = collect([]);
    }

    
    /**
     * Check if field is the first of its type in the given fields array.
     * It's used in each field_type.blade.php to determine wether to push the css and js content or not (we only need to push the js and css for a field the first time it's loaded in the form, not any subsequent times).
     *
     * @param array $field        The current field being tested if it's the first of its type.
     * @param array $fields_array All the fields in that particular form.
     *
     * @return bool true/false
     */
    public function checkIfOnce($field)
    {
        if(!in_array($field['type'], $this->CssJsApply)) {
            $this->CssJsApply[] = $field['type'];
            return true;
        }

        return false;
    }

    public function isColumnNullable($column_name)
    {
        // create an instance of the model to be able to get the table name
        $instance = $this->model;

        $conn = \DB::connection($instance->getConnectionName());
        $table = \Config::get('database.connections.'.env('DB_CONNECTION').'.prefix').$instance->getTable();

        // register the enum column type, because Doctrine doesn't support it
        $conn->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        return ! $conn->getDoctrineColumn($table, $column_name)->getNotnull();
    }

    /**
     * get module info;
     *
     * @return [Eloquent Collection]
     */
    public function getModule()
    {
        return $this->module;
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
    
    /**
     * Get the current CrudController route.
     *
     * Can be defined in the CrudController with:
     * - $this->crud->setRoute(config('aquaspade.base.route_prefix').'/article')
     * - $this->crud->setRouteName(config('aquaspade.base.route_prefix').'.article')
     * - $this->crud->route = config('aquaspade.base.route_prefix')."/article"
     *
     * @return [string]
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set the entity name in singular and plural.
     * Used all over the CRUD interface (header, add button, reorder button, breadcrumbs).
     *
     * @param [string] Entity name, in singular. Ex: article
     * @param [string] Entity name, in plural. Ex: articles
     */
    public function setEntityNameStrings($singular, $plural)
    {
        $this->label = $singular;
        $this->labelPlural = $plural;
    }

    // ----------------------------------
    // Miscellaneous functions or methods
    // ----------------------------------

    /**
     * Return the first element in an array that has the given 'type' attribute.
     *
     * @param string $type
     * @param array  $array
     *
     * @return array
     */
    public function getFirstOfItsTypeInArray($type, $array)
    {
        return array_first($array, function ($item) use ($type) {
            if(isset($item['field_type_id']) && isset($item['field_type_id'])) {
                return strtolower($item['field_type']->name) == $type;
            } else {
                return $item['type'] == $type;
            }
        });
    }

    // ------------
    // TONE FUNCTIONS - UNDOCUMENTED, UNTESTED, SOME MAY BE USED IN THIS FILE
    // ------------
    //
    // TODO:
    // - figure out if they are really needed
    // - comments inside the function to explain how they work
    // - write docblock for them
    // - place in the correct section above (CREATE, READ, UPDATE, DELETE, ACCESS, MANIPULATION)

    public function sync($type, $fields, $attributes)
    {
        if (! empty($this->{$type})) {
            $this->{$type} = array_map(function ($field) use ($fields, $attributes) {
                if (in_array($field['name'], (array) $fields)) {
                    $field = array_merge($field, $attributes);
                }

                return $field;
            }, $this->{$type});
        }
    }

    public function setColumnNames($table, $option="")
    {
        $arr = collect($this->fields)->keys();
        if(isset($option) && $option == "All") {
            $this->column_names = $arr;
        } else if(is_array($option) && count($option)) {
            $this->column_names = collect($arr)->intersect($option)->all();
        } else {
            $this->column_names = collect($arr)->diff(['id', 'created_at', 'updated_at', 'deleted_at'])->all();
        }
    }
}
