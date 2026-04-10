<?php 

    namespace Core\Database;
    use Core\Database\Entities\AbstractEntity;

    /**
     * @template T of AbstractEntity
     */
    class EntityManager
    {
        public SnapshotEntityManager $snapshotEntityManager;

        public function __construct()
        {
            $this->snapshotEntityManager = new SnapshotEntityManager();
        }
        
        /**
         * /
         * @param class-string<T> $entity
         * @param array $data
         * @return T|T[]
         */
        public function mapToEntity(string $entity, array $data){
            if($this->isSingleArray($data)){
                return new $entity(function($entity) use ($data){
                    /** @var T $entity **/
                    $entity->normalizeArrayToEntity($data);
                });
            }

            return array_map(function ($data) use($entity) {
                return new $entity(function($entity) use($data){
                     /** @var T $entity **/
                    $entity->normalizeArrayToEntity($data);
                });
            }, $data);
        }

        /**
         * /
         * @param array $data
         * @return bool
         */
        public function isSingleArray(array $data): bool{
            return !isset($data[0]);
        }

        /**
         * /
         * @param T|array $arrayOrEntity
         * @return array
         */
        public function normalizeDataToArray(AbstractEntity|array $arrayOrEntity){
            if($arrayOrEntity instanceof AbstractEntity){
                return $arrayOrEntity->entityToArray();
            }
            if(isset($arrayOrEntity['password'])){
                $arrayOrEntity['password'] = password_hash($arrayOrEntity['password'], PASSWORD_BCRYPT);
            }
            if(isset($arrayOrEntity['firstName'])){
                $arrayOrEntity['firstName'] = ucfirst($arrayOrEntity['firstName']);
            }
            if(isset($arrayOrEntity['lastName'])){
                $arrayOrEntity['lastName'] = ucfirst($arrayOrEntity['lastName']);
            }
            return $arrayOrEntity;
        }
    }








?>