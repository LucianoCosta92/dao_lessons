<?php
    namespace Core\Database;
    use PDO;
    use Dotenv\Dotenv;

    class Connection
    {

        public static function getConnection(): PDO {
            $dotenv = Dotenv::createImmutable(dirname(__DIR__,2));
            $dotenv->load();

            return new PDO(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }
    }


?>