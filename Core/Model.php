<?php

namespace Core;

use Exception;
use ReflectionClass;

class Model
{

    private $fields = [];

    private $tableNameThis = '';
    private $idColumnName = "id";

    private $selectColumns = ['*'];
    private $modelNoFields = [];
    private $found = false;
    private $modifiedFields = [];
    private $observeChanges = true;

    private $readonly = false;

    public function __construct($_where = null, $_values = null, $_readonly = false)
    {
        $this->tableNameThis = self::getTableName();
        $this->readonly = $_readonly;

        if ($_where != null) {
            if (is_numeric($_where))
                $_where = "{$this->idColumnName} = " . intval($_where);

            $row = self::queryBuilder("SELECT {$this->getSelectColumns()} FROM {$this->tableNameThis} WHERE $_where LIMIT 1", $_values);

            if (DB::getNumRows($row)) {
                $this->found = true;
                $this->setModelFields(DB::getRow($row));
            }
        }
    }

    public function exist()
    {
        return $this->found;
    }

    public function isReadonly()
    {
        return $this->readonly;
    }

    protected function setFound($_found)
    {
        $this->found = $_found;
    }

    public static function getAffectedRows()
    {
        return DB::getAffectedRows();
    }

    private function getSelectColumns()
    {
        return implode(",", $this->selectColumns);
    }

    public function setSelectColumns($_columns)
    {
        $this->selectColumns = $_columns;
    }

    public function addNoFields($_name)
    {
        $this->modelNoFields[] = $_name;
    }

    public function action($_action)
    {
        if ($_action == 'delete') {
            return $this->delete();
        }

        if ($_action == 'clone') {
            return $this->clone();
        }
    }

    public function delete()
    {
        if ($this->readonly) {
            throw new Exception('Readonly');
        }

        method_exists($this, 'deleting') && $this->deleting();

        $id = $this->idColumnName;
        if (isset($this->fields[$id])) {
            $r = $this->query("DELETE FROM {$this->tableNameThis} WHERE {$this->idColumnName} = {$this->fields[$id]} LIMIT 1");

            method_exists($this, 'deleted') && $this->deleted();

            return $r;
        }
        return false;
    }

    public function clone()
    {
        if ($this->readonly) {
            throw new Exception('Readonly');
        }

        $id = $this->idColumnName;
        if (isset($this->fields[$id])) {
            $clone = clone $this;

            method_exists($this, 'cloning') && $this->cloning();

            $clone->setId(null);
            if ($clone->save()) {

                method_exists($this, 'cloned') && $this->cloned();

                return $clone;
            }
        }
        return null;
    }

    public function save()
    {

        if ($this->readonly) {
            throw new Exception('Readonly');
        }

        method_exists($this, 'saving') && $this->saving();

        $id = $this->idColumnName;

        if (isset($this->fields[$id]) && ($this->fields[$id] != 0)) {

            method_exists($this, 'updating') && $this->updating();

            $updates = [];
            $genUpdatedAt = self::getTableAttribute('updatedAt');
            if ($genUpdatedAt === true) {
                $this->setUpdatedAt(date("Y-m-d H:i:s"));
            }

            foreach ($this->fields as $column => $value) {
                if (!in_array($column, $this->modelNoFields) && ($column != $id) && (in_array($column, $this->modifiedFields))) {
                    $updates[] = " `" . $column . "`=" . (is_null($value) ? "NULL" : "'" . DB::escape($value) . "'");
                }
            }
            if (count($updates)) {
                //     echo("UPDATE " . $this->tableNameThis . " SET " . implode(",", $updates) . " WHERE " . $id . " = " . $this->fields[$id]);
                $this->query("UPDATE " . $this->tableNameThis . " SET " . implode(",", $updates) . " WHERE " . $id . " = " . $this->fields[$id]);
                $this->modifiedFields = [];

                method_exists($this, 'updated') && $this->updated();
            }

            method_exists($this, 'saved') && $this->saved();

            return true;
        } else {

            method_exists($this, 'creating') && $this->creating();

            $vars = get_object_vars($this);
            $inserts_columns = [];
            $inserts_values = [];
            $genCreatedAt = self::getTableAttribute('createdAt');
            if ($genCreatedAt === true) {
                $this->setCreatedAt(date("Y-m-d H:i:s"));
            }
            foreach ($this->fields as $column => $value) {
                if (!in_array($column, $this->modelNoFields) && ($column != $id) && (substr($column, 0, 2) != '__')) {
                    $inserts_columns[] = " `" . $column . "`";
                    $inserts_values[] = (is_null($value) ? "NULL" : "'" . DB::escape($value) . "'");
                }
            }
            if (count($inserts_columns)) {
                // dd("INSERT INTO " . $this->tableNameThis . " (" . implode(",", $inserts_columns) . ") VALUES (" . implode(",", $inserts_values) . ")");
                $this->query("INSERT INTO " . $this->tableNameThis . " (" . implode(",", $inserts_columns) . ") VALUES (" . implode(",", $inserts_values) . ")");
                $this->fields[$id] = DB::getLastInsertId();
                if ($this->fields[$id]) {
                    $this->modifiedFields = [];

                    method_exists($this, 'created') && $this->created();

                    method_exists($this, 'saved') && $this->saved();

                    return true;
                }
            }
        }

        method_exists($this, 'failed') && $this->failed();

        return false;
    }

