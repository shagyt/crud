<?php

namespace Shagyt\lvcrud\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Shagyt\lvcrud\Helpers\Inflect;

class CrudControllerCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'st:crud-controller';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'st:crud-controller {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a CRUD controller';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Controller';

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

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'Controller.php';
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../stubs/crud-controller.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers\Admin';
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
        
        $stub = str_replace('__Class__', ucfirst(str_replace($this->getNamespace($name).'\\', '', Inflect::pluralize($name))), $stub);
        $stub = str_replace('__ViewFolder__', ucfirst(str_replace($this->getNamespace($name).'\\', '', $name)), $stub);
        $stub = str_replace('__ModelName__', ucfirst(str_replace($this->getNamespace($name).'\\', '', Inflect::singularize($name))), $stub);
        $stub = str_replace('__smallPlural__', strtolower(str_replace($this->getNamespace($table).'\\', '', Inflect::pluralize($table))), $stub);
        $stub = str_replace('__smallSingular__', strtolower(str_replace($this->getNamespace($table).'\\', '', Inflect::singularize($table))), $stub);
        
        return $this;
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
     * Build the class with the given name.
     *
     * @param string $name
     *
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $name)->replaceNameStrings($stub, $name)->replaceClass($stub, $name);
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
