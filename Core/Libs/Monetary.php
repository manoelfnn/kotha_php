<?php
namespace Core\Libs;

abstract class Monetary
{

    /**
     * Converte um valor do formato 999999.99 para o formato 999.999,99.
     *
     * @param string $value
     * @param boolean $tags
     *            Inclui tags html sub para o símbolo e sup para os centavos.
     * @return string
     */
    public static function format($value, $tags = false)
    {
        $value = number_format($value, 2, ',', '.');
        return $tags ? "<sub>R$</sub>" . substr($value, 0, -3) . "<sup>," . substr($value, -2, 2) . "</sup>" : "R$ " . $value;
    }

    public static function clean($price)
    {
        if (!$price) return 0;
        $price = str_replace("R$", "", $price);
        $price = str_replace("$", "", $price);
        $price = str_replace(".", "", $price);
        $price = str_replace(",", ".", $price);
        return trim($price);
    }

    // convert xxxxxxxx EM xxxxxx.xx
    public static function intToDecimal($int)
    {
        if ($int) {
            $price_l = substr($int, 0, -2);
            $price_r = substr($int, -2, 2);
            $int = $price_l . "." . $price_r;
            $int = number_format($int, 2, '.', '');
            return $int;
        }
        return "";
    }
}