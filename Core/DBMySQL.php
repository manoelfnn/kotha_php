<?php

namespace Core;

class DBMySQL
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection == null) {
            self::$connection = mysqli_connect(config_item('HOST', 'DB'), config_item('USER', 'DB'), config_item('PASSWORD', 'DB'), config_item('BASENAME', 'DB'));
            mysqli_set_charset(self::$connection, config_item('CHARSET', 'DB'));
            if (!self::$connection) {
                ErrorHandler::display("Não foi possível conectar ao servidor de banco de dados");
            }
        }
        return self::$connection;
    }

    public static function close()
    {
        mysqli_close(self::getConnection());
    }

    public static function escape($_str)
    {
        return mysqli_real_escape_string(self::getConnection(), $_str);
    }

    public static function executeQuery($_query)
    {
        $query = mysqli_query(self::getConnection(), $_query);
        if ($query === false) {
            if (isProduction()) {
                error_log("\n" . mysqli_error(self::getConnection()) . "\nQuery: " . $_query . "\n", 0);
                header("HTTP/1.0 500 Internal Server Error");
            } else {
                error_log("\n" . mysqli_error(self::getConnection()) . "\nQuery: " . $_query . "\n", 0);
                die(mysqli_error(self::getConnection()));
            }
            return false;
        }
        return $query;
    }

    public static function getAffectedRows()
    {
        return mysqli_affected_rows(self::getConnection());
    }

    public static function getNumRows($_result)
    {
        return mysqli_num_rows($_result);
    }

    public static function getRow($_result)
    {
        return mysqli_fetch_assoc($_result);
    }

    public static function getLastInsertId()
    {
        return mysqli_insert_id(self::getConnection());
    }

    public static function autoCommit($_mode)
    {
        mysqli_autocommit(self::getConnection(), $_mode);
    }

    public static function beginTransaction()
    {
        mysqli_begin_transaction(self::getConnection());
    }

    public static function commit()
    {
        mysqli_commit(self::getConnection());
    }

    public static function rollBack()
    {
        mysqli_rollback(self::getConnection());
    }
}
