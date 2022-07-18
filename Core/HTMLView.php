<?php

namespace Core;

abstract class HTMLView
{

    static private $titleDivider = '-';
    static private $template;
    static private $templateVars = [];
    static private $templateContent;
    static private $onTemplateContent = null;
    static private $content;
    static private $headers = [];
    static private $jsons = [];
    static private $breadcrumbList = [];
    static private $htmlClass = null;
    static private $headInfo = '';

    static private $libs = [];
    static private $scripts = ['header' => '', 'begin' => '', 'end' => ''];
    static private $codes = ['header-prefiles' => '', 'header' => '', 'begin' => '', 'end' => ''];
    static private $files = ['css' => ['header' => '', 'end' => ''], 'js' => ['header' => '', 'end' => '']];

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

    public static function removeLastBreadcrumb()
    {
        return array_pop(self::$breadcrumbList);
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
        $title = isset(self::$headers['title']) ? $_title . ' ' . (self::$titleDivider ? self::$titleDivider . ' ' : '') . self::$headers['title'] : $_title;
        self::setHeader('title', $title);
    }

    public static function getTitle()
    {
        return self::$headers['title'];
    }

    public static function setTitleDivider($_divider)
    {
        self::$titleDivider = $_divider;
    }

    public static function getTitleDivider()
    {
        return self::$titleDivider;
    }

    public static function setHTMLTagClass($_class)
    {
        self::$htmlClass = $_class;
    }

    public static function setHeadInfo($_headInfo)
    {
        self::$headInfo = $_headInfo;
    }

    public static function setHeader($_name, $_value = null)
    {
        self::$headers[$_name] = $_value;
    }

    public static function getHeader($_name)
    {
        return self::$headers[$_name];
    }

    public static function removeHeader($_name)
    {
        if (isset(self::$headers[$_name]))
            unset(self::$headers[$_name]);
    }

    public static function addCode($_code, $_position) // position = [header-prefiles, header, begin, end]
    {
        self::$codes[$_position] .= "\t" . $_code . EOL;
    }

    public static function addScript($_script, $_position) // position = [header, begin, end]
    {
        self::$scripts[$_position] .= "\t" . $_script . EOL;
    }

    public static function addFile($_type, $_file, $_position = 'header') // type = [css, js], position = [header, end]
    {
        self::$files[$_type][$_position] .= "\t" . ($_type == 'css' ? '<link rel="stylesheet" href="' . $_file . '">' : '<script type="text/javascript" src="' . $_file . '"></script>') . EOL;
    }

    public static function addJson($_json)
    {
        array_push(self::$jsons, $_json);
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

    public static function useLib(...$_libs)
    {

        foreach ($_libs as $_lib) {
            if (!isset(self::$libs[$_lib])) {
                array_push(self::$libs, $_lib);
                switch ($_lib) {
                    case 'jquery341':
                        self::addFile('js', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js');
                        break;
                    case 'bootstrap431':
                        self::addFile('css', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css');
                        self::addFile('js', 'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js');
                        self::addFile('js', 'https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js');
                        break;
                    case 'aos234':
                        self::addFile('css', 'https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css');
                        self::addFile('js', 'https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js');
                        self::addScript('AOS.init({duration: 1500});', 'end');
                        break;
                    case 'lazy179':
                        self::addFile('js', '//cdnjs.cloudflare.com/ajax/libs/jquery.lazy/1.7.9/jquery.lazy.min.js');
                        self::addScript('$(".lazy").Lazy();', 'end');
                        break;
                    case 'etline101':
                        self::addFile('css', 'https://cdn.jsdelivr.net/npm/et-line@1.0.1/style.min.css');
                        break;
                    case 'fontawesome470':
                        self::addFile('css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
                        break;
                    case 'remixicon':
                        self::addFile('css', 'https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css');
                        break;
                }
            }
        }
    }

    public static function removeLib($_lib)
    {
        if (isset(self::$libs[$_lib])) {
            unset(self::$libs[$_lib]);
        }
    }

    static private function genLibs()
    {
        $r = '';

        return $r;
    }


    public static function genHeader()
    {
        $lang = self::getHeader('lang');
        $r = '<!DOCTYPE html>' . EOL . EOL;
        $r .= self::$headInfo ? '<!--' . EOL . self::$headInfo . EOL . '-->' . EOL : '';
        $r .= '<html' . (self::$htmlClass ? ' class="' . self::$htmlClass . '"' : '') . ($lang ? ' lang="' . $lang . '"' : '') .  '>' . EOL;
        $r .= '<head>' . EOL;

        foreach (self::$headers as $name => $value) {
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

        $r .= self::$codes['header-prefiles'];
        $r .= self::$files['css']['header'];
        $r .= self::$files['js']['header'];
        $r .= self::$scripts['header'] ? '<script>$(function(){' . self::$scripts['header'] . '});</script>' . EOL : '';
        $r .= self::$codes['header'];
        $r .= '</head>' . EOL;
        $r .= self::$codes['begin'];
        return $r;
    }


    public static function genFooter($_jquery = true)
    {
        self::genBreadcrumbScript();

        $r = '';
        $r .= self::$files['css']['end'];
        $r .= self::$files['js']['end'];
        $r .= self::$scripts['end'] ? '<script>$(function(){' . self::$scripts['end'] . '});</script>' . EOL : '';
        $r .= self::$codes['end'];
        $r .= self::getJsons();

        $r .= '</html>';
        return $r . EOL;
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

    public static function renderStr($_string = '', $_vars = [])
    {
        if ($_vars && count($_vars)) {
            extract($_vars);
        }

        if (count(self::$templateVars)) {
            extract(self::$templateVars);
        }

        self::$content = '' . $_string;
        if (self::$templateContent) {
            if (self::$onTemplateContent) {
                $func = self::$onTemplateContent;
                eval(' ?>' . $func(self::$templateContent) . '<?php ');
            } else {
                eval(' ?>' . self::$templateContent . '<?php ');
            }
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

    public static function render($_name = null, $_vars = [])
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

    public static function setOnTemplateContent($_func)
    {
        self::$onTemplateContent = $_func;
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
