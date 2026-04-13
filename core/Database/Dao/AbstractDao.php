<?php 

    namespace Core\Database\Dao;

    use Core\Database\Connection;
    use Core\Database\Entities\AbstractEntity;
    use Core\Database\EntityManager;
    use Core\Database\Services\LoggerService;
    use Exception;
    use Monolog\Level;
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
        protected LoggerService $logger;

        public function __construct()
        {
            $this->connection = Connection::getConnection();
            $this->entityManager = new EntityManager();
            $this->logger = new LoggerService();
        }

        /**
         * Summary of findAll
         * @param array $options
         * @throws Exception
         * @return T[]|null <- array de entidades do tipo T (ex: UserEntity[])
         */
        public function findAll(array $options = []): ?array {
            try{
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

                $this->logAction(
                    "findAll executado",
                    Level::Debug,
                    ['table' => $this->table, 'fields' => $fields]
                );

                return $this->entityManager->mapToEntity($this->entity, $data);
            }catch(Exception $e){
                $this->logAction("Erro no findAll", Level::Error, [
                    'table' => $this->table,
                    'error' => $e->getMessage()
                ]);

                throw $e;
            } catch(\Exception $e){
                $this->logAction("Erro inesperado no findAll", Level::Warning, ['table' => $this->table,'error' => $e->getMessage()]);
                throw $e;
            }

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
            try{
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
                    $this->logAction(
                        "Registro não encontrado",
                        Level::Notice,
                        ['table' => $this->table, 'field' => $field, 'value' => $value]
                    );
                    return null;
                }

                $this->logAction("findBy executado", Level::Debug, ['table' => $this->table, 'field' => $field]);

                $entity = $this->entityManager->mapToEntity($this->entity, $data);
                $this->entityManager->snapshotEntityManager->takeSnapshot($entity);
                return $entity;
            }catch(Exception $e){
                $this->logAction("Erro no findBy", Level::Error, [
                    'table' => $this->table,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            } catch(\Exception $e){
                $this->logAction("Erro inesperado no findBy", Level::Warning, ['table' => $this->table,'error' => $e->getMessage()]);
                throw $e;
            }
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

                $this->logAction("Novo registro inserido em {$this->table}. ID: {$lastInsertedId}", Level::Info, $data);
                return $this->findById($lastInsertedId);
            }catch (\PDOException $e){
                $this->logAction("ERRO NO BANCO AO INSERIR", Level::Error, ['table' => $this->table, 'error' => $e->getMessage()]);
                throw $e;
            } catch(\Exception $e){
                $this->logAction("Erro inesperado no insert", Level::Warning, ['table' => $this->table, 'error' => $e->getMessage()]);
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
                        Level::Notice,
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
                $this->logAction("ERRO NO BANCO AO ATUALIZAR", Level::Error, ['table' => $this->table, 'id' => $entity->id, 'error' => $e->getMessage()]);
                throw $e;
            } catch(\Exception $e){
                $this->logAction("Erro inesperado no update", Level::Warning, ['table' => $this->table,'id' => $entity->id,'error' => $e->getMessage()]);
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
                    $this->logAction("Usuário deletado em {$this->table}", Level::Warning, ['id_deletado' => $entity->id]);
                }else{
                    $this->logAction("Nenhum registro encontrado para deletar", Level::Warning, ['table' => $this->table, 'id' => $entity->id]);
                }

                return $rowCount;
            }catch(\PDOException $e){
                $this->logAction("ERRO DE BANCO AO DELETAR", Level::Error, [ 'table' => $this->table, 'id' => $entity->id, 'error' => $e->getMessage()]);
                throw $e;
            } catch(\Exception $e){
                $this->logAction("Erro inesperado no delete", Level::Warning, ['table' => $this->table,'id' => $entity->id,'error' => $e->getMessage()]);
                throw $e;
            }
        }

        protected function logAction(string $message, Level $level = Level::Warning, array $context = []): void{
            $this->logger->log($message, $level, $context);
        }

    }




?>