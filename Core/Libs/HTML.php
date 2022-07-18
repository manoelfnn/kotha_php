<?php
namespace Core\Libs;

class HTML {

	public static function props2HtmlAttr($_props, $_start = '_')
    {
        $r = '';
        $len = strlen($_start);
        foreach ($_props as $name => $value) {

            if (substr($name, 0, $len) == $_start) {
                $r .= ' ' . substr($name, $len) . '="' . $value . '"';
                continue;
            }
        }
        return $r;
    }
}