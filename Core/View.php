<?php

namespace Core;

abstract class View
{

    static private $titleDivider = '-';
    static private $template;
    static private $templateVars = [];
    static private $templateContent;
    static private $content;
    static private $heads = [];
    static private $js = [];
    static private $css = [];
    static private $scripts = [];
    static private $jsons = [];
    static private $headCodes = [];
    static private $beginCodes = [];
    static private $endCodes = [];
    static private $breadcrumbList = [];
    static private $htmlClass = null;
    static private $htmlLang = null;
    static private $headInfo = '';

    public static function setVar($_name, $_value)
    {
        self::$templateVars[$_name] = $_value;
    }

    public static function getVar($_name)
    {
        return isset(self::$templateVars[$_name]) ? self::$templateVars[$_name] : null;
    }

    public static function addBreadcrumb($_name, $_url)
    {
        self::$breadcrumbList[] = [
            "name" => $_name,
            "url" => $_url
        ];
    }

    public static function genBreadcrumbScript()
    {
        $elements = [];
        for ($i = 0; $i < count(self::$breadcrumbList); $i++) {
            $elements[] = [
                "@type" => "ListItem",
                "position" => $i + 1,
                "name" => self::$breadcrumbList[$i]["name"],
                "item" => self::$breadcrumbList[$i]["url"]
            ];
        }
        self::addJson([
            "@context" => "https://schema.org",
            "@type" => "BreadcrumbList",
            "itemListElement" => $elements
        ]);
    }

    public static function getBreadcrumbComponent()
    {
        if (!count(self::$breadcrumbList))
            return '';

        $elements = [];
        for ($i = 0; $i < count(self::$breadcrumbList); $i++) {
            $elements[] = '<li class="breadcrumb-item ' . ($i == count(self::$breadcrumbList) - 1 ? 'active' : '') . '"><a href="' . self::$breadcrumbList[$i]["url"] . '">' . self::$breadcrumbList[$i]['name'] . '</a></li>';
        }
        return TAB . '<ol class="breadcrumb">' . implode("", $elements) . '</ol>' . EOL;
    }

    public static function setTitle($_title)
    {
        self::setHeader('title', $_title);
    }

    public static function addTitle($_title)
    {
        $title = isset(self::$heads['title']) ? $_title . ' ' . (self::$titleDivider ? self::$titleDivider . ' ' : '') . self::$heads['title'] : $_title;
        self::setHeader('title', $title);
    }

    public static function getTitle()
    {
        return self::$heads['title'];
    }

    public static function setTitleDivider($_divider)
    {
        self::$titleDivider = $_divider;
    }

    public static function setLang($_lang)
    {
        self::$htmlLang = $_lang;
    }

    public static function setHTMLTagClass($_class)
    {
        self::$htmlClass = $_class;
    }

    public static function setHeadInfo($_headInfo)
    {
        self::$headInfo = $_headInfo;
    }

    public static function getTitleDivider()
    {
        return self::$titleDivider;
    }

    public static function setHeader($_name, $_value = null)
    {
        self::$heads[$_name] = $_value;
    }

    public static function addHeadCode($_code)
    {
        array_push(self::$headCodes, $_code);
    }

    public static function addBeginCode($_code)
    {
        array_push(self::$beginCodes, $_code);
    }

    public static function addEndCode($_code)
    {
        array_push(self::$endCodes, $_code);
    }

    public static function addScript($_script)
    {
        array_push(self::$scripts, $_script);
    }

    public static function addJson($_json)
    {
        array_push(self::$jsons, $_json);
    }

    public static function addJS($_js)
    {
        array_push(self::$js, $_js);
    }

    public static function addCSS($_css)
    {
        array_push(self::$css, $_css);
    }

    public static function removeHead($_name)
    {
        if (isset(self::$heads[$_name]))
            unset(self::$heads[$_name]);
    }

