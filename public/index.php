<?php

    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    use App\Database\Dao\UserDao;
    use App\Database\Entities\UserEntity;

    require '../vendor/autoload.php';

    $userDao = new UserDao();
    // $user = $userDao->findById(1);
    // $user->firstName = 'Alexandre';
    // $user->lastName = 'Santos';
    // $user->email = 'josefa2026@email.com';

    // $updated = $userDao->update($user);
    // dd($updated);

    // $deleted = $userDao->delete($user);

    // dd($deleted);

    $created = $userDao->insert([
        'firstName' => 'julio',
        'lastName' => 'marcos',
        'email' => 'julio@email.com',
        'password' => '444222'
    ]);

    dd($created);

    // dd($userEntity); // getter

    /*$users = $userDao->findAll(
        ['fields' => 'firstName, email']
    );*/

    /*$user = $userDao->findBy(
        'first_name',
        'Maria',
        ['fields' => 'id, firstName, lastName']
    );*/

    // $user = $userDao->findById(2, ['id', 'firstName', 'email']);

    // dd($user);






?>