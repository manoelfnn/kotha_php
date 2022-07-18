<?php

namespace Core\Libs;

use Core\DB;
use Core\Model;
use stdClass;

class JumbotronBuilder
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

        $class = $this->getProp('class') ? $this->getProp('class') : 'text-center';
        $title = $this->getProp('title') ? "<h1 class='display-4'>{$this->getProp('title')}</h1>" : '';
        $text = $this->getProp('text') ? "<p class='lead'>{$this->getProp('text')}</p>" : '';
        $button_label = $this->getProp('button_label') ? $this->getProp('button_label') : '';
        $button_url = $this->getProp('button_url') ? $this->getProp('button_url') : '';
        $button_class = $this->getProp('button_class') ? $this->getProp('button_class') : 'btn btn-primary';

        $button = $button_label && $button_url ? "<hr><a class='$button_class' href='$button_url'>$button_label</a>" : '';

        $html = "
                <div class='jumbotron m-5 $class'>
                    $title
                    $text
                    $button
                </div>         
        ";
        return $html;
    }

    public function __toString()
    {
        return $this->getHTML();
    }
}
