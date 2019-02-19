<?php

namespace Shagyt\lvcrud\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Shagyt\lvcrud\Helpers\Inflect;
use Shagyt\lvcrud\Models\Module;
use Shagyt\lvcrud\Models\FieldType;

class CrudMigrateCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'st:migrate';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'st:migrate {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a CRUD migration';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Migration';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../stubs/crud-migration.stub';
    }

    /**
     * Replace the table name for the given stub.
     *
     * @param string $stub
     * @param string $name
     *
     * @return string
     */
    protected function replaceTable(&$stub, $name)
    {
        $table = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', str_replace($this->getNamespace($name).'\\', '', Inflect::pluralize($name)))), '_');

        $stub = str_replace('__Class__', ucfirst(str_replace($this->getNamespace($name).'\\', '', Inflect::pluralize($name))), $stub);
        $stub = str_replace('__Table__', $table, $stub);
        $stub = str_replace('__Class_Singular__', ucfirst(str_replace($this->getNamespace($name).'\\', '', Inflect::singularize($name))), $stub);

        return $this;
    }

    /**
     * Replace the table name for the given stub.
     *
     * @param string $stub
     * @param string $name
     *
     * @return string
     */
    protected function replaceNameStrings(&$stub)
    {
        $name = $this->getNameInput();
        $modul = Module::where('name', $name)->first();
        $out = "";
        if(isset($modul) && $modul->id) {
            foreach ($modul->fields as $key => $field) {
                
                $out .= "[\n";
                $out .= "\t\t\t\t'name' => '".$field['name']."',\n";
                $out .= "\t\t\t\t'label' => '".$field['label']."',\n";
                $out .= "\t\t\t\t'field_type' => '".$field['field_type']->name."',\n";
                if(isset($field['unique']) && $field['unique']) {
                    $out .= "\t\t\t\t'unique' => true,\n";
                } else {
                    $out .= "\t\t\t\t'unique' => false,\n";
                }
                if(isset($field['defaultvalue']) && $field['defaultvalue'] && $field['defaultvalue'] != '[""]') {
                    $out .= "\t\t\t\t'defaultvalue' => '".$field['defaultvalue']."',\n";
                } else {
                    $out .= "\t\t\t\t'defaultvalue' => Null,\n";
                }

                $out .= "\t\t\t\t'minlength' => '".$field['minlength']."',\n";
                $out .= "\t\t\t\t'maxlength' => '".$field['maxlength']."',\n";
                
                if(isset($field['required']) && $field['required']) {
                    $out .= "\t\t\t\t'required' => true,\n";
                } else {
                    $out .= "\t\t\t\t'required' => false,\n";
                }
                
                if(isset($field['nullable_required']) && $field['nullable_required'] == "0") {
                    $out .= "\t\t\t\t'nullable_required' => false,\n";
                }
                if(isset($field['show_index']) && $field['show_index']) {
                    if(isset($field['json_values']) && $field['json_values'] !== "") {
                        $out .= "\t\t\t\t'show_index' => true,\n";
                    } else {
                        $out .= "\t\t\t\t'show_index' => true\n";
                    }
                    
                } else {
                    if(isset($field['json_values']) && $field['json_values'] !== "") {
                        $out .= "\t\t\t\t'show_index' => false,\n";
                    } else {
                        $out .= "\t\t\t\t'show_index' => false\n";
                    }
                }
                
                if(isset($field['json_values']) && (starts_with($field['json_values'], "@") || starts_with($field['json_values'],"["))) {
                    if(starts_with($field['json_values'], "@")) {
                        $out .= "\t\t\t\t'json_values' => '".$field['json_values']."'\n";
                    } else {
                        $out .= "\t\t\t\t'json_values' => ".$field['json_values']."\n";
                    }
                }
                
                $out .= "\t\t\t]";
                
                if(!($key == count($modul->fields)-1)){
                    $out .= ",";
                }
            }

            // update represent_attr and icon from module
            $stub = str_replace('__represent_attr__', $modul->represent_attr, $stub);
            $stub = str_replace('__icon__', $modul->icon, $stub);
        } else {
            $out .= "[
                'name' => 'name',
                'label' => 'Name',
                'field_type' => 'Text',
                'unique' => false,
                'defaultvalue' => Null,
                'minlength' => '0',
                'maxlength' => '0',
                'required' => true,
                'show_index' => true
            ],[
                'name' => 'description',
                'label' => 'Description',
                'field_type' => 'Textarea',
                'unique' => false,
                'defaultvalue' => Null,
                'minlength' => '0',
                'maxlength' => '0',
                'required' => false,
                'show_index' => true
            ]";

            // default set represent_attr and icon.
            $stub = str_replace('__represent_attr__', 'name', $stub);
            $stub = str_replace('__icon__', 'fa-smile-o', $stub);
        }
        
        $out = trim($out);
        // $this->info($out);
        $stub = str_replace('__single_field__', $out, $stub);
        
        $field_types = "";
        $tabspace = "";
        foreach (FieldType::all() as $key => $field_type) {
            $field_types .= $tabspace.$field_type->name.','."\n";
            $tabspace = "\t\t\t";
        }

        $stub = str_replace('__Field_type__', $field_types, $stub);

        return $stub;
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName) {
        return false;
    }

    /**
     * Get the destination class path.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getPath($name)
    {
        $table = ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', str_replace($this->getNamespace($name).'\\', '', Inflect::pluralize($name)))), '_');

        $mfiles = scandir(base_path('database/migrations/'));
        // print_r($mfiles);

        $migrationName = 'create_' . $table . '_table';
        $migrationFileName = date("Y_m_d_His_") . $migrationName . ".php";

        $fileExists = false;
        $fileExistName = "";
        foreach($mfiles as $mfile) {
            // print_r($mfile. "  " .$migrationName ."\n");
            if(str_contains($mfile, $migrationName)) {
                $fileExists = true;
                $fileExistName = $mfile;
            }
        }
        if($fileExists) {
            $migrationFileName = $fileExistName;
        }
        return base_path() . '/database/migrations/' . $migrationFileName;
    }

    /**
     * Build the class with the given name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceTable($stub, $name)->replaceNameStrings($stub);
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [

        ];
    }
}
