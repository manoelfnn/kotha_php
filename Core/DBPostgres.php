<?php

namespace Core;

class DBPostgres
{
    private static $connection = null;

    public static function getConnection()
    {
        if (self::$connection == null) {
            self::$connection = pg_connect("host=".config_item('HOST', 'DB')." port=".config_item('PORT', 'DB')." dbname=".config_item('BASENAME', 'DB')." user=".config_item('USER', 'DB')." password=".config_item('PASSWORD', 'DB'));
            if (!self::$connection) {
                ErrorHandler::display("Não foi possível conectar ao servidor de banco de dados");
            }
        }
        return self::$connection;
    }

    public static function close()
    {
        pg_close(self::getConnection());
    }

    public static function escape($_str)
    {
        return pg_escape_string(self::getConnection(), $_str);
    }

    public static function executeQuery($_query)
    {
        $query = pg_query(self::getConnection(), $_query);
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
        return pg_affected_rows(self::getConnection());
    }

    public static function getNumRows($_result)
    {
        return pg_num_rows($_result);
    }

    public static function getRow($_result)
    {
        return pg_fetch_assoc($_result);
    }

    public static function getLastInsertId()
    {
        return pg_last_oid(self::getConnection());
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
