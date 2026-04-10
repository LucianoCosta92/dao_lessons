<?php 

    namespace Core\Database\Dao;

    use Core\Database\Connection;
    use Core\Database\Entities\AbstractEntity;
    use Core\Database\EntityManager;
    use Exception;
    use Monolog\Handler\StreamHandler;
    use Monolog\Logger;
    use PDO;
    use PDOException;

    /**
     * @template T of AbstractEntity
     */
    abstract class AbstractDao
    {
        protected PDO $connection;
        protected string $table;
        protected string $entity;
        protected EntityManager $entityManager;

        public function __construct()
        {
            $this->connection = Connection::getConnection();
            $this->entityManager = new EntityManager();
        }

        /**
         * Summary of findAll
         * @param array $options
         * @throws Exception
         * @return T[]|null <- array de entidades do tipo T (ex: UserEntity[])
         */
        public function findAll(array $options = []): ?array {
            $allowedFields = ['id', 'firstName', 'lastName', 'email'];
            
            $fields = $options['fields'] ?? $allowedFields;

            if(is_string($fields)){
                $fields = explode(',', $fields);
            }

            $fields = array_map('trim', $fields);

            if(!empty(array_diff($fields, $allowedFields))){
                throw new Exception("Invalid field(s): " . implode(', ', array_diff($fields, $allowedFields)));
            }

            $fieldSql = implode(', ', $fields);

            $sql = "SELECT {$fieldSql} FROM {$this->table}";
            $select = $this->connection->query($sql);
            $data = $select->fetchAll();
            return $this->entityManager->mapToEntity($this->entity, $data);
        }

        /**
         * Summary of findBy
         * @param string $field
         * @param mixed $value
         * @param array $options
         * @throws Exception
         * @return T|null
         */
        public function findBy(string $field, mixed $value, array $options = []): ?AbstractEntity {
            $allowedFields = ['id', 'firstName', 'lastName', 'email'];

            if(!in_array($field, $allowedFields)){
                throw new Exception("Invalid filter field: {$field}");
            }
            
            $fields = $options['fields'] ?? $allowedFields;

            if(is_string($fields)){
                $fields = explode(',', $fields);
            }

            $fields = array_map('trim', $fields);

            if(!empty(array_diff($fields, $allowedFields))){
                throw new Exception("Invalid field(s): " . implode(', ', array_diff($fields, $allowedFields)));
            }

            $fieldSql = implode(', ', $fields);

            $sql = "SELECT {$fieldSql} FROM {$this->table} WHERE {$field} = :value";
            $prepare = $this->connection->prepare($sql);
            $prepare->execute([
                'value' => $value
            ]);

            $data = $prepare->fetch();

            if(!$data){
                return null;
            }

            $entity = $this->entityManager->mapToEntity($this->entity, $data);
            $this->entityManager->snapshotEntityManager->takeSnapshot($entity);
            return $entity;
        }

        /**
         * Summary of findById
         * @param int $id
         * @param array|string $fields
         * @return T|null
         */
        public function findById(int $id, array|string $fields = []): ?AbstractEntity {
            $options = [];

            if(!empty($fields)){
                $options['fields'] = $fields;
            }
            
            return $this->findBy('id', $id, $options);
        }

        public function insert(AbstractEntity|array $arrayOrEntity): AbstractEntity{
            try{
                $data = $this->entityManager->normalizeDataToArray($arrayOrEntity);
                $fields = implode(',', array_keys($data));
                $placeholders = ':' . implode(',:', array_keys($data));
                $sql = "INSERT INTO {$this->table}({$fields}) VALUES({$placeholders})";
                $prepare = $this->connection->prepare($sql);
                $prepare->execute($data);
                $lastInsertedId = $this->connection->lastInsertId();

                $this->logAction("Novo registro inserido em {$this->table}. ID: {$lastInsertedId}", Logger::INFO, $data);
                return $this->findById($lastInsertedId);
            }catch (\PDOException $e){
                $this->logAction("FALHA AO INSERIR em {$this->table}", Logger::ERROR, ['error' => $e->getMessage()]);
                throw $e;
            }
        }

        public function update(?AbstractEntity $entity): ?int{
            if(is_null($entity)){
                return null;
            }

            if(!$this->entityManager->snapshotEntityManager->snapshotTaken()){
                throw new Exception('To update use find method');
            }

            $properties = $this->entityManager->snapshotEntityManager->propertiesChanged($entity);

            if(!$properties){
                return null;
            }

            try{
                $sets = implode(', ', array_map(fn($field) => "{$field} = :{$field}", array_keys($properties)));

                $sql = "UPDATE {$this->table} SET {$sets} WHERE id = :id";
                $prepare = $this->connection->prepare($sql);
                $prepare->execute([
                    ...$properties, // O prefixo ... (spread operator) "desempacota" o array $properties.
                    'id' => $entity->id
                ]);

                $rowCount = $prepare->rowCount();

                if($rowCount > 0){
                    $this->logAction(
                        "Registro atualizado",
                        Logger::NOTICE,
                        [
                            'tabela' => $this->table,
                            'id' => $entity->id,
                            'alteracoes' => $properties
                        ]
                    );
                }

                $this->entityManager->snapshotEntityManager->clearSnapshot();

                return $rowCount;
            }catch (\PDOException $e) {
                $this->logAction("FALHA AO ATUALIZAR em {$this->table} (ID: {$entity->id})", Logger::ERROR, ['error' => $e->getMessage()]);
                throw $e;
            }

        }

        public function delete(?AbstractEntity $entity): ?int{
            if(is_null($entity)){
                return null;
            }

            try{
                $sql = "DELETE FROM {$this->table} WHERE id = :id";
                $prepare = $this->connection->prepare($sql);
                $prepare->execute([
                    'id' => $entity->id
                ]);
                
                $rowCount = $prepare->rowCount();

                if($rowCount > 0){
                    $this->logAction("Usuário deletado em {$this->table}", Logger::WARNING, ['id_deletado' => $entity->id]);
                }

                return $rowCount;
            }catch(\PDOException $e){
                $this->logAction("FALHA AO DELETAR em {$this->table}", Logger::ERROR, ['id' => $entity->id, 'error' => $e->getMessage()]);
                return null;
            }
        }

        private function logAction(string $message, int $level = Logger::WARNING, array $context = []): void{
            try{
                $logger = new Logger('web');
                $logPath = __DIR__ . '/../logs/log.txt';

                $logger->pushHandler(new StreamHandler($logPath, Logger::DEBUG));

                $logger->addRecord($level, $message, $context);
            }catch(\Exception $e){
                error_log("Erro Monolog: " . $e->getMessage());
            }

        }

    }




?>