<?php

    ini_set('display_errors', 0);
    ini_set('log_errors',1);
    error_reporting(E_ALL);

    use App\Database\Dao\UserDao;
    use App\Database\Entities\UserEntity;
    use Core\Database\Services\LoggerService;
    use Monolog\Level;

    require '../vendor/autoload.php';

    $userDao = new UserDao();
    $logger = new LoggerService();

    set_error_handler(function($level, $message, $file, $line) use($logger){
        $logger->log("$message em $file:$line", Level::Error, ['level' => $level]);
    });

    set_exception_handler(function($exception) use ($logger){
        $logger->log(
            $exception->getMessage(),
            Level::Critical, 
            [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceException()
            ]
            );
    });

    register_shutdown_function(function() use($logger){
        $error = error_get_last();

        if($error !== null){
            $logger->log(
                $error['message'] . " em {$error['file']}:{$error['line']}",
                Level::Emergency
            );
        }
    });


    // $user = $userDao->findById(1);
    // $user->firstName = 'Alexandre';
    // $user->lastName = 'Santos';
    // $user->email = 'josefa2026@email.com';

    // $updated = $userDao->update($user);
    // dd($updated);

    // $deleted = $userDao->delete($user);

    // dd($deleted);

    /*$created = $userDao->insert([
        'firstName' => 'James',
        'lastName' => 'Milton',
        'email' => 'james@email.com',
        'password' => '555999'
    ]);*/

    // dd($created);

    // dd($userEntity); // getter

    /*$users = $userDao->findAll(
        ['fields' => 'firstName, email']
    );*/

    /*$user = $userDao->findBy(
        'first_name',
        'Maria',
        ['fields' => 'id, firstName, lastName']
    );*/

    // $user = $userDao->findById(10, ['id', 'firstName', 'email']);

    // dd($user);






?>