<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * IPFS Service
 * 
 * Handles file upload and retrieval from IPFS network
 */
class IpfsService
{
    private $client;
    private $ipfsGateway;
    private $ipfsApi;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60,
            'headers' => [
                'User-Agent' => 'Taxparency/1.0'
            ]
        ]);

        // IPFS node configuration
        $this->ipfsApi = env('IPFS_API_URL', 'http://localhost:5001');
        $this->ipfsGateway = env('IPFS_GATEWAY_URL', 'https://gateway.pinata.cloud/ipfs/');
    }

    /**
     * Upload file to IPFS
     *
     * @param UploadedFile|string $file
     * @param array $metadata
     * @return array
     * @throws \Exception
     */
    public function uploadFile($file, array $metadata = []): array
    {
        try {
            if ($file instanceof UploadedFile) {
                $filePath = $file->getPathname();
                $fileName = $file->getClientOriginalName();
                $mimeType = $file->getMimeType();
            } else {
                // Assuming it's a file path
                $filePath = $file;
                $fileName = basename($file);
                $mimeType = mime_content_type($file);
            }

            // Check if local IPFS node is available
            if ($this->isLocalNodeAvailable()) {
                return $this->uploadToLocalNode($filePath, $fileName, $mimeType, $metadata);
            }

            // Fallback to Pinata service
            return $this->uploadToPinata($filePath, $fileName, $mimeType, $metadata);

        } catch (\Exception $e) {
            Log::error('IPFS upload failed', [
                'error' => $e->getMessage(),
                'file' => $fileName ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('Failed to upload file to IPFS: ' . $e->getMessage());
        }
    }

    /**
     * Upload file to local IPFS node
     */
    private function uploadToLocalNode(string $filePath, string $fileName, string $mimeType, array $metadata): array
    {
        $response = $this->client->post($this->ipfsApi . '/api/v0/add', [
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => $fileName
                ]
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (!isset($result['Hash'])) {
            throw new \Exception('IPFS upload failed: No hash returned');
        }

        // Pin the file to ensure it stays on the network
        $this->pinFile($result['Hash']);

        return [
            'hash' => $result['Hash'],
            'size' => $result['Size'] ?? null,
            'name' => $result['Name'] ?? $fileName,
            'gateway_url' => $this->ipfsGateway . $result['Hash'],
            'metadata' => array_merge($metadata, [
                'mime_type' => $mimeType,
                'upload_method' => 'local_node',
                'uploaded_at' => now()->toISOString()
            ])
        ];
    }

    /**
     * Upload file to Pinata (IPFS pinning service)
     */
    private function uploadToPinata(string $filePath, string $fileName, string $mimeType, array $metadata): array
    {
        $pinataApiKey = env('PINATA_API_KEY');
        $pinataSecretKey = env('PINATA_SECRET_API_KEY');

        if (!$pinataApiKey || !$pinataSecretKey) {
            throw new \Exception('Pinata API credentials not configured');
        }

        $response = $this->client->post('https://api.pinata.cloud/pinning/pinFileToIPFS', [
            'headers' => [
                'pinata_api_key' => $pinataApiKey,
                'pinata_secret_api_key' => $pinataSecretKey,
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r'),
                    'filename' => $fileName
                ],
                [
                    'name' => 'pinataMetadata',
                    'contents' => json_encode([
                        'name' => $fileName,
                        'keyvalues' => array_merge($metadata, [
                            'project' => 'taxparency',
                            'mime_type' => $mimeType
                        ])
                    ])
                ],
                [
                    'name' => 'pinataOptions',
                    'contents' => json_encode([
                        'cidVersion' => 1
                    ])
                ]
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        if (!isset($result['IpfsHash'])) {
            throw new \Exception('Pinata upload failed: No hash returned');
        }

        return [
            'hash' => $result['IpfsHash'],
            'size' => $result['PinSize'] ?? null,
            'name' => $fileName,
            'gateway_url' => $this->ipfsGateway . $result['IpfsHash'],
            'metadata' => array_merge($metadata, [
                'mime_type' => $mimeType,
                'upload_method' => 'pinata',
                'uploaded_at' => now()->toISOString(),
                'timestamp' => $result['Timestamp'] ?? null
            ])
        ];
    }

    /**
     * Retrieve file from IPFS
     *
     * @param string $hash
     * @return string
     */
    public function getFile(string $hash): string
    {
        try {
            // Try local node first
            if ($this->isLocalNodeAvailable()) {
                $response = $this->client->get($this->ipfsApi . '/api/v0/cat?arg=' . $hash);
                return $response->getBody()->getContents();
            }

            // Fallback to public gateway
            $response = $this->client->get($this->ipfsGateway . $hash);
            return $response->getBody()->getContents();

        } catch (RequestException $e) {
            Log::error('IPFS file retrieval failed', [
                'hash' => $hash,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Failed to retrieve file from IPFS: ' . $e->getMessage());
        }
    }

    /**
     * Get file info from IPFS
     *
     * @param string $hash
     * @return array
     */
    public function getFileInfo(string $hash): array
    {
        try {
            if ($this->isLocalNodeAvailable()) {
                $response = $this->client->post($this->ipfsApi . '/api/v0/object/stat?arg=' . $hash);
                $result = json_decode($response->getBody()->getContents(), true);

                return [
                    'hash' => $hash,
                    'size' => $result['DataSize'] ?? null,
                    'links' => $result['NumLinks'] ?? 0,
                    'gateway_url' => $this->ipfsGateway . $hash
                ];
            }

            // For external gateways, we can only provide basic info
            return [
                'hash' => $hash,
                'size' => null,
                'links' => null,
                'gateway_url' => $this->ipfsGateway . $hash
            ];

        } catch (RequestException $e) {
            Log::error('IPFS file info retrieval failed', [
                'hash' => $hash,
                'error' => $e->getMessage()
            ]);

            throw new \Exception('Failed to get file info from IPFS: ' . $e->getMessage());
        }
    }

    /**
     * Pin file to local IPFS node
     *
     * @param string $hash
     * @return bool
     */
    public function pinFile(string $hash): bool
    {
        try {
            if (!$this->isLocalNodeAvailable()) {
                return false;
            }

            $response = $this->client->post($this->ipfsApi . '/api/v0/pin/add?arg=' . $hash);
            $result = json_decode($response->getBody()->getContents(), true);

            return isset($result['Pins']) && in_array($hash, $result['Pins']);

        } catch (RequestException $e) {
            Log::warning('IPFS file pinning failed', [
                'hash' => $hash,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Unpin file from local IPFS node
     *
     * @param string $hash
     * @return bool
     */
    public function unpinFile(string $hash): bool
    {
        try {
            if (!$this->isLocalNodeAvailable()) {
                return false;
            }

            $response = $this->client->post($this->ipfsApi . '/api/v0/pin/rm?arg=' . $hash);
            $result = json_decode($response->getBody()->getContents(), true);

            return isset($result['Pins']) && in_array($hash, $result['Pins']);

        } catch (RequestException $e) {
            Log::warning('IPFS file unpinning failed', [
                'hash' => $hash,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Check if local IPFS node is available
     *
     * @return bool
     */
    public function isLocalNodeAvailable(): bool
    {
        try {
            $response = $this->client->get($this->ipfsApi . '/api/v0/version', [
                'timeout' => 5
            ]);

            return $response->getStatusCode() === 200;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get IPFS node version and info
     *
     * @return array
     */
    public function getNodeInfo(): array
    {
        try {
            if (!$this->isLocalNodeAvailable()) {
                return [
                    'status' => 'unavailable',
                    'message' => 'Local IPFS node not available'
                ];
            }

            $response = $this->client->get($this->ipfsApi . '/api/v0/version');
            $version = json_decode($response->getBody()->getContents(), true);

            $response = $this->client->get($this->ipfsApi . '/api/v0/id');
            $id = json_decode($response->getBody()->getContents(), true);

            return [
                'status' => 'available',
                'version' => $version['Version'] ?? 'unknown',
                'id' => $id['ID'] ?? 'unknown',
                'addresses' => $id['Addresses'] ?? [],
                'api_url' => $this->ipfsApi,
                'gateway_url' => $this->ipfsGateway
            ];

        } catch (\Exception $e) {
            Log::error('Failed to get IPFS node info', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate IPFS hash format
     *
     * @param string $hash
     * @return bool
     */
    public static function isValidHash(string $hash): bool
    {
        // Basic validation for IPFS hash formats (CIDv0 and CIDv1)
        if (preg_match('/^Qm[1-9A-HJ-NP-Za-km-z]{44}$/', $hash)) {
            return true; // CIDv0
        }

        if (preg_match('/^[a-z0-9]{59}$/', $hash)) {
            return true; // CIDv1 (simplified check)
        }

        return false;
    }
}
