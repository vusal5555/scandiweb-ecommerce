<?php

namespace App\Controller;

use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL as GraphQLBase;
use RuntimeException;
use Throwable;

class GraphQL
{
    protected $schema;

    public function __construct()
    {
        // Load environment variables if not already loaded
        if (!isset($_ENV['APP_ENV'])) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../');
            $dotenv->load();
        }

        $this->schema = require __DIR__ . '/../../../config/schema.php';
    }

    public function handle(): array
    {
        try {
            // Read the raw input
            $rawInput = file_get_contents('php://input');
            if ($rawInput === false) {
                throw new RuntimeException('Failed to get php://input');
            }

            // Decode the JSON input
            $input = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Invalid JSON input: ' . json_last_error_msg());
            }

            // Extract query and variables
            $query = $input['query'] ?? null;
            $variableValues = $input['variables'] ?? null;

            if (!$query) {
                throw new RuntimeException('No valid GraphQL query provided.');
            }

            // Optionally define a root value if needed
            $rootValue = null; // or any specific root value as required
            $context = []; // Include context here if needed, such as user information

            // Determine debug flags based on environment
            $debug = ($_ENV['APP_ENV'] === 'development')
            ? DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE
            : DebugFlag::NONE;

            // Execute the GraphQL query
            $result = GraphQLBase::executeQuery(
                $this->schema,
                $query,
                $rootValue,
                $context,
                $variableValues
            );
            $output = $result->toArray($debug);
        } catch (Throwable $e) {
            // Optional logging in production
            if ($_ENV['APP_ENV'] !== 'development') {
                error_log($e->getMessage());
            }

            // Construct a structured error response
            $output = [
                'errors' => [
                    [
                        'message' => $e->getMessage(),
                        'extensions' => [
                            'code' => 'INTERNAL_SERVER_ERROR',
                            'exception' => $_ENV['APP_ENV'] === 'development' ? $e->getTraceAsString() : null,
                        ],
                    ],
                ],
            ];
        }

        return $output;
    }
}
