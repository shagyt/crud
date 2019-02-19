<?php

namespace Shagyt\lvcrud;

use Illuminate\Support\ServiceProvider;

class ShagytServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
            __DIR__.'/generators/0000_00_00_000001_create_modules_table.php' => app()->basePath() . '/database/migrations/0000_00_00_000001_create_modules_table.php.php',
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands($this->commands);
    }

     /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // CRUD files:
        'Shagyt\lvcrud\Console\Commands\CrudModelCommand',
        'Shagyt\lvcrud\Console\Commands\CrudControllerCommand',
        'Shagyt\lvcrud\Console\Commands\CrudCommand',
        'Shagyt\lvcrud\Console\Commands\CrudViewIndexCommand',
        'Shagyt\lvcrud\Console\Commands\CrudViewCreateCommand',
        'Shagyt\lvcrud\Console\Commands\CrudViewEditCommand',
        'Shagyt\lvcrud\Console\Commands\CrudViewShowCommand',
        'Shagyt\lvcrud\Console\Commands\CrudMigrateCommand',
    ];
}
