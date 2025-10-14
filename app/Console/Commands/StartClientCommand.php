<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class StartClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     * 
     * Example: php artisan slime-chat:start-client "My App" "myapp.com"
     *
     * @var string
     */
    protected $signature = 'slime-chat:start-client 
                            {name : The client name}
                            {domain : The full domain where requests will be received (e.g., example.com)}
                            {--public-key= : Custom public key (optional, will generate if not provided)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a new client for Slime Chat with provided parameters';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Slime Chat Client Setup...');
        $this->newLine();

        // Get parameters from command arguments
        $clientName = $this->argument('name');
        $domain = $this->argument('domain');
        $customPublicKey = $this->option('public-key');

        // Validate domain format
        if (!filter_var('http://' . $domain, FILTER_VALIDATE_URL)) {
            $this->error('Please provide a valid domain format!');
            return 1;
        }

        // Use custom public key if provided, otherwise generate one
        $publicKey = $customPublicKey ?: 'pk_' . Str::random(32);
        
        // Generate API token
        $client = Client::create([
            'name' => $clientName,
            'domain' => $domain,
            'public_key' => $publicKey,
            'allowed_ips' => null,
            'allowed_subdomains' => null,
        ]);

        $token = $client->createToken('client-api-token')->plainTextToken;

        $this->newLine();
        $this->info('✅ Client created successfully!');
        $this->newLine();
        
        $this->table(
            ['Property', 'Value'],
            [
                ['Client ID', $client->id],
                ['Client Name', $client->name],
                ['Domain', $client->domain],
                ['Public Key', $publicKey],
                ['API Token', $token],
            ]
        );

        $this->newLine();
        $this->warn('🔐 Keep your API Token secure! Store it safely as it won\'t be shown again.');
        $this->info('📝 Use the Public Key in your request headers as "X-Public-Key"');
        $this->info('🔑 Use the API Token in your Authorization header as "Bearer {token}"');

        return 0;
    }
}
