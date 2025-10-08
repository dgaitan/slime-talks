<?php

declare(strict_types=1);

namespace SlimeTalks\SDK;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Slime Talks API PHP Client
 *
 * A comprehensive PHP SDK for interacting with the Slime Talks Messaging API.
 * Provides methods for managing clients, customers, channels, and messages.
 * Uses Laravel's HTTP client for seamless integration.
 *
 * @package SlimeTalks\SDK
 * @author Laravel Slime Talks
 * @version 1.0.0
 */
class SlimeTalksClient
{
    /**
     * Laravel HTTP client instance
     *
     * @var PendingRequest
     */
    private PendingRequest $httpClient;

    /**
     * API base URL
     *
     * @var string
     */
    private string $baseUrl;

    /**
     * API secret key
     *
     * @var string
     */
    private string $secretKey;

    /**
     * API public key
     *
     * @var string
     */
    private string $publicKey;

    /**
     * Origin domain
     *
     * @var string
     */
    private string $origin;

    /**
     * Create a new Slime Talks API client instance
     *
     * @param array{
     *     base_url: string,
     *     secret_key: string,
     *     public_key: string,
     *     origin: string,
     *     timeout?: int,
     *     retry?: array{times?: int, sleep?: int}
     * } $config Configuration array
     *
     * @example
     * $client = new SlimeTalksClient([
     *     'base_url' => 'https://api.slime-talks.com/api/v1',
     *     'secret_key' => 'sk_live_your_secret_key',
     *     'public_key' => 'pk_live_your_public_key',
     *     'origin' => 'https://yourdomain.com',
     * ]);
     */
    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['base_url'], '/');
        $this->secretKey = $config['secret_key'];
        $this->publicKey = $config['public_key'];
        $this->origin = $config['origin'];

        $this->httpClient = Http::baseUrl($this->baseUrl)
            ->timeout($config['timeout'] ?? 30)
            ->withHeaders([
                'Authorization' => "Bearer {$this->secretKey}",
                'X-Public-Key' => $this->publicKey,
                'Origin' => $this->origin,
                'Accept' => 'application/json',
            ])
            ->acceptJson();

        // Add retry logic if configured
        if (isset($config['retry'])) {
            $this->httpClient->retry(
                $config['retry']['times'] ?? 3,
                $config['retry']['sleep'] ?? 100
            );
        }
    }

    /**
     * Get client information
     *
     * @param string $clientUuid Client UUID
     * @return array<string, mixed> Client data
     * @throws SlimeTalksException When request fails
     */
    public function getClient(string $clientUuid): array
    {
        return $this->request('GET', "/client/{$clientUuid}");
    }

    /**
     * Create a new customer
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     metadata?: array<string, mixed>
     * } $data Customer data
     * @return array<string, mixed> Created customer
     * @throws SlimeTalksException When request fails
     */
    public function createCustomer(array $data): array
    {
        return $this->request('POST', '/customers', $data);
    }

    /**
     * Get customer by UUID
     *
     * @param string $customerUuid Customer UUID
     * @return array<string, mixed> Customer data
     * @throws SlimeTalksException When request fails
     */
    public function getCustomer(string $customerUuid): array
    {
        return $this->request('GET', "/customers/{$customerUuid}");
    }

    /**
     * List customers with pagination
     *
     * @param array{
     *     limit?: int,
     *     starting_after?: string
     * } $params Query parameters
     * @return array<string, mixed> Paginated customers
     * @throws SlimeTalksException When request fails
     */
    public function listCustomers(array $params = []): array
    {
        return $this->request('GET', '/customers', null, $params);
    }

    /**
     * Create a channel
     *
     * @param array{
     *     type: string,
     *     customer_uuids: array<string>,
     *     name?: string
     * } $data Channel data
     * @return array<string, mixed> Created channel
     * @throws SlimeTalksException When request fails
     */
    public function createChannel(array $data): array
    {
        return $this->request('POST', '/channels', $data);
    }

    /**
     * Get channel by UUID
     *
     * @param string $channelUuid Channel UUID
     * @return array<string, mixed> Channel data
     * @throws SlimeTalksException When request fails
     */
    public function getChannel(string $channelUuid): array
    {
        return $this->request('GET', "/channels/{$channelUuid}");
    }

    /**
     * List channels with pagination
     *
     * @param array{
     *     limit?: int,
     *     starting_after?: string
     * } $params Query parameters
     * @return array<string, mixed> Paginated channels
     * @throws SlimeTalksException When request fails
     */
    public function listChannels(array $params = []): array
    {
        return $this->request('GET', '/channels', null, $params);
    }

    /**
     * Get channels for a specific customer
     *
     * @param string $customerUuid Customer UUID
     * @return array<string, mixed> Customer's channels
     * @throws SlimeTalksException When request fails
     */
    public function getCustomerChannels(string $customerUuid): array
    {
        return $this->request('GET', "/channels/customer/{$customerUuid}");
    }

    /**
     * Send a message to a channel
     *
     * @param array{
     *     channel_uuid: string,
     *     sender_uuid: string,
     *     type: string,
     *     content: string,
     *     metadata?: array<string, mixed>
     * } $data Message data
     * @return array<string, mixed> Sent message
     * @throws SlimeTalksException When request fails
     */
    public function sendMessage(array $data): array
    {
        return $this->request('POST', '/messages', $data);
    }

    /**
     * Get messages from a channel
     *
     * @param string $channelUuid Channel UUID
     * @param array{
     *     limit?: int,
     *     starting_after?: string
     * } $params Query parameters
     * @return array<string, mixed> Paginated messages
     * @throws SlimeTalksException When request fails
     */
    public function getChannelMessages(string $channelUuid, array $params = []): array
    {
        return $this->request('GET', "/messages/channel/{$channelUuid}", null, $params);
    }

    /**
     * Get messages for a specific customer
     *
     * @param string $customerUuid Customer UUID
     * @param array{
     *     limit?: int,
     *     starting_after?: string
     * } $params Query parameters
     * @return array<string, mixed> Paginated messages
     * @throws SlimeTalksException When request fails
     */
    public function getCustomerMessages(string $customerUuid, array $params = []): array
    {
        return $this->request('GET', "/messages/customer/{$customerUuid}", null, $params);
    }

    /**
     * Make an HTTP request to the API
     *
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array<string, mixed>|null $data Request data
     * @param array<string, mixed> $queryParams Query parameters
     * @return array<string, mixed> Response data
     * @throws SlimeTalksException When request fails
     */
    private function request(
        string $method,
        string $endpoint,
        ?array $data = null,
        array $queryParams = []
    ): array {
        try {
            $response = match (strtoupper($method)) {
                'GET' => $this->httpClient->get($endpoint, $queryParams),
                'POST' => $this->httpClient->post($endpoint, $data),
                'PUT' => $this->httpClient->put($endpoint, $data),
                'PATCH' => $this->httpClient->patch($endpoint, $data),
                'DELETE' => $this->httpClient->delete($endpoint, $data),
                default => throw new SlimeTalksException("Unsupported HTTP method: {$method}"),
            };

            // Throw exception for HTTP errors
            $response->throw();

            return $response->json();
        } catch (RequestException $e) {
            $errorMessage = $this->parseErrorMessage($e);
            throw new SlimeTalksException(
                "API request failed: {$errorMessage}",
                $e->response?->status() ?? 0,
                $e
            );
        } catch (\Exception $e) {
            throw new SlimeTalksException(
                "Unexpected error: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Parse error message from exception
     *
     * @param RequestException $exception Request exception
     * @return string Error message
     */
    private function parseErrorMessage(RequestException $exception): string
    {
        $response = $exception->response;

        if (!$response) {
            return $exception->getMessage();
        }

        $body = $response->json();

        // Handle different error response formats
        if (isset($body['error']['message'])) {
            return $body['error']['message'];
        }

        if (isset($body['error'])) {
            return is_string($body['error']) ? $body['error'] : json_encode($body['error']);
        }

        if (isset($body['message'])) {
            return $body['message'];
        }

        return "HTTP {$response->status()}: {$response->body()}";
    }

    /**
     * Get the underlying HTTP client
     *
     * Useful for testing or advanced customization
     *
     * @return PendingRequest HTTP client instance
     */
    public function getHttpClient(): PendingRequest
    {
        return $this->httpClient;
    }
}

/**
 * Slime Talks API Exception
 *
 * Custom exception for API errors
 *
 * @package SlimeTalks\SDK
 */
class SlimeTalksException extends \Exception
{
    /**
     * Create a new exception instance
     *
     * @param string $message Error message
     * @param int $code Error code
     * @param \Throwable|null $previous Previous exception
     */
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}