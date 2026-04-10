<?php

    namespace App\Database\Entities;
    use Core\Database\Entities\AbstractEntity;
    use Exception;

    /**
     * @property ?int $id
     * @property ?string $firstName
     * @property ?string $lastName
     * @property ?string $email
     * @property ?string $password
     */
    class UserEntity extends AbstractEntity
    {
        protected ?int $id;
        protected ?string $firstName;
        protected ?string $lastName;
        protected ?string $email;
        protected ?string $password = null;


        public function getId(): ?int {
            return $this->id;
        }

        public function setId(?int $id) {
            $this->id = $id;
        }

        public function getFirstName(): ?string {
            return $this->firstName;
        }

        public function setFirstName(?string $firstName) {
            $this->firstName = ucfirst($firstName);
        }

        public function getLastName(): ?string {
            return $this->lastName;
        }

        public function setLastName(?string $lastName) {
            $this->lastName = ucfirst($lastName);
        }

        public function getEmail(): ?string {
            return $this->email;
        }

        public function setEmail(?string $email) {
            if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
                throw new Exception("Email invalid format!");
            }
            $this->email = strtolower($email);
        }

        public function getPassword(): ?string {
            return $this->password;
        }

        public function setPassword(?string $password) {
            $this->password = password_hash($password, PASSWORD_BCRYPT);
        }
    }


?>