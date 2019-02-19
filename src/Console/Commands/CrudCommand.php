<?php

namespace Shagyt\lvcrud\Console\Commands;

use Artisan;
use Illuminate\Console\Command;
use Shagyt\lvcrud\Models\Module;

class CrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'st:crud {name?} {--All=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'st:crud {name?} {--All=["views","migrate","model","controller","request","viewIndex","viewCreate","viewEdit","viewShow"]} ';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = ucfirst(str_plural($this->argument('name')));
        if ($this->option('All')) {
            $this->info($this->option('All'));
            foreach (Module::all() as $key => $value) {
                if(!in_array($value->name, ['Users','Uploads','Permissions','Roles'])) {
                    $this->info($value->name);
                    if(($this->option('All') == "migrate")) {
                        Artisan::call('st:migrate', ['name' => $value->name]);
                        echo Artisan::output();
                    } else if(($this->option('All') == "model")) {
                        // Create the CRUD Model and show output
                        Artisan::call('st:crud-model', ['name' => $value->name]);
                        echo Artisan::output();
                    } else if(($this->option('All') == "controller")) {
                        // Create the CRUD Controller and show output
                        Artisan::call('st:crud-controller', ['name' => $value->name]);
                        echo Artisan::output();
                    } else if(($this->option('All') == "request")) {
                        // Create the CRUD Request and show output
                        // Artisan::call('st:crud-request', ['name' => $value->name]);
                        // echo Artisan::output();
                    } else if(($this->option('All') == "viewIndex")) {
                        // Create the CRUD Request and show output
                        Artisan::call('st:viewIndex', ['name' => $value->name]);
                        echo Artisan::output();
                    } else if(($this->option('All') == "viewCreate")) {
                        // Create the CRUD Request and show output
                        Artisan::call('st:viewCreate', ['name' => $value->name]);
                        echo Artisan::output();
                    } else if(($this->option('All') == "viewEdit")) {
                        // Create the CRUD Request and show output
                        Artisan::call('st:viewEdit', ['name' => $value->name]);
                        echo Artisan::output();
                    } else if(($this->option('All') == "viewShow")) {
                        // Create the CRUD Request and show output
                        Artisan::call('st:viewShow', ['name' => $value->name]);
                        echo Artisan::output();
                    } else if(($this->option('All') == "views")) {
                        // Create the CRUD Request and show output
                        Artisan::call('st:viewIndex', ['name' => $value->name]);
                        // Create the CRUD Request and show output
                        echo Artisan::output();
                        Artisan::call('st:viewCreate', ['name' => $value->name]);
                        echo Artisan::output();
                        // Create the CRUD Request and show output
                        Artisan::call('st:viewEdit', ['name' => $value->name]);
                        echo Artisan::output();
                        // Create the CRUD Request and show output
                        Artisan::call('st:viewShow', ['name' => $value->name]);
                        echo Artisan::output();
                    } else if(($this->option('All') == "all")) {
                        // Create the CRUD Request and show output
                        Artisan::call('st:migrate', ['name' => $value->name]);
                        echo Artisan::output();

                        // // Create the CRUD Model and show output
                        Artisan::call('st:crud-model', ['name' => $value->name]);
                        echo Artisan::output();

                        // Create the CRUD Controller and show output
                        Artisan::call('st:crud-controller', ['name' => $value->name]);
                        echo Artisan::output();

                        // Create the CRUD Request and show output
                        Artisan::call('st:viewIndex', ['name' => $value->name]);
                        echo Artisan::output();

                        // // Create the CRUD Request and show output
                        Artisan::call('st:viewCreate', ['name' => $value->name]);
                        echo Artisan::output();

                        // // Create the CRUD Request and show output
                        Artisan::call('st:viewEdit', ['name' => $value->name]);
                        echo Artisan::output();

                        // // Create the CRUD Request and show output
                        Artisan::call('st:viewShow', ['name' => $value->name]);
                        echo Artisan::output();
                    }
                }
            }
        } else if($this->argument('name')) {
            // Create the CRUD Request and show output
            Artisan::call('st:migrate', ['name' => $name]);
            echo Artisan::output();
            
            // Create the CRUD Request and show output
            Artisan::call('migrate');
            echo Artisan::output();

            // // Create the CRUD Model and show output
            Artisan::call('st:crud-model', ['name' => $name]);
            echo Artisan::output();

            // Create the CRUD Controller and show output
            Artisan::call('st:crud-controller', ['name' => $name]);
            echo Artisan::output();

            // Create the CRUD Request and show output
            Artisan::call('st:viewIndex', ['name' => $name]);
            echo Artisan::output();

            // // Create the CRUD Request and show output
            Artisan::call('st:viewCreate', ['name' => $name]);
            echo Artisan::output();

            // // Create the CRUD Request and show output
            Artisan::call('st:viewEdit', ['name' => $name]);
            echo Artisan::output();

            // // Create the CRUD Request and show output
            Artisan::call('st:viewShow', ['name' => $name]);
            echo Artisan::output();

        }
        // Create the CRUD log_config
        // Artisan::call('st:log_config');
        // echo Artisan::output();
    }
}
