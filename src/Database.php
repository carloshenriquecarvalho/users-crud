<?php

use Dotenv\Dotenv;

class Database
{
    private string $dbName;
    private string $dbUser;
    private string $dbPassword;
    private string $dbHost;
    private string $dbCharset;
    private string $dbDriver;
    private ?PDO $pdo = null;
    private static ?Database $instance = null;

    /**
     * @throws Exception
     */
    private function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
        $this->loadConfig();
        $this->connect();
    }

    public static function getInstance(): Database
    {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    private function loadConfig(): void
    {
        $this->dbName = $_ENV['DB_NAME'];
        $this->dbUser = $_ENV['DB_USER'];
        $this->dbPassword = $_ENV['DB_PASSWORD'];
        $this->dbHost = $_ENV['DB_HOST'];
        $this->dbCharset = $_ENV['DB_CHARSET'];
        $this->dbDriver = $_ENV['DB_DRIVER'];
    }

    /**
     * @throws Exception
     */
    private function connect(): void
    {
        $dsn = "{$this->dbDriver}:host={$this->dbHost};dbname={$this->dbName};charset={$this->dbCharset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        $intAttempt = 0;

        while ($intAttempt < 3) {
            try {
                $this->pdo = new PDO($dsn, $this->dbUser, $this->dbPassword, $options);
                return;
            } catch (PDOException $e) {
                $this->logError($e->getMessage());
                if ($intAttempt < 2) {
                    sleep(2);
                } else {
                    throw new Exception($e->getMessage());
                }
                $intAttempt++;
            }
        }
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    private function logError(string $message): void
    {
        $log_file = __DIR__ . "/../logs/errors.log";
        $time_stamp = date("Y-m-d H:i:s");
        $message = $time_stamp . " " . $message . PHP_EOL;
        file_put_contents($log_file, $message, FILE_APPEND);
    }

    private function __clone() {}

    /**
     * @throws Exception
     */
    public function __wakeup() {throw new Exception("Cannot unserialize a singleton.");}
}