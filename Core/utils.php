<?php

use Core\App;

/**
 * Verifica a existência de um arquivo sem Case-sensitive
 *
 * @param $fileName
 * @return bool|string Retorna o nome do arquivo conforme encontrado ou false caso não encontre o arquivo.
 */
function file_exists_case($fileName)
{
    if (file_exists($fileName)) {
        return $fileName;
    }
    $directoryName = dirname($fileName);
    $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
    $fileNameLowerCase = strtolower($fileName);
    foreach ($fileArray as $file) {
        if (strtolower($file) == $fileNameLowerCase) {
            return $file;
        }
    }
    return false;
}

function delTree($dir)
{
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

function fullCopy($source, $target)
{
    if (is_dir($source)) {
        @mkdir($target);
        $d = dir($source);
        while (FALSE !== ($entry = $d->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            $Entry = $source . '/' . $entry;
            if (is_dir($Entry)) {
                fullCopy($Entry, $target . '/' . $entry);
                continue;
            }
            copy($Entry, $target . '/' . $entry);
        }

        $d->close();
    } else {
        copy($source, $target);
    }
}

/**
 * Faz um dump da variável e sai do script.
 *
 * @param $_var
 */
function dd($_var)
{
    echo 'Dumped<pre>';
    print_r($_var);
    echo '</pre>';
    exit;
}

function d($_var)
{
    echo 'Dumped<pre>';
    print_r($_var);
    echo '</pre>';
}

function anchor($_url, $_text, $_title = null, $_class = null)
{
    return "<a href=\"$_url\"" . ($_title ? " title=\"$_title\"" : '') . ($_class ? " class=\"$_class\"" : '') . ">$_text</a>";
}

/**
 * Converte uma string em propriedades.
 * Ex.
 *      Entrada:
 *          min-length:5|max-length:10|style:font-size>15px
 *
 *      Saída:
 *           return [
 *              'min-length' => 5,
 *              'max-length' => 10,
 *              'style' => 'font-size: 15px'
 *          ];
 *
 * @param $_string
 * @return array
 */


function decamelize($string)
{
    return strtolower(preg_replace(['/([a-z\d])([A-Z\d])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
}

function camelize(string $string): string
{
    return lcfirst(str_replace(' ', '', ucwords(preg_replace('/[^a-zA-Z0-9\x7f-\xff]++/', ' ', $string))));
}

function redirect($_url)
{
    header("location: $_url");
    exit;
}

function status($_code = 404, $_exit = true)
{
    $codes = [
        401 => 'Unauthorized',
        404 => 'Not Found',
        501 => 'Not Implemented',
        503 => 'Service Unavailable'
    ];
    if (isset($codes[$_code])) {
        header("HTTP/1.1 {$_code} {$codes[$_code]}");
        $_exit && exit;
    }
    throw new Exception("Código não existe: {$_code}");
}

function redirectHere()
{
    header("location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

function GET($_name, $_default = null)
{
    return isset($_GET[$_name]) ? $_GET[$_name] : ($_default ? $_default : '');
}

function POST($_name, $_default = null)
{
    return isset($_POST[$_name]) ? $_POST[$_name] : ($_default ? $_default : '');
}

function isProduction()
{
    return config_item('ENV') == PRO;
}

function isDevelopment()
{
    return config_item('ENV') == DEV;
}

function url($_add = null, $_text = null, $_title = null, $_class = null)
{
    return App::getUrl($_add, $_text, $_title, $_class);
}
function public_url($_add = null, $_text = null, $_title = null, $_class = null)
{
    return App::getPublicUrl($_add, $_text, $_title, $_class);
}


function size_convert($size)
{
    if ($size === 0) return '0 bytes';
    $unit = array('bytes', 'KB', 'MM', 'GB', 'TB', 'PB');
    return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
}



function rif($_v1, $_condition, $_v2, $_true, $_false = '')
{
    switch ($_condition) {
        case '==':
            return $_v1 == $_v2 ? $_true : $_false;
        case '!=':
            return $_v1 != $_v2 ? $_true : $_false;
        case '>=':
            return $_v1 >= $_v2 ? $_true : $_false;
        case '<=':
            return $_v1 <= $_v2 ? $_true : $_false;
        case '>':
            return $_v1 > $_v2 ? $_true : $_false;
        case '<':
            return $_v1 < $_v2 ? $_true : $_false;
        case 'in':
            return in_array($_v1, explode(";", $_v2)) ? $_true : $_false;
    }
}
