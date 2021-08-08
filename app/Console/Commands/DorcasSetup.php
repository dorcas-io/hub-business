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


        // DATABASE CREATION AND IMPORT MOVED TO CORE DORCAS SETUP COMMAND

        $this->info('Writing OAuth Client Details to .env');
        try {
            $client = DB::connection('core_mysql')->table("oauth_clients")->where('password_client', 1)->first();
            $client_id = $client->id;
            $client_secret = $client->secret;

            $this->info(' ID: ' . $client_id . " / Secret: " . $client_secret);

            $path = base_path('.env');
            if (file_exists($path)) {
                file_put_contents($path, str_replace(
                    'DORCAS_CLIENT_ID=', 'DORCAS_CLIENT_ID='.$client_id, file_get_contents($path)
                ));
                file_put_contents($path, str_replace(
                    'DORCAS_CLIENT_SECRET=', 'DORCAS_CLIENT_SECRET='.$client_secret, file_get_contents($path)
                ));
                file_put_contents($path, str_replace(
                    'DORCAS_PERSONAL_CLIENT_ID=', 'DORCAS_PERSONAL_CLIENT_ID='.$client_id, file_get_contents($path)
                ));
                file_put_contents($path, str_replace(
                    'DORCAS_PERSONAL_CLIENT_SECRET=', 'DORCAS_PERSONAL_CLIENT_SECRET='.$client_secret, file_get_contents($path)
                ));
                $this->info('Successfully written Client ID & Secret to .env');
            }


        } catch (Exception $exception) {
            $this->error(sprintf('Failed setting up OAuth: %s', $exception->getMessage()));
        }


    }

}