    public function observeChanges($_state)
    {
        $this->observeChanges = $_state;
    }

    public function setModelFields($_fields)
    {
        $this->fields = $_fields;
    }

    public function mergeModelFields($_fields)
    {
        $this->fields = array_merge($this->fields, $_fields);
    }

    public function getModelFields()
    {
        return $this->fields;
    }

    public function getModelFieldByName($_name)
    {
        if (array_key_exists($_name, $this->fields)) {
            return $this->fields[$_name];
        }
        return null;
    }

    public function removeModelField($_names)
    {
        $_names = is_array($_names) ? $_names : [$_names];
        foreach ($_names as $name) {
            if (array_key_exists($name, $this->fields)) {
                unset($this->fields[$name]);
            }
            if (in_array($name, $this->modifiedFields)) {
                unset($this->modifiedFields[array_search($name, $this->modifiedFields)]);
            }
        }
    }

    public function __call($_name, $_value)
    {
        $mode = substr($_name, 0, 3);
        $field = decamelize(substr($_name, 3, strlen($_name)));
        if ($mode == 'set' || $mode == 'inc' || $mode == 'dec' || $mode == 'add') {
            $value = isset($_value[0]) ? $_value[0] : null;
            //echo $this->fields[$field] ."!==". $value."<br>";
            // if ($this->observeChanges && array_key_exists($field, $this->fields) && $this->fields[$field] !== $value) {
            //     $this->modifiedFields[] = $field;
            // }

            if (
                $this->observeChanges &&
                ((array_key_exists($field, $this->fields) && $this->fields[$field] !== $value) ||
                    (!array_key_exists($field, $this->fields)))
            ) {
                $this->modifiedFields[] = $field;
            }

            if (in_array($mode, ['inc', 'dec', 'add']) && !array_key_exists($field, $this->fields)) {
                throw new Exception("Não é possível executar um '" . $mode . "' em '" . camelize($mode . '_' . $field) . "()' porque o campo '" . $field . "' não existe!");
            }

            if ($mode == 'set') {
                $this->fields[$field] = $value;
            } elseif ($mode == 'inc') {
                $this->fields[$field]++;
            } elseif ($mode == 'dec') {
                $this->fields[$field]--;
            } elseif ($mode == 'add') {
                $this->fields[$field] .= $value;
            }
        } elseif ($mode == 'get') {
            if (array_key_exists($field, $this->fields)) {
                return $this->fields[$field];
            }
            throw new Exception("Método '" . camelize('get_' . $field) . "()' não existe em '" . (new ReflectionClass(get_called_class()))->getShortName() . "'!");
        } else {
            throw new Exception("Método '" . camelize($_name) . "()' não existe em '" . (new ReflectionClass(get_called_class()))->getShortName() . "'!");
        }
    }

    public function fieldExist($_field)
    {
        return array_key_exists($_field, $this->fields);
    }

    public function __set($_name, $_value)
    {
        throw new Exception("Atribuição somente através do método '" . camelize('set_' . $_name) . "()'");
    }

    public function __get($_name)
    {
        throw new Exception("Acesso somente através do método '" . camelize('get_' . $_name) . "()'");
    }



