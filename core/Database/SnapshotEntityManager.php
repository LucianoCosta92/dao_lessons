<?php 

    namespace Core\Database;
    use Core\Database\Entities\AbstractEntity;
    use ReflectionClass;

    class SnapshotEntityManager
    {
        protected array $snapshot = [];

        public function takeSnapshot(AbstractEntity $entity){
            $reflectionClass = new ReflectionClass($entity);
            $properties = $reflectionClass->getProperties();
            
            foreach ($properties as $property) {
                if(!$property->isInitialized($entity)){
                    continue;
                }
                $name = $property->getName();
                $value = $property->getValue($entity);
                $this->snapshot[$name] = $value;
            }
        }

        public function propertiesChanged(AbstractEntity $entity){
            $propertiesChanged = [];

            $reflection = new ReflectionClass($entity);

            foreach ($this->snapshot as $property => $oldValue) {
                if($property == 'id'){
                    continue;
                }

                if(!$reflection->hasProperty($property)){
                    continue;
                }

                $rp = $reflection->getProperty($property);

                if(!$rp->isInitialized($entity)){
                    continue;
                }

                $newValue = $rp->getValue($entity);

                if($oldValue !== $newValue){
                    $propertiesChanged[$property] = $newValue;
                }
            }
            return $propertiesChanged;
        }

        public function clearSnapshot(){
            $this->snapshot = [];
        }

        public function getSnapshot(){
            return $this->snapshot;
        }

        public function snapshotTaken(): bool{
            return !empty($this->snapshot);
        }
    }



?>