    public static function getScripts($_jquery = true)
    {
        if (count(self::$scripts))
            return '<script>' . ($_jquery ? '$(function(){' : '') . EOL . implode(EOL, self::$scripts) . ($_jquery ? '});' : '') . EOL . '</script>' . EOL;
        return '';
    }

    public static function getJsons()
    {
        $r = '';
        foreach (self::$jsons as $json) {
            //$r .= '<script type="application/ld+json">' . EOL . json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . EOL . '</script>' . EOL;
            $r .= '<script type="application/ld+json">' . EOL . json_encode($json, JSON_UNESCAPED_SLASHES) . EOL . '</script>' . EOL;
        }
        return $r;
    }

    public static function getJSs()
    {
        if (count(self::$js))
            return '<script type="text/javascript" src="' . implode('"></script>' . EOL . '<script type="text/javascript" src="', self::$js) . '"></script>' . EOL;
        return '';
    }

    public static function getCSSs()
    {
        if (count(self::$css))
            return '<link rel="stylesheet" href="' . implode('">' . EOL . '<link rel="stylesheet" href="', self::$css) . '">' . EOL;
        return '';
    }

    public static function getHead()
    {
        $r = '<!DOCTYPE html>' . EOL . EOL;
        $r .= self::$headInfo ? '<!--' . EOL . self::$headInfo . EOL . '-->' . EOL : '';
        $r .= '<html' . (self::$htmlClass ? ' class="' . self::$htmlClass . '"' : '') . (self::$htmlLang ? ' lang="' . self::$htmlLang . '"' : '') .  '>' . EOL;
        $r .= '<head>' . EOL;

        foreach (self::$heads as $name => $value) {
            if (!$value) continue;
            switch ($name) {
                case 'canonical':
                    $r .= TAB . "<link rel=\"canonical\" href=\"{$value}\">" . EOL;
                    break;
                case 'manifest':
                    $r .= TAB . "<link rel=\"manifest\" href=\"{$value}\">" . EOL;
                    break;
                case 'icon':
                    $r .= TAB . "<link rel=\"shortcut icon\" href=\"{$value}\" type=\"image/x-icon\">" . EOL;
                    break;
                case 'code':
                    $r .= TAB . $value . EOL;
                    break;
                case 'charset':
                    $r .= TAB . "<meta charset=\"{$value}\">" . EOL;
                    break;
                case 'name':
                    $r .= TAB . "<meta property=\"og:site_name\" content=\"{$value}\">" . EOL;
                    break;
                case 'title':
                    $r .= TAB . "<title>{$value}</title>" . EOL;
                    $r .= TAB . "<meta name=\"title\" content=\"{$value}\">" . EOL;
                    $r .= TAB . "<meta name=\"twitter:title\" content=\"{$value}\">" . EOL;
                    $r .= TAB . "<meta property=\"og:title\" content=\"{$value}\">" . EOL;
                    break;
                case 'description':
                    $r .= TAB . "<meta name=\"description\" content=\"{$value}\">" . EOL;
                    $r .= TAB . "<meta name=\"twitter:description\" content=\"{$value}\">" . EOL;
                    $r .= TAB . "<meta property=\"og:description\" content=\"{$value}\">" . EOL;
                    break;
                case 'image':
                    $r .= TAB . "<meta name=\"twitter:image\" content=\"{$value}\">" . EOL;
                    $r .= TAB . "<meta property=\"og:image\" content=\"{$value}\">" . EOL;
                    break;
                case 'state':
                    $r .= TAB . "<meta name=\"state\" content=\"{$value}\">" . EOL;
                    $r .= TAB . "<meta name=\"geo.region\" content=\"{$value}\">" . EOL;
                    break;
                case 'location':
                    $r .= TAB . "<meta name=\"geo.placename\" content=\"{$value}\">" . EOL;
                    break;
                case 'twitter-card':
                    $r .= TAB . "<meta name=\"twitter:card\" content=\"{$value}\">" . EOL;
                    break;
                case 'url':
                    $r .= TAB . "<meta property=\"og:url\" content=\"{$value}\">" . EOL;
                    break;
                case 'type':
                    $r .= TAB . "<meta property=\"og:type\" content=\"{$value}\">" . EOL;
                    break;
                case 'locale':
                    $r .= TAB . "<meta property=\"og:locale\" content=\"{$value}\">" . EOL;
                    break;
                case 'x-ua-Compatible':
                    $r .= TAB . "<meta http-equiv=\"X-UA-Compatible\" content=\"{$value}\">" . EOL;
                    break;
                case 'city':
                case 'keywords':
                case 'robots':
                case 'author':
                case 'publisher':
                case 'copyright':
                case 'google-site-verification':
                case 'viewport':
                case 'theme-color':
                case 'revisit-after':
                    $r .= TAB . "<meta name=\"{$name}\" content=\"{$value}\">" . EOL;
                    break;
            }
        }
        $r .= self::getCSSs();
        $r .= self::getJSs();
        $r .= count(self::$headCodes) ?  TAB . implode(EOL, self::$headCodes) . EOL : '';
        $r .= '</head>';
        $r .= count(self::$beginCodes) ?  TAB . implode(EOL, self::$beginCodes) . EOL : '';
        return $r;
    }


