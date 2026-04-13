<?php

    namespace Core\Database\Cache;

    use Predis\Client;

    class RedisClient
    {
        private static $instance;

        public static function getInstance(){
            if(!self::$instance){
                self::$instance = new Client([
                    'scheme' => 'tcp',
                    'host' => '127.0.0.1',
                    'port' => 6379,
                ]);
            }
            return self::$instance;
        }

    }




?>