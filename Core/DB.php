<?php

namespace Core;

class DB
{
    private static $connection = null;
    private static $engine = null;


    public static function setEngine()
    {

        if (config_item('ENGINE', 'DB') == 'mysql') {
            self::$engine = DBMySQL::class;
        }

        if (config_item('ENGINE', 'DB') == 'postgres') {
            self::$engine = DBPostgres::class;
        }
    }

    public static function close()
    {
        self::$engine::close();
    }

    public static function escape($_str)
    {
        return self::$engine::escape($_str);
    }

    public static function executeQuery($_query)
    {
        return self::$engine::executeQuery($_query);
    }

    public static function getAffectedRows()
    {
        return self::$engine::getAffectedRows();
    }

    public static function getNumRows($_result)
    {
        return self::$engine::getNumRows($_result);
    }

    public static function getRow($_result)
    {
        return self::$engine::getRow($_result);
    }

    public static function getLastInsertId()
    {
        return self::$engine::getLastInsertId();
    }

    public static function autoCommit($_mode)
    {
        return self::$engine::autoCommit($_mode);
    }

    public static function beginTransaction()
    {
        return self::$engine::beginTransaction();
    }

    public static function commit()
    {
        return self::$engine::commit();
    }

    public static function rollBack()
    {
        return self::$engine::rollBack();
    }
}
