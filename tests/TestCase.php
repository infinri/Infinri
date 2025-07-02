<?php declare(strict_types=1);

namespace Tests;

use App\Application;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Slim\Psr7\Stream;
use Slim\Psr7\Uri;

class TestCase extends BaseTestCase
{
    /** @var Application */
    protected $app;
    
    /** @var ContainerInterface */
    protected $container;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a new application instance for testing
        $this->app = require __DIR__ . '/bootstrap.php';
        $this->container = $this->app->getContainer();
        
        // Set up test database connection
        $this->setUpDatabase();
        
        // Set up test environment
        $this->setupTestEnvironment();
    }
    
    /**
     * Set up the test environment.
     */
    protected function setupTestEnvironment(): void
    {
        // Set the application environment to testing
        putenv('APP_ENV=testing');
        
        // Use main database configuration for testing
        $this->container->get('settings')['db'] = [
            'driver' => $_ENV['DB_CONNECTION'] ?? 'pgsql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '5432',
            'database' => $_ENV['DB_DATABASE'] ?? 'infinri',
            'username' => $_ENV['DB_USERNAME'] ?? 'postgres',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix' => '',
        ];
        
        // Set up test mailer
        $this->container->set('mailer', function () {
            return $this->createMock(\Laminas\Mail\Transport\TransportInterface::class);
        });
        
        // Set up test session
        $this->container->set('session', new class {
            private $data = [];
            
            public function get($key, $default = null)
            {
                return $this->data[$key] ?? $default;
            }
            
            public function set($key, $value)
            {
                $this->data[$key] = $value;
            }
            
            public function remove($key)
            {
                unset($this->data[$key]);
            }
            
            public function clear()
            {
                $this->data = [];
            }
        });
    }
    
    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Clean up test database
        $this->tearDownDatabase();
    }
    
    /**
     * Set up the test database and run migrations.
     */
    protected function setUpDatabase(): void
    {
        $dbConfig = $this->container->get('settings')['db'];
        
        // Create database connection
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $dbConfig['host'],
            $dbConfig['port'] ?? '5432',
            $dbConfig['database']
        );
        
        try {
            $pdo = new \PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            
            // Truncate all tables instead of dropping them
            $tables = $pdo->query("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_type = 'BASE TABLE'
            ")->fetchAll(\PDO::FETCH_COLUMN);
            
            if (!empty($tables)) {
                // Disable foreign key checks
                $pdo->exec('SET session_replication_role = replica;');
                
                // Truncate all tables
                foreach ($tables as $table) {
                    $pdo->exec("TRUNCATE TABLE \"$table\" CASCADE");
                }
                
                // Re-enable foreign key checks
                $pdo->exec('SET session_replication_role = DEFAULT;');
            }
            
            // Run migrations
            $this->runMigrations($pdo);
            
        } catch (\PDOException $e) {
            $this->fail("Failed to set up test database: " . $e->getMessage());
        }
    }
    
    /**
     * Run database migrations.
     */
    protected function runMigrations(\PDO $pdo): void
    {
        $migrationsPath = __DIR__ . '/../database/migrations';
        if (!is_dir($migrationsPath)) {
            return;
        }
        
        // Get all migration files
        $migrations = glob($migrationsPath . '/*.php');
        sort($migrations);
        
        // Run each migration
        foreach ($migrations as $migration) {
            $migrationClass = require $migration;
            if ($migrationClass instanceof \Closure) {
                $migrationClass($pdo);
            }
        }
    }
    
    /**
     * Clean up the test database.
     */
    protected function tearDownDatabase(): void
    {
        // No need to clean up after each test since we're using the main database
        // and we want to keep the data for inspection
    }
    
    /**
     * Create a test request.
     */
    protected function createRequest(
        string $method,
        string $path,
        array $queryParams = [],
        array $parsedBody = [],
        array $headers = [],
        array $cookies = [],
        array $serverParams = []
    ): ServerRequestInterface {
        $uri = new Uri('', '', 80, $path, http_build_query($queryParams));
        
        $request = (new ServerRequestFactory())
            ->createServerRequest($method, $uri, array_merge([
                'REMOTE_ADDR' => '127.0.0.1',
                'HTTP_USER_AGENT' => 'PHPUnit',
            ], $serverParams));
            
        // Add headers
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        
        // Add cookies
        foreach ($cookies as $name => $value) {
            $request = $request->withCookieParams([$name => $value] + $request->getCookieParams());
        }
        
        // Add parsed body for POST/PUT/PATCH requests
        if (!empty($parsedBody) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $request = $request->withParsedBody($parsedBody);
        }
        
        // Add session to request
        if ($this->container->has('session')) {
            $request = $request->withAttribute('session', $this->container->get('session'));
        }
        
        return $request;
    }
    
    /**
     * Make a request to the application.
     */
    protected function request(
        string $method,
        string $path,
        array $data = [],
        array $headers = [],
        array $cookies = [],
        array $serverParams = []
    ): ResponseInterface {
        $queryParams = [];
        $parsedBody = [];
        
        if ($method === 'GET') {
            $queryParams = $data;
        } else {
            // For non-GET requests, add CSRF token if not present
            if (!isset($data['csrf_name']) && $this->container->has('csrf')) {
                $csrf = $this->container->get('csrf');
                $data[$csrf->getTokenNameKey()] = $csrf->getTokenName();
                $data[$csrf->getTokenValueKey()] = $csrf->getTokenValue();
            }
            $parsedBody = $data;
        }
        
        $request = $this->createRequest(
            $method,
            $path,
            $queryParams,
            $parsedBody,
            array_merge([
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ], $headers),
            $cookies,
            $serverParams
        );
        
        // Handle session
        if ($this->container->has('session')) {
            $session = $this->container->get('session');
            $request = $request->withAttribute('session', $session);
        }
        
        // Process the request through the application
        $response = $this->app->handle($request);
        
        // Store session data for assertions
        if (isset($session) && $response->hasHeader('Set-Cookie')) {
            $cookies = [];
            foreach ($response->getHeader('Set-Cookie') as $cookie) {
                if (preg_match('/([^=]+)=([^;]+)/', $cookie, $matches)) {
                    $cookies[$matches[1]] = $matches[2];
                }
            }
            // Update session data from cookies if needed
        }
        
        return $response;
    }
    
    /**
     * Make a GET request to the application.
     */
    protected function get(string $path, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('GET', $path, $data, $headers);
    }
    
    /**
     * Make a POST request to the application.
     */
    protected function post(string $path, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('POST', $path, $data, $headers);
    }
    
    /**
     * Make a PUT request to the application.
     */
    protected function put(string $path, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('PUT', $path, $data, $headers);
    }
    
    /**
     * Make a DELETE request to the application.
     */
    protected function delete(string $path, array $data = [], array $headers = []): ResponseInterface
    {
        return $this->request('DELETE', $path, $data, $headers);
    }
    
    /**
     * Assert that the response has the given status code.
     */
    protected function assertResponseStatus(ResponseInterface $response, int $code): void
    {
        $this->assertEquals($code, $response->getStatusCode());
    }
    
    /**
     * Assert that the response contains the given string.
     */
    protected function assertResponseContains(ResponseInterface $response, string $needle): void
    {
        $body = (string) $response->getBody();
        $this->assertStringContainsString($needle, $body);
    }
    
    /**
     * Assert that the response does not contain the given string.
     */
    protected function assertResponseNotContains(ResponseInterface $response, string $needle): void
    {
        $body = (string) $response->getBody();
        $this->assertStringNotContainsString($needle, $body);
    }
    
    /**
     * Assert that the response is a redirect to the given URI.
     */
    protected function assertRedirectsTo(ResponseInterface $response, string $uri): void
    {
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals($uri, $response->getHeaderLine('Location'));
    }
    
    /**
     * Assert that the session has the given error message.
     */
    protected function assertSessionHasError(ResponseInterface $response, string $key, string $message = null): void
    {
        $session = $this->container->get('session');
        $errors = $session->getFlash('errors', []);
        
        $this->assertArrayHasKey($key, $errors);
        
        if ($message !== null) {
            $this->assertStringContainsString($message, $errors[$key]);
        }
    }
    
    /**
     * Assert that the session has the given flash message.
     */
    protected function assertSessionHas(ResponseInterface $response, string $key, $value = null): void
    {
        $session = $this->container->get('session');
        $flash = $session->getFlash($key);
        
        if ($value === null) {
            $this->assertNotEmpty($flash);
        } else {
            $this->assertEquals($value, $flash);
        }
    }
    
    /**
     * Assert that the session has no errors.
     */
    protected function assertSessionHasNoErrors(ResponseInterface $response): void
    {
        $session = $this->container->get('session');
        $errors = $session->getFlash('errors', []);
        
        $this->assertEmpty($errors, 'Session has unexpected errors: ' . json_encode($errors));
    }
}
