<?php

namespace Shagyt\lvcrud\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Shagyt\lvcrud\Helpers\Inflect;
use Shagyt\lvcrud\Models\Module;

class CrudViewShowCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'st:viewShow';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'st:viewShow {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a Show templated view';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'ViewShow';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        // if ($this->option('plain')) {
        //     return __DIR__.'/../stubs/view-plain.stub';
        // }

        return __DIR__.'/../stubs/viewShow.stub';
    }

    /**
     * Replace the table name for the given stub.
     *
     * @param string $stub
     * @param string $name
     *
     * @return string
     */
    protected function replaceNameStrings(&$stub, $name)
    {
        $table = str_plural(ltrim(strtolower(preg_replace('/[A-Z]/', '_$0', str_replace($this->getNamespace($name).'\\', '', Inflect::pluralize($name)))), '_'));

        $name = $this->getNameInput();
        $modul = Module::where('name', $name)->first();
        $out = "";
        if(isset($modul) && $modul->id) {
            foreach ($modul->fields as $key => $field) {
                if($key % 2 == 0){
                    $out .= "\t\t\t\t\t\t\t\t\t<div class='row'>\n";
                }

                $out .= "\t\t\t\t\t\t\t\t\t\t\t<div class='col-md-6'>@display($" . "crud, '" . $field['name'] . "')</div>\n";
                
                if(($key % 2 != 0) || ($key == count($modul->fields)-1)){
                    $out .= "\t\t\t\t\t\t\t\t\t\t</div>\n";
                }
            }
        }
        
        $out = trim($out);
        // $this->info($out);
        $stub = str_replace("__single_display__", $out, $stub);
        
        $stub = str_replace('__tablename__', $table, $stub);
        $stub = str_replace('__smallPlural__', strtolower(str_replace($this->getNamespace($table).'\\', '', Inflect::pluralize($table))), $stub);
        $stub = str_replace('__smallSingular__', strtolower(str_replace($this->getNamespace($table).'\\', '', Inflect::singularize($table))), $stub);

        return $stub;
    }

    /**
     * Determine if the class already exists.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function alreadyExists($name)
    {
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
        $name = str_replace($this->laravel->getNamespace(), '', $name);
        return $this->laravel['path'].'/../resources/views/admin/'.$name.'/'.str_replace('\\', '/', 'show').'.blade.php';
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
        // return $stub;
        return $this->replaceNameStrings($stub, $name);
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