    // public function __call($_name, $_value)
    // {
    //     if (substr($_name, 0, 6) == "getOne") {
    //         $modelName = substr($_name, 6, strlen($_name)) . "Model";
    //         $findModel = new $modelName();
    //         $idColumnName = $this->idColumnName;
    //         return $findModel->getByWhere($this->tableNameThis . "_" . $this->idColumnName . "=" . $this->$idColumnName . (isset($_value[0]) ? " " . $_value[0] : ""));
    //     } elseif (substr($_name, 0, 3) == "get") {
    //         $modelName = substr($_name, 3, strlen($_name)) . "Model";
    //         $findModel = new $modelName();
    //         $idColumnName = $this->idColumnName;
    //         return $findModel->getAllByWhere($this->tableNameThis . "_" . $this->idColumnName . "=" . $this->$idColumnName . (isset($_value[0]) ? " " . $_value[0] : ""));
    //     } else {
    //         $modelName = $_name . "Model";
    //         if (class_exists($modelName)) {
    //             $findModel = new $modelName();
    //             $idColumnName = $findModel->tableNameThis . "_" . $findModel->idColumnName;
    //             if ($this->idColumnName && ($this->$idColumnName != null)) {
    //                 $findModel->getByWhere($findModel->idColumnName . "=" . $this->$idColumnName . (isset($_value[0]) ? " " . $_value[0] : ""));
    //                 return $findModel;
    //             }
    //             return null;
    //         }
    //     }
    // }


    public static function getTableName()
    {
        $classReflect = new ReflectionClass(get_called_class());
        if ($tableName = $classReflect->getStaticPropertyValue('__tableName', null)) {
            return $tableName;
        }
        return decamelize($classReflect->getShortName());
    }

    public static function getTableAttribute($_name)
    {
        $classReflect = new ReflectionClass(get_called_class());
        return $classReflect->getStaticPropertyValue('__' . $_name, null);;
    }

    private static function query($_sql)
    {
        // echo $_sql . "<br><br>";
        $query = DB::executeQuery($_sql);
        return $query;
    }

    private static function queryBuilder($_query, $_values = null)
    {
        if (null == $_values) {
            $_values = '';
        }

        if (is_array($_values)) {
            foreach ($_values as $value) {
                $_query = preg_replace("#\?#", "'" . DB::escape(str_replace('?', ' ', $value)) . "'", $_query, 1);
            }
        } else {
            $_query = preg_replace("#\?#", "'" . DB::escape(str_replace('?', ' ', $_values)) . "'", $_query, 1);
        }
        $query = self::query($_query);

        return $query;
    }

    private static function rowsToObjects($_rows, $_readonly = false)
    {
        $return = [];
        $modelClass = get_called_class();
        if (DB::getNumRows($_rows)) {
            while ($row = DB::getRow($_rows)) {
                $obj = new $modelClass(null, null, $_readonly);
                $obj->setModelFields($row);
                $return[] = $obj;
            }
        }
        return $return;
    }

    public static function getByQuery($_query, $_values = null, $_readonly = false)
    {
        $row = self::queryBuilder($_query, $_values);
        if (DB::getNumRows($row)) {
            $modelClass = get_called_class();
            $model = new $modelClass(null, null, $_readonly);
            $model->setFound(true);
            $model->setModelFields(DB::getRow($row));
            return $model;
        }
        return null;
    }

    public static function getByWhere($_where, $_values = null, $_readonly = false)
    {
        $tableName = self::getTableName();
        $row = self::queryBuilder("SELECT * FROM {$tableName} WHERE $_where", $_values);
        if (DB::getNumRows($row)) {
            $modelClass = get_called_class();
            $model = new $modelClass(null, null, $_readonly);
            $model->setFound(true);
            $model->setModelFields(DB::getRow($row));
            return $model;
        }
        return null;
    }

    public static function getAllByQuery($_query, $_values = null, $_readonly = false)
    {
        $rows = self::queryBuilder($_query, $_values);
        return self::rowsToObjects($rows, $_readonly);
    }

    public static function getAllByWhere($_where = null, $_values = null, $_readonly = false)
    {
        $tableName = self::getTableName();
        $rows = self::queryBuilder("SELECT * FROM {$tableName} " . ($_where ? "WHERE {$_where}" : ''), $_values);
        return self::rowsToObjects($rows, $_readonly);
    }

    public static function getAll($_readonly = false)
    {
        return self::getAllByWhere(null, $_readonly);
    }

    public static function getCount($_where = null, $_values = null)
    {
        $tableName = self::getTableName();
        $row = DB::getRow(self::queryBuilder("SELECT COUNT(id) AS total FROM {$tableName} " . ($_where ? "WHERE {$_where}" : ''), $_values));
        return $row['total'];
    }

    public static function deleteAllByWhere($_where, $_values = null)
    {
        $tableName = self::getTableName();
        $rows = self::queryBuilder("DELETE FROM {$tableName} WHERE $_where", $_values);
        return self::getAffectedRows();
    }
}
