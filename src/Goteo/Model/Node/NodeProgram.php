<?php

/*
* Model for Node program
*/

namespace Goteo\Model\Node;

use Goteo\Application\Exception\ModelNotFoundException;
use Goteo\Application\Lang;
use Goteo\Application\Config;
use Goteo\Model\Image;



class NodeProgram extends \Goteo\Core\Model {

    protected $Table = 'node_program';
    protected static $Table_static = 'node_program';
    public
    $id,
    $node_id,
    $title,
    $description,
    $header,
    $icon,
    $action,
    $action_url,
    $date,
    $order;

    public static function getLangFields() {
        return ['title', 'description', 'action', 'action_url'];
    }


    /**
     * Get data about node program
     *
     * @param   int    $id         check id.
     * @return  NodeProgram object
     */
    static public function get($id) {

        if(!$lang) $lang = Lang::current();
        list($fields, $joins) = self::getLangsSQLJoins($lang, Config::get('sql_lang'));

        $sql="SELECT
                    node_program.id as id,
                    node_program.node_id as node_id,
                    $fields,
                    node_program.header as `header`,
                    node_program.icon as `icon`,
                    node_program.date as `date`,
                    node_program.order as `order`
              FROM node_program
              $joins
              WHERE node_program.node_id = ?
              ORDER BY node_program.date ASC";
        // die(\sqldbg($sql, array($id)));
        $query = static::query($sql, array($id));
        $item = $query->fetchAll(\PDO::FETCH_CLASS, __CLASS__);

        if(!$item) {
            throw new ModelNotFoundException("Node program not found for ID [$id]");
        }
        
        return $item;
    }

   
    /**
     * Save.
     *
     * @param   type array  $errors
     * @return  type bool   true|false
     */
    public function save(&$errors = array()) {

        if (!$this->validate($errors))
            return false;

        $fields = array(
            'id',
            'node_id',
            'title',
            `header`,
            'icon',
            'description',
            'action',
            'action_url',
            'action_icon',
            'order'
        );

        try {
            //automatic $this->id assignation
            $this->dbInsertUpdate($fields);

            return true;
        } catch(\PDOException $e) {
            $errors[] = "Node program save error: " . $e->getMessage();
            return false;
        }
    }

    public function getHeader() {
        if(!$this->imageInstance instanceOf Image) {
            $this->imageInstance = new Image($this->header);
        }
        return $this->imageInstance;
    }

    /**
     * Validate.
     *
     * @param   type array  $errors     Errores devueltos pasados por referencia.
     * @return  type bool   true|false
     */
    public function validate(&$errors = array()) {
        return empty($errors);
    }


}

