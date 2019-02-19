<?php

namespace Shagyt\lvcrud\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Shagyt\lvcrud\Helpers\Inflect;
use Shagyt\lvcrud\Models\Module;

class CrudViewIndexCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'st:viewIndex';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'st:viewIndex {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a index list templated view';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'ViewIndex';

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

        return __DIR__.'/../stubs/viewIndex.stub';
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
            $out .= "\t\t\t\t\t\t\t<div class='row'>\n";
            foreach ($modul->fields as $key => $field) {
                if($field->required) {
                    $out .= "\t\t\t\t\t\t\t\t<div class='col-md-6'>@input($" . "crud, '" . $field['name'] . "')</div>\n";
                }
            }
            $out .= "\t\t\t\t\t\t\t</div>\n";
        } else {
            $out .= '__single_input__';
        }

        $out = trim($out);
        // $this->info($out);
        $stub = str_replace('__single_input__', $out, $stub);
        
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
        return $this->laravel['path'].'/../resources/views/admin/'.$name.'/'.str_replace('\\', '/', 'index').'.blade.php';
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
        return $this->replaceNameStrings($stub);
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
