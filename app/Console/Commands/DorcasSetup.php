<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Illuminate\Support\Facades\DB;

class DorcasSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dorcas:setup {--database=}';

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

        // $value = $this->argument('name');
        // // and
        // $value = $this->option('name');
        // // or get all as array
        // $arguments = $this->argument();
        // $options = $this->option();


        // default setup
        $database = $this->option('database');
        $database = getenv('DB_DATABASE');

        if (!$database) {
            $this->info('Skipping creation of database as env(DB_DATABASE) is empty');
            return;
        }

        try {
            //putenv ("CUSTOM_VARIABLE=hero");

            $conn = mysqli_connect(getenv('DB_HOST'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));

            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }
            
            // Create database
            $sql = "CREATE DATABASE IF NOT EXISTS `" . $database . "`";
            if (mysqli_query($conn, $sql)) {
                $this->info(sprintf('Successfully created %s database', $database));
            } else {
                $this->error(sprintf('Error creating %s database, %s', $database, mysqli_error($conn)));
            }
            
            mysqli_close($conn);

        } catch (Exception $exception) {
            $this->error(sprintf('Failed to create %s database, %s', $database, $exception->getMessage()));
            }

    }

}
