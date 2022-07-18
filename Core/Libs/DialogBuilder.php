<?php

namespace Core\Libs;

use Core\DB;
use Core\Model;
use stdClass;

class DialogBuilder
{
    public static $defaults = [
        'name' => 'dlg',
        'option_class' => 'mr-1 btn',
    ];

    private $props = [];

    public function __construct($_config)
    {
        $this->props = array_merge(self::$defaults, $_config);
    }

    public function getProp($_name, $_default = null)
    {
        return isset($this->props[$_name]) ? $this->props[$_name] : ($_default ? $_default : '');
    }

    public function setProp($_name, $_value)
    {
        $this->props[$_name] = $_value;
    }

    public function isProp($_name, $_default = false)
    {
        return isset($this->props[$_name]) ? (in_array($this->props[$_name], ['true', 'TRUE', '1', 'yes']) ? true : false) : $_default;
    }

    public function getHTML()
    {

        if($this->getProp('direct')){
            $option = $this->getProp('options')[$this->getProp('direct')];
            if (is_callable($option['action'])) {
                return $option['action']();
            }
            return;
        }

        $dialogName = $this->getProp('name');

        if (GET($dialogName . '_option') && isset($this->getProp('options')[GET($dialogName . '_option')])) {
            $option = $this->getProp('options')[GET($dialogName . '_option')];
            if (is_callable($option['action'])) {
                return $option['action']();
            }
            return '';
        }

        $options = '';
        if ($this->getProp('options')) {
            foreach ($this->getProp('options') as $name => $props) {
                $class = $this->getProp('option_class') . ' ' . (isset($props['class']) ? $props['class'] : '');
                $options .= "<button name='{$dialogName}_option' value='$name' class='$class'>{$props['title']}</button>";
            }
        }

        $title = $this->getProp('title') ? "<h5 class='card-title'>{$this->getProp('title')}</h5>" : '';
        $subtitle = $this->getProp('subtitle') ? "<h6 class='card-subtitle mb-2 text-muted'>{$this->getProp('subtitle')}</h6>" : '';
        $text = $this->getProp('text') ? "<p class='card-text'>{$this->getProp('text')}</p>" : '';
        $icon = $this->getProp('icon') ? "<div class='m-3'><i class='{$this->getProp('icon')}'></i></div>" : '';

        $html = "
            <form method='get' action='' class='d-flex justify-content-center align-items-center'>
                <div class='card' style='width: auto;'>
                    <div class='card-body d-flex align-items-center'>
                        $icon
                        <div>
                            $title
                            $subtitle 
                        </div>
                    </div>
                    <div class='card-footer'>
                        $options         
                    </div>
                </div>
            </form>                
        ";
        return $html;
    }

    public function __toString()
    {
        return $this->getHTML();
    }
}
