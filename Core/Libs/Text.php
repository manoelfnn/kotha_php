<?php

namespace Core\Libs;

use ArgumentCountError;
use Core\Model;
use Exception;

class Text
{

    public static function limiter($_str, $_max = 500, $_end_char = '...', $_preserve_words = TRUE)
    {
        $_max = (int)max(1, $_max);
        $end_char = ($_end_char === NULL) ? '& #8230;' : $_end_char;
        if (strlen($_str) <= $_max) {
            return $_str;
        }
        if (!$_preserve_words) {
            return rtrim(substr($_str, 0, $_max)) . $end_char;
        }
        $matches = null;
        preg_match('/^.{' . ($_max - 1) . '}(?:\s|.+?(?:\s|$))/sD', $_str, $matches);
        if (strlen($matches[0]) == strlen($_str)) {
            $end_char = '';
        }
        return rtrim($matches[0]) . $end_char;
    }

    private static function strtr_utf8($str, $from, $to)
    {
        $keys = array();
        $values = array();
        if (!is_array($from)) {
            preg_match_all('/./u', $from, $keys);
            preg_match_all('/./u', $to, $values);
            $mapping = array_combine($keys[0], $values[0]);
        } else
            $mapping = $from;
        return strtr($str, $mapping);
    }

    public static function slug($string, $slug = "-")
    {
        // $string = character_limiter($string, 100, " ", TRUE);
        $string = html_entity_decode($string);
        $string = mb_strtolower(trim($string));
        $string = (self::strtr_utf8($string, "ÀÁÂÃÄÅÆàáâãäåæÒÓÔÕÕÖØòóôõöøÈÉÊËèéêëðÇçÐÌÍÎÏìíîïÙÚÛÜùúûüÑñÞßÿý", "aaaaaaaaaaaaaaoooooooooooooeeeeeeeeecceiiiiiiiiuuuuuuuunntsyy"));
        $string = preg_replace('/[^a-z0-9]/i', $slug, $string);
        $string = str_replace("--", "-", $string);
        $string = preg_replace('/-{2,}/', '-', $string);
        return $string;
    }

    public static function dateToDBDate($date)
    {
        if (!$date) return "";
        return date("Y-m-d", strtotime(str_replace('/', '-', $date)));
    }

    public static function onlyNumbers($_str)
    {
        return preg_replace("/[^0-9]/", "", $_str);
    }

    public static function randomChars($_size = 40)
    {
        // return bin2hex(openssl_random_pseudo_bytes($_size));
        return substr(str_shuffle(str_repeat($x = "123456789abcdefghijklmnopqrstuvxzwyABCDEFGHIJKLMNOPQRSTUVXZWY", ceil($_size / strlen($x)))), 1, $_size);
    }

    public static function randomNumbers($_size = 8)
    {
        // return bin2hex(openssl_random_pseudo_bytes($_size));
        return substr(str_shuffle(str_repeat($x = "1234567890", ceil($_size / strlen($x)))), 1, $_size);
    }

    public static function firstWord($_str)
    {
        return explode(" ", $_str)[0];
    }

    public static function formatPhone($phone)
    {
        $phone = preg_replace('/[^0-9]+/', '', $phone); // Strip all non number characters
        if (strlen($phone) == 10) {
            return preg_replace("/([0-9]{2})([0-9]{4})([0-9]{4})/", "<span>($1)</span> $2-$3", $phone);
        } elseif (strlen($phone) == 11) {
            return preg_replace("/([0-9]{2})([0-9]{5})([0-9]{4})/", "<span>($1)</span> $2-$3", $phone);
        } else {
            return $phone;
        }
    }

    public static function formatZipcode($_zipcode)
    {
        $_zipcode = preg_replace('/[^0-9]+/', '', $_zipcode); // Strip all non number characters
        return preg_replace("/(\\d{5})(\\d{3})/", "$1-$2", $_zipcode);
    }

    public static function formatCPF($_cpf)
    {
        $_cpf = preg_replace('/[^0-9]+/', '', $_cpf); // Strip all non number characters
        return preg_replace("/(\\d{3})(\\d{3})(\\d{3})(\\d{2})/", "$1.$2.$3-$4", $_cpf);
    }

    public static function formatCNPJ($_cnpj)
    {
        $_cnpj = preg_replace('/[^0-9]+/', '', $_cnpj); // Strip all non number characters
        return preg_replace("/(\\d{2})(\\d{3})(\\d{3})(\\d{4})(\\d{2})/", "$1.$2.$3/$4-$5", $_cnpj);
    }

    public static function removeHtmlAttributes($_str)
    {
        return preg_replace('/<[\/]*(\w+)[^>]*>/', '<$1>', $_str);
    }

    public static function evaluteValue($_var, $_prop)
    {
        $_prop = trim($_prop);
        if ($_var instanceof Model) {
            if ($_var->fieldExist($_prop)) {
                return $_var->{camelize("get_$_prop")}();
            } elseif (method_exists($_var, $_prop)) {
                return $_var->$_prop();
            }
        } elseif (is_array($_var)) {
            return $_var[$_prop];
        }
        return $_prop;
    }

    public static function strCompile($_str, $_vars, $_functions = [])
    {
        return preg_replace_callback("|{([^}]*)}|", function ($m) use ($_vars, $_functions) {
            $str = $m[1];
            if ($str[0] == '@') {
                $params = explode(',', $str);
                $fnc = trim(substr($params[0], 1));
                array_shift($params);
                foreach ($params as $index => $value) {
                    $params[$index] = trim(self::evaluteValue($_vars, $value));
                }

                try {
                    if (isset($_functions[$fnc])) {
                        return $_functions[$fnc](...$params);
                    } else {
                        return call_user_func_array($fnc, $params);
                    }
                } catch (ArgumentCountError $e) {
                    return $e->getMessage();
                }
            }
            return self::evaluteValue($_vars, $str);
        }, $_str);
    }

    public static function str2Props($_string)
    {
        $r = [];
        $props = explode('|', $_string);
        foreach ($props as $prop) {
            $name = trim(strstr($prop, '=', true));
            if (!$name) continue;
            $value = substr($prop, strpos($prop, '=') + 1);
            $r[$name] = $value != '' ? $value : null;
        }
        return $r;
    }
}
