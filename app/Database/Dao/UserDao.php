<?php

    namespace App\Database\Dao;
    use App\Database\Entities\UserEntity;
    use Core\Database\Dao\AbstractDao;

    /**
     * Summary of UserDao
     * @extends AbstractDao<UserEntity>
     */
    class UserDao extends AbstractDao
    {
        protected string $table = 'users';
        protected string $entity = UserEntity::class;
    }



?>