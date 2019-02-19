<?php

namespace Shagyt\lvcrud\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Shagyt\lvcrud\Helpers\Inflect;

class CrudModelCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'st:crud-model';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'st:crud-model {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a CRUD model';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/../stubs/crud-model.stub';
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
        return $rootNamespace.'\Models';
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

        $stub = str_replace('DummyTable', $table, $stub);
        $stub = str_replace('DummyClassSingular', ucfirst(str_replace($this->getNamespace($name).'\\', '', Inflect::singularize($name))), $stub);

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
     * Get the destination class path.
     *
     * @param string $name
     *
     * @return string
     */
    protected function getPath($name)
    {
        $name = Inflect::singularize(str_replace($this->laravel->getNamespace(), '', $name));
        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
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

        return $this->replaceNamespace($stub, $name)->replaceTable($stub, $name)->replaceClass($stub, $name);
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
