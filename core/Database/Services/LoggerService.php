<?php 

    namespace Core\Database\Services;

    use Monolog\Formatter\LineFormatter;
    use Monolog\Handler\StreamHandler;
    use Monolog\Logger;
    use Monolog\Level;

    class LoggerService
    {
        private Logger $logger;

        public function __construct()
        {
            $this->logger = new Logger('web');
            $lineFormatter = new LineFormatter();
            $lineFormatter->setDateFormat('d/m/Y H:i:s');
            $streamHandler = new StreamHandler(__DIR__ . '/../logs/log.txt', Level::Debug);
            $streamHandler->setFormatter($lineFormatter);
            $this->logger->pushHandler($streamHandler);
        }

        public function log(string $message, Level $level = Level::Warning, array $context = []): void{
            $this->logger->log($level, $message, $context);
        }

    }





?>