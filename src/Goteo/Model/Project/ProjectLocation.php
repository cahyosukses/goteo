<?php

namespace Goteo\Model\Project {

    use Goteo\Model\Location;

    class ProjectLocation extends \Goteo\Core\Model {
        protected $Table = 'location_item';
        public
            $location,
            $locations = array(), //array of addresses
            $method, // latitude,longitude obtaining method
                     // ip      = auto detection from ip,
                     // browser = project automatic provided,
                     // manual    = project manually provided
            $locable = true, //if is or not locable
            $info, //Some stored info
            $project;

        /**
         * Recupera la geolocalización de este
         * @param varcahr(50) $id  project identifier
         * @return int (id geolocation)
         */
	 	public static function get ($id) {

            $query = static::query("SELECT * FROM location_item WHERE type = 'project' AND item = ?", array($id));
            if($ob = $query->fetchObject()) {
                if(!($loc = Location::get($ob->location))) {
                    //location non exists
                    $loc = new Location();
                }
                $loc = new ProjectLocation(array(
                    'location' => (int) $ob->location,
                    'locations' => array($loc),
                    'project' => $id,
                    'method' => $ob->method,
                    'info' => $ob->info,
                    'locable' => (bool) $ob->locable
                ));
            }
            return $loc ? $loc : false;
		}

		public function validate(&$errors = array()) {
            if (empty($this->location)) {
                $errors[] = 'Location ID missing!';
                return false;
            }
            if (empty($this->project)) {
                $errors[] = 'Project ID missing!';
                return false;
            }
            $methods = array('ip', 'browser', 'manual');
            if (!in_array($this->method, $methods)) {
                $errors[] = 'Method (' . $this->method . ') error! must be one of: ' . implode(', ', $methods);
                return false;
            }
            return true;
        }

		/*
		 *  Guarda la asignación del usuario a la localización
		 */
		public function save (&$errors = array()) {
            if (!$this->validate($errors)) {
                return false;
            }

            // remove from unlocable if method is not IP
            if($this->method !== 'ip') $this->locable = true;

            $values = array(':item'     => $this->project,
                            ':location' => $this->location,
                            ':method'   => $this->method,
                            ':locable'  => $this->locable,
                            ':info'     => $this->info,
                            ':type'     => 'project'
                            );

            try {
                $sql = "REPLACE INTO location_item (location, item, type, method, locable, info) VALUES (:location, :item, :type, :method, :locable, :info)";
                self::query($sql, $values);
			} catch(\PDOException $e) {
				$errors[] = "No se ha podido asignar. Por favor, revise los datos." . $e->getMessage();
				return false;
			}
            return true;
		}

		/**
		 * Desasignar el usuario de su localización
		 *
		 * @param varchar(50) $project id de un usuario
		 * @param array $errors
		 * @return boolean
		 */
		public function delete (&$errors = array()) {
            $project = $this->project;
            $values = array(':item'=>$project, ':type'=>'project');

            try {
                self::query("DELETE FROM location_item WHERE type = :type AND item = :item", $values);
            } catch(\PDOException $e) {
                $errors[] = 'No se ha podido quitar la geolocalización del usuario ' . $project . '.<br />' . $e->getMessage();
                return false;
            }
			return true;
		}

        /**
         * Adds a location to the corresponding location/location_item tables according to the project
         * @param [type] $data    [description]
         * @param array  &$errors [description]
         * @return instance of Model\Project\ProjectLocation if successfull, false otherwise
         */
        public static function addProjectLocation($data, &$errors = array()) {
            try {
                $location = new Location($data);
                if($location->save($errors)) {
                    $project_loc = new ProjectLocation(array(
                        'location' => $location->id,
                        'project' => $data['project'],
                        'method' => $data['method'],
                        'locable' => !self::isUnlocable($data['project'])
                    ));
                    if($project_loc->save($errors)) {
                        $project_loc->locations[] = $location;
                        return $project_loc;
                    }
                    if(empty($errors)) $errors[] = 'unknow error';
                }
            } catch(\PDOException $e) {
                $errors[] = "Fallo SQL ".$e->getMessage();
                return false;
            }
            return false;
        }

        /**
         * Sets a property
         * @param [type] $project    [description]
         * @param [type] $prop    [description]
         * @param [type] $value   [description]
         * @param [type] &$errors [description]
         */
        public static function setProperty($project, $prop, $value, &$errors) {
            try {
                if(self::query("INSERT INTO location_item ($prop, type, item) VALUES (:value, 'project', :project)
                                ON DUPLICATE KEY UPDATE $prop = :value", array(':value' => $value, ':project' => $project)));
                    return true;
            } catch(\PDOException $e) {
                $errors[] = 'Error modifying [' . $prop . '] with val [' . $value . '] ' . $e->getMessage();
            }
            return false;

        }


        /**
         * Borrar de unlocable
         *
         * @param varchar(50) $project id de un usuario
         * @param array $errors
         * @return boolean
         */
        public static function setLocable ($project, &$errors = array()) {
            return self::setProperty($project, 'locable', 1, $errors);
        }

        /**
         * Añadir a unlocable
         *
         * @param varchar(50) $project id de un usuario
         * @param array $errors
         * @return boolean
         */
        public static function setUnlocable ($project, &$errors = array()) {
            return self::setProperty($project, 'locable', 0, $errors);
		}


        /**
         * Si está como ilocalizable
         * @param varcahr(50) $id  project identifier
         * @return int (have an unlocable register)
         */
	 	public static function isUnlocable ($project) {

            try {
                $query = self::query("SELECT locable FROM location_item WHERE type = 'project' AND item = ?", array($project));
                return !(bool) $query->fetchColumn();
            } catch(\PDOException $e) {
                return true;
            }
		}

	}

}