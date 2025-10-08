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
     * @var string
     */
    protected $signature = 'slime-chat:start-client';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a new client for Slime Chat with interactive setup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Slime Chat Client Setup...');
        $this->newLine();

        // Prompt for client name
        $clientName = $this->ask('What is the client name?');
        
        if (empty($clientName)) {
            $this->error('Client name is required!');
            return 1;
        }

        // Prompt for domain
        $domain = $this->ask('What is the full domain where requests will be received? (e.g., example.com)');
        
        if (empty($domain)) {
            $this->error('Domain is required!');
            return 1;
        }

        // Validate domain format
        if (!filter_var('http://' . $domain, FILTER_VALIDATE_URL)) {
            $this->error('Please provide a valid domain format!');
            return 1;
        }

        // Generate public key
        $publicKey = 'pk_' . Str::random(32);
        
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
