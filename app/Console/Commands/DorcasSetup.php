<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Illuminate\Support\Facades\DB;

class DorcasSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dorcas:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup Dorcas Installation';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($options = "")
    {
        if (empty($options)) {
            // default setup
            $database = env('DB_DATABASE', "");

            if (!$database) {
                $this->info('Skipping creation of database as env(DB_DATABASE) is empty');
                return;
            }
    
            try {
                //putenv ("CUSTOM_VARIABLE=hero");

                //Connecting to MySQL
                $pdo = DB::connection()->getPdo();
                
                //Creating the Database
                $pdo->exec(sprintf(
                    'CREATE DATABASE IF NOT EXISTS %s ;', $database
                ));
    
                $this->info(sprintf('Successfully created %s database', $database));
    
            } catch (PDOException $exception) {
                $this->error(sprintf('Failed to create %s database, %s', $database, $exception->getMessage()));
            }
        }
    }
}
