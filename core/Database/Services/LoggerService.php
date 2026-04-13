<?php 

    namespace Core\Database\Services;

    use Monolog\Handler\StreamHandler;
    use Monolog\Logger;
    use Monolog\Level;

    class LoggerService
    {
        private Logger $logger;

        public function __construct()
        {
            $this->logger = new Logger('web');
            $this->logger->pushHandler(
                new StreamHandler(__DIR__ . '/../logs/log.txt', Level::Debug)
            );
        }

        public function log(string $message, Level $level = Level::Warning, array $context = []): void{
            $this->logger->log($level, $message, $context);
        }

    }





?>