    public static function getFoot($_jquery = true)
    {
        self::genBreadcrumbScript();

        $r = '';
        $r .= self::getEndCodes();
        $r .= self::getJsons();
        $r .= self::getScripts($_jquery);
        $r .= '</html>';
        return $r . EOL;
    }

    public static function getheadCodes()
    {
        return implode(EOL, self::$headCodes) . EOL;
    }

    public static function getEndCodes()
    {
        return implode(EOL, self::$endCodes) . EOL;
    }

    public static function findViewFile($_name)
    {

        if ($file = file_exists_case($_name . ".php")) {
            return $file;
        }

        $viewFile = APP_VIEWS . "/$_name.php";
        if ($file = file_exists_case($viewFile)) {
            return $file;
        }

        ErrorHandler::display("View '$_name' não encontrada.");
    }

    public static function showString($_string = '', $_vars = [])
    {
        if ($_vars && count($_vars)) {
            extract($_vars);
        }

        if (count(self::$templateVars)) {
            extract(self::$templateVars);
        }

        self::$content = '' . $_string;
        if (self::$templateContent) {
            eval(' ?>' . self::$templateContent . '<?php ');
            return;
        } else {
            if (!self::$template && ($dt = config_item('DEFAULT_TEMPLATE'))) {
                self::$template = $dt;
            }
            if (self::$template && ($templateFile = self::findViewFile(self::$template))) {
                // concatenamos com uma string para caso $_string seja um objeto,
                // então já usarmos o __toString() aqui, e não passarmos como objeto para $content.
                include $templateFile;
                return;
            }
        }


        echo $_string;
    }

    public static function show($_name = null, $_vars = [])
    {
        if ($_vars && count($_vars))
            extract($_vars);
        $viewContent = '';
        if ($_name) {
            if ($viewFile = self::findViewFile($_name)) {
                ob_start();
                include $viewFile;
                $viewContent = ob_get_clean();
            }
        }

        self::renderStr($viewContent, $_vars);
    }

    public static function read($_name = null, $_vars = [])
    {
        if (count($_vars))
            extract($_vars);
        $viewContent = '';
        if ($_name) {
            if ($viewFile = self::findViewFile($_name)) {
                ob_start();
                include $viewFile;
                $viewContent = ob_get_clean();
            }
        }

        return $viewContent;
    }

    public static function setTemplate($_name)
    {
        self::$template = $_name;
    }

    public static function setContent($_content)
    {
        self::$content = $_content;
    }

    public static function setTemplateContent($_templateContent)
    {
        self::$templateContent = $_templateContent;
    }

    public static function getContent()
    {
        return self::$content;
    }
}
