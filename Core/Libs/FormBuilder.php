<?php

namespace Core\Libs;

use Core\DB;
use Core\Model;
use stdClass;

class FormBuilder
{
    public static $defaults = [
        'css_framework' => 'bootstrap4.3',
        'css_framework_style' => 'card',
        'ajax' => false,
        'by_table' => false,
        'name' => 'form',
        'method' => 'post',
        'title' => '',
        'style' => '',
        'class' => '',
        'cancel_url' => '',
        'submit_label' => 'Salvar',
        'hidden_form' => false,
        'submit_class' => '',
        'before_text' => '',
        'after_text' => '',
        'phoneRegex' => '/(\(?\d{2}\)?) ?9?\d{4}-?\d{4}$/',

        //   <strong>Holy guacamole!</strong> You should check in on some of those fields below.


        'show_required' => true,
        'required_code' => '<span class="required text-danger">(*)</span>',
        'info_required' => '<span class="required text-danger small">(*) Preenchimento obrigatório.</span>',

        'all_msg' => '<script>setTimeout(function(){$("#form-alert").alert("close");}, 1300);</script>',
        'insert_msg' => '<div id="form-alert" class="alert alert-success mb-2 alert-dismissible fade show" style="position:fixed;top:0px;left:0px;width:100%;z-index:9999;border-radius:0px" role="alert">Registro adicionado com sucesso!<span type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>',
        'update_msg' => '<div id="form-alert" class="alert alert-success mb-2 alert-dismissible fade show" style="position:fixed;top:0px;left:0px;width:100%;z-index:9999;border-radius:0px" role="alert">Registro atualizado com sucesso!<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>',
        'error_msg' => '<div id="form-alert" class="alert alert-danger mb-2 alert-dismissible fade show" style="position:fixed;top:0px;left:0px;width:100%;z-index:9999;border-radius:0px" role="alert">Não foi possível realizar a operação!<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>',
        'success_msg' => '<div id="form-alert" class="alert alert-success mb-2 alert-dismissible fade show" style="position:fixed;top:0px;left:0px;width:100%;z-index:9999;border-radius:0px" role="alert">Operação realizada com sucesso!<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>',
        'submit_class' => 'btn btn-primary text-uppercase',
        'submit_class_new' => 'btn btn-secondary text-uppercase',
        'help_class' => 'fa fa-fw fa-question-circle',

        'required_msg' => 'Campo requerido',
        'file_max_msg' => 'O tamanho máximo permitido do arquivo é %2$s',
        'file_type_msg' => 'O tipo do arquivo é inválido',
        'min-length_msg' => 'É preciso informar no mínimo %2$s caracteres',
        'max-length_msg' => 'É preciso informar no máximo %2$s caracteres',
        'max_msg' => 'O valor máximo permitido é %2$s',
        'min_msg' => 'O valor mínimo requerido é %2$s',
        'url_msg' => 'URL com formato inválido',
        'email_msg' => 'E-mail com formato inválido',
        'hour_msg' => 'Hora com formato inválida',
        'domain_msg' => 'Domínio com formato inválido',
        'alpha_numeric_msg' => 'Somente letras ou números',
        'numeric_msg' => 'Somente números',
        'name_msg' => 'Não parece ser um nome válido',
        'phone_msg' => 'Telefone com formato inválido',
        'matches_msg' => '"%s" diferente de "%s"',
        'must_be_msg' => 'Inválido',
    ];

    private $props = [];
    private $old_fields = [];
    private $fields = [];
    private $customTypes = [];
    private $renders = [];
    private $stylizer;
    private $msgStatus;
    private $requiredCount = 0;

    private $includedsJSLib = [];

    public function __construct($_config)
    {
        $this->props = array_merge(self::$defaults, $_config);

        if (isset($_config['fields'])) {
            $this->old_fields = $_config['fields'];
            $this->parseFields($_config['fields']);
        }
    }

    public function getFormProp($_name, $_default = null)
    {
        return isset($this->props[$_name]) ? $this->props[$_name] : ($_default ? $_default : '');
    }

    public function setFormProp($_name, $_value)
    {
        $this->props[$_name] = $_value;
    }

    public function isFormProp($_name, $_default = false)
    {
        return isset($this->props[$_name]) ? (in_array($this->props[$_name], ['true', 'TRUE', '1', 'yes']) ? true : false) : $_default;
    }

    public function getFieldProp($_name, $_prop, $_default = null)
    {
        return isset($this->fields[$_name][$_prop]) ? $this->fields[$_name][$_prop] : ($_default ? $_default : '');
    }

    public function setFieldProp($_name, $_prop, $_value)
    {
        $this->fields[$_name][$_prop] = $_value;
    }

    public function isFieldProp($_field, $_name, $_default = false)
    {
        return isset($this->fields[$_field][$_name]) ? (in_array($this->fields[$_field][$_name], ['true', 'TRUE', '1', 'yes']) ? true : false) : $_default;
    }

    public function addFieldOptions($_name, $_label, $_value = null)
    {
        if (is_string($_label)) {
            $this->fields[$_name]['options'][$_value] = $_label;
            return;
        }
        if (is_array($_label)) {
            foreach ($_label as $name => $value) {
                $this->fields[$_name]['options'][$value] = $name;
            }
            return;
        }
    }

    public function addFieldMultipleOptions($_name, $_label, $_value = null, $_selected = false)
    {
        if (is_string($_label)) {
            $this->fields[$_name]['multiple_options'][] = ['value' => $_value, 'label' => $_label, 'selected' => $_selected];
            return;
        }
        if (is_array($_label)) {
            foreach ($_label as $option) {
                $this->fields[$_name]['multiple_options'][] = ['value' => $option['value'], 'label' => $option['label'], 'selected' => isset($option['selected']) ? $option['selected'] : false];
            }
            return;
        }
    }

    public function removeModelField($_name)
    {
        if (isset($this->fields[$_name])) {
            unset($this->fields[$_name]);
            return true;
        }
        return false;
    }

    public function addField($_name, $_props)
    {
        $this->old_fields[$_name] = $_props;
        $this->parseFields($this->old_fields);
    }

    public function addFieldCustomValidation($_name, $_closure)
    {
        $this->fields[$_name]['@custom_validation'] = $_closure;
    }

    public function addCustomType($_name, $_call)
    {
        $this->customTypes[$_name] = $_call;
    }

    public function addRender($_name, $_call)
    {
        $this->renders[$_name] = $_call;
    }

    private function parseFields($_fields)
    {
        $this->fields = null;
        foreach ($_fields as $name => $props) {

            $name = trim($name);


            if (!$name)
                continue;

            $this->fields[$name] = array_merge([
                'name' => $name,
                'id' => $name,
                'type' => 'text',
                'default' => '',
                '@required' => false,
            ], Text::str2Props($props));

            /*
             * Se for do type phone já criamos uma validação do tipo phone.
             */
            if ($this->getFieldProp($name, 'type') == 'phone')
                $this->setFieldProp($name, '@phone', true);

            if ($fo = $this->getFieldProp($name, 'options')) {
                $optStrParts = explode(';', $fo);
                $options = [];
                foreach ($optStrParts as $optStrPart) {
                    if (!$optStrPart) continue;
                    $optStrPart = explode(':', $optStrPart);
                    $options[$optStrPart[0]] = $optStrPart[1];
                }
                $this->setFieldProp($name, 'options', $options);
            }
        }
        if (is_string($this->getFormProp('by_table'))) {
            $this->byTable();
        }
    }

    public function getFieldValue($_name)
    {
        $value = '';
        if ($this->submitted() && !($this->isFieldProp($_name, 'disabled'))) {
            //if (($this->getFieldProp($_name, 'type') == "file") && isset($_FILES[$_name]) && $_FILES[$_name]['tmp_name']) {
            //if (($this->getFieldProp($_name, 'type') == "file") && isset($_FILES[$_name])) {
            if (
                $this->getFieldProp($_name, 'type') == "file" &&
                isset($_FILES[$_name]) &&
                (
                    (is_array($_FILES[$_name]) && $_FILES[$_name]['tmp_name']) ||
                    (!is_array($_FILES[$_name]) && $_FILES[$_name]))
            ) {
                $value = $_FILES[$_name];
            }
            if (($this->getFormProp('method') == "post") && isset($_POST[$_name])) {
                $value = $_POST[$_name];
            }
            if (($this->getFormProp('method') == "get") && isset($_GET[$_name])) {
                $value = $_GET[$_name];
            }
            if (
                $this->getFieldProp($_name, 'type') == 'checkbox' ||
                $this->getFieldProp($_name, 'type') == 'switch'
            ) {
                $value = ($value == 'on' || $value == '1' || $value == 'true' ? '1' : '0');
            }
            if ($this->getFieldProp($_name, 'disabled')) {
                $value = '';
            }
        } else {
            $value = $this->getFieldProp($_name, 'default');
        }

        if (!$this->isFieldProp($_name, 'nohtmlspecialchars') && !is_array($value)) {
            $value = htmlspecialchars(htmlspecialchars_decode($value));
        }

        if (!is_array($value)) {
            $value = trim($value);

            if ($transform = $this->getFieldProp($_name, 'transform')) {
                foreach (explode(";", $transform) as $t) {
                    switch ($t) {
                        case "uppercase": {
                                $value = mb_strtoupper($value);
                                break;
                            }
                        case "lowercase": {
                                $value = mb_strtolower($value);
                                break;
                            }
                        case "slug": {
                                $value = Text::slug($value);
                                break;
                            }
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Converte um array com propriedades dos campos no formato
     * de atributos de tags HTML. Ignorando algumas propriedades
     * que são de uso exclusivo do framework e adaptando algumas
     * que não possuem valor bastando apenas o seu nome.
     *
     * Ex.
     *      Entrada:
     *          ['class' => 'btn', 'name' => 'name1']
     *
     *      Saída:
     *          return "class="btn" name="name1"
     *
     * @param $_props
     * @return string
     */
    private function props2HtmlAttr($_props)
    {
        $r = '';
        foreach ($_props as $name => $value) {

            if (in_array($name, ['name', 'id', 'disabled'])) {

                if ($name == 'name' && ((isset($_props['array']) && $_props['array'] != 'false') || (isset($_props['type']) && $_props['type'] == 'selects'))) {
                    $r .= ' ' . $name . '="' . $value . '[]"';
                } else {
                    $r .= ' ' . $name . '="' . $value . '"';
                }
                continue;
            }

            if (substr($name, 0, 1) == '@') {

                /*
                 * Validações que também são atributos de tags.
                 */
                if (!in_array($name, ['@required', '@required-if', '@max-length', '@min-length', '@max', '@min']))
                    continue;

                /*
                 * O required não tem valor.
                 */
                if ($name == '@required') {
                    if ($value === true || $value == 'true' || $value == '1') {
                        $r .= " required has-required=\"true\"";
                    }
                    continue;
                }

                if ($name == '@required-if') {
                    $r .= " required has-required=\"true\"";
                    continue;
                }

                $r .= ' ' . substr($name, 1) . '="' . $value . '"';

                continue;
            }

            /*
             * Se começa com '_' deve ser inserido como atributo de tag.
             */
            if (substr($name, 0, 1) == '_') {
                $r .= ' ' . substr($name, 1) . '="' . $value . '"';
                continue;
            }
        }
        return $r;
    }

    /**
     * Adiciona propriedades de validação no campo baseado nos Type, Null e
     * Comment da tabela especificada em 'by_table'.
     *
     * Ex.
     * Se no banco de dados a coluna 'name' é do tipo VARCHAR(30) NOT NULL,
     * então no campo do formulário com o nome 'name' será adicionado as validações
     * max-length = 30 e required.
     *
     * Caso existam informações no comentário da coluna estes também serão interpretados.
     * Ex. min-length:6|max-length:30.
     *
     */
    public function byTable()
    {
        $q = DB::executeQuery("SHOW FULL COLUMNS FROM {$this->props['by_table']}");
        while ($column = DB::getRow($q)) {
            $fieldIndex = $column['Field'];

            if (isset($this->fields[$fieldIndex])) {

                $r = Text::str2Props($column['Comment']);
                // juntamos primeiro o que está no banco com o que está no objeto
                $this->fields[$fieldIndex] = array_merge($r, $this->fields[$fieldIndex]);

                $this->fields[$fieldIndex]["@required"] = $column['Null'] == 'NO' ? true : $this->fields[$fieldIndex]['@required'];
                preg_match('/([a-zA-Z]*)[\(]?([0-9]*)[\)]?(.*)$/', $column['Type'], $matches);
                //echo var_dump($matches)."\n\n\n\n";
                if (($matches[1] == 'char' || $matches[1] == 'varchar') && !isset($this->fields[$fieldIndex]['@max-length'])) {
                    $this->fields[$fieldIndex]["@max-length"] = $matches[2];
                    if ($matches[1] == 'char' && !isset($this->fields[$fieldIndex]['@min-length'])) {
                        $this->fields[$fieldIndex]['@min-length'] = $matches[2];
                    }
                }
            }
        }
    }

    public function __toString()
    {
        return $this->getHTML();
    }

    public function open()
    {
        $this->msgStatus = '';
        if (isset($_GET[$this->props['name'] . 'tkn'])) {
            $token = $_GET[$this->props['name'] . 'tkn'];
            if (isset($_SESSION["__formsToken__"][$this->props['name']][$token])) {
                $msgType = $_SESSION["__formsToken__"][$this->props['name']][$token];
                if ($msgType == "insert") {
                    $this->msgStatus .= $this->props['insert_msg'];
                }
                if ($msgType == "update") {
                    $this->msgStatus .= $this->props['update_msg'];
                }
                if ($msgType == "success") {
                    $this->msgStatus .= $this->props['success_msg'];
                }
                if ($msgType == "error") {
                    $this->msgStatus .= $this->props['error_msg'];
                }
                $this->msgStatus .= $this->props['all_msg'];
                unset($_SESSION["__formsToken__"][$this->props['name']][$token]);
            } else {

                if (!$this->isFormProp('ajax')) {

                    $qTkn = $this->props['name'] . 'tkn';
                    $uriParts = explode('?', $_SERVER["REQUEST_URI"]);
                    if (isset($uriParts[1])) {
                        parse_str($uriParts[1], $vars);
                        unset($vars[$qTkn]);
                        $q = "?" . http_build_query($vars);
                    } else {
                        $q = "";
                    }
                    header('location: ' . $q);
                    exit;
                }
            }
        }

        $q = '';
        $qTkn = $this->props['name'] . 'tkn';
        $uriParts = explode('?', $_SERVER["REQUEST_URI"]);
        if (isset($uriParts[1])) {
            parse_str($uriParts[1], $vars);
            unset($vars[$qTkn]);
            $q .= "?" . http_build_query($vars);
        } else {
            $q .= "";
        }
        $action = $uriParts[0] . $q;

        $r = '<div id="' . $this->getFormProp('name') . '-container">';
        $r .= $this->getFormProp('before') . "<form action=\"$action\" name=\"{$this->props['name']}-form\" id=\"{$this->props['name']}-form\" class=\"{$this->props['class']}\" style=\"{$this->props['style']}\" method=\"{$this->props['method']}\" enctype=\"multipart/form-data\">" . EOL;
        $r .= "<input type=\"hidden\" name=\"{$this->props['name']}-submitted\" value=\"1\">" . EOL;
        $r .= $this->getFormProp('begin_form') ? $this->getFormProp('begin_form') : '';
        return $r;
    }

    public function close()
    {


        $ajax  = '';
        if ($this->isFormProp('ajax')) {
            $ajax = '
            $("#btn-' . $this->getFormProp('name') . '-submit").click(function(){
                const formContainer = $("#' . $this->getFormProp('name') . '-container");
                const data = $("#' . $this->getFormProp('name') . '-form").serializeArray();
                formContainer.append(\'<div class="h-100 d-flex align-items-center justify-content-center" style="position: absolute; left: 0; top: 0; height: 100%; width: 100%; background-color: #FFF; opacity: 0.5; text-align: center;"><div><span class="spinner-grow text-primary" role="status" aria-hidden="true" style="width: 5rem; height: 5rem;"></span></div></div>\');                       
                $.ajax({
                    type: "' . $this->getFormProp('method') . '",
                    ' . ($this->getFormProp('ajax_url') ? 'url: "' . $this->getFormProp('ajax_url') . '",' : '') . '
                    data: data,
                    success: function(r){
                        if(r.substr(0, 8)==="REDIRECT"){
                            document.location.href = r.substr(8);
                            return;
                        }                        
                        formContainer.html(r);
                    },
                    error: function(){
                        formContainer.html("Não foi possível completar sua solicitação!");  
                    },                        
                  });               
            });              
            ';
        }

        $r = '</form>' . $this->getFormProp('after');
        $r .= '<script>$(document).ready(function(){

                ' . $this->getFormProp('script') . '

                function applyPlugins(){
                    ' . $this->getFormProp('script_libs') . '
                }
            
                $("#form-' . $this->getFormProp('name') . '").change(function(){
                    $("[show-if]").each(function(){
                        $("#form-group-" + $(this).attr("id")).toggle(eval($(this).attr("show-if")));
                        if(eval($(this).attr("show-if")) && $(this).attr("has-required")){
                            $(this).prop("required", true);
                        } else {
                            $(this).removeAttr("required");
                        }
                    });
                    $("[el-show-if]").each(function(){
                        $(this).toggle(eval($(this).attr("el-show-if")));
                    });    
                    $("[class-on]").each(function(){       
                        if(eval($(this).attr("class-on"))){
                            $(this).addClass($(this).attr("class-on-name"))
                        } else {
                            $(this).removeClass($(this).attr("class-on-name"))
                        }
                    });                                      
                });    
                $("#form-' . $this->getFormProp('name') . '").change();             
                $("[data-toggle=\'tooltip\']").tooltip()
                applyPlugins();                            
                
                ' . $ajax . '

        
            })</script>';
        $r .= '</div>';
        return $r;
    }

    public function includeJSLib($_lib)
    {
        switch ($_lib) {
            case 'price':
                if (!in_array($_lib, $this->includedsJSLib)) {
                    $this->setFormProp('after', $this->getFormProp('after') . "<script type=\"text/javascript\" src=\"https://cdnjs.cloudflare.com/ajax/libs/jquery-price-format/2.2.0/jquery.priceformat.min.js\"></script>");
                    $this->setFormProp('script_libs', $this->getFormProp('script_libs') . "                  
                        $('[price]').each(function(){
                            console.log($(this).attr('price-prefix'))
                            $(this).priceFormat({
                                prefix: $(this).attr('price-prefix') ? $(this).attr('price-prefix') : 'R$',
                                centsSeparator: $(this).attr('price-centsSeparator') ? $(this).attr('price-centsSeparator') : ',',
                                thousandsSeparator: $(this).attr('price-thousandsSeparator') ? $(this).attr('price-thousandsSeparator') : '.'
                            });                       
                        });                                        
                    ");
                }
                break;
            case 'mask':
                if (!in_array($_lib, $this->includedsJSLib)) {
                    $this->includeJSMask = true;
                    $this->setFormProp('after', $this->getFormProp('after') . "<script type=\"text/javascript\" src=\"https://cdnjs.cloudflare.com/ajax/libs/inputmask/4.0.9/jquery.inputmask.bundle.min.js\"></script>");
                    $this->setFormProp('script_libs', $this->getFormProp('script_libs') . "
                          $(\":input\").inputmask();
                    ");
                }
                break;
        }
        array_push($this->includedsJSLib, $_lib);
    }


    public function getFormControl($_name, $_onlyControl = false)
    {

        if (!isset($this->fields[$_name])) {
            return '';
        }

        if ($this->getFieldProp($_name, 'html')) {
            return $this->getFieldProp($_name, 'html');
        }

        if (!$this->isFieldProp($_name, 'show', true)) {
            return '';
        }

        $horizontalLabel = $this->isFormProp('horizontal_label');
        $labelCols = $this->getFormProp('default_label_cols', ($horizontalLabel ? '4' : '12'));
        $cols = $this->getFormProp('default_cols', ($horizontalLabel ? '8' : '12'));

        if ($price = $this->getFieldProp($_name, 'price')) {
            $this->includeJSLib('price');
            $price = explode(";", $price);
            $this->fields[$_name]['_price'] = '';
            if (isset($price[0]) && $price[0]) $this->fields[$_name]['_price-prefix'] = $price[0];
            if (isset($price[1]) && $price[1]) $this->fields[$_name]['_price-centsSeparator'] = $price[1];
            if (isset($price[2]) && $price[2]) $this->fields[$_name]['_price-thousandsSeparator'] = $price[2];
        }

        if ($mask = $this->getFieldProp($_name, 'mask')) {
            $this->includeJSLib('mask');
            if ($this->getFieldProp($_name, 'mask-multiple') != 'true') {
                $mask = "'$mask'";
            }
            $this->setFieldProp($_name, '_data-inputmask', "'mask': $mask");
        }


        $htmlBefore = '';
        $htmlAfter = '';
        $htmlErrors = '';

        $htmlBefore .= $this->getFieldProp($_name, 'before');

        if ($this->getFieldProp($_name, 'bsr')) {
            $htmlBefore .= '<div class="row">';
        }

        if ($this->getFieldProp($_name, 'bc')) {
            $htmlBefore .= '<div class="col-' . $this->getFieldProp($_name, 'bc') . '">';
        }


        $htmlBefore .= '<div id="form-group-' . $_name . '" class="form-group row ' .
            $this->getFieldProp($_name, 'group_class') . ' ' .
            ($this->getFieldProp($_name, 'errors') ? ' has-error' : '') . '">' . EOL;

        $cXL = $this->getFieldProp($_name, 'cols', $cols);
        $cMD = ($cXL + 2 > 12 ? 12 : $cXL + 2);
        $cLG = ($cXL + 1 > 12 ? 12 : $cXL + 1);

        $help = $this->getFieldProp($_name, 'help');
        $required = $this->getFormProp('show_required') && in_array($this->getFieldProp($_name, '@required'), [true, 'true', 1]) ? $this->getFormProp('required_code') : '';

        if ($required) $this->requiredCount++;

        $help = $help ? '<i data-toggle="tooltip" title="' . $help . '" data-placement="' . ($horizontalLabel ? 'top' : 'left') . '" class="' . $this->getFormProp('help_class') . '"></i>' : '';

        if ($title = $this->getFieldProp($_name, 'title')) {
            if ($horizontalLabel) {

                $lCXL = $this->getFieldProp($_name, 'label_cols', $labelCols);
                $lCMD = ($lCXL + 2 > 12 ? 12 : $lCXL + 2);
                $lCLG = ($lCXL + 1 > 12 ? 12 : $lCXL + 1);

                $htmlBefore .= "
                    <label class=\"
                        col-sm-12 
                        col-md-" . $lCMD . " 
                        col-lg-" . $lCLG . " 
                        col-xl-" . $lCXL . " 
                        col-form-label 
                        d-flex 
                        align-items-center \">
                        <div class=\"ml-md-auto\">{$title} {$required} {$help}</div>
                    </label>";


                // Calculamos a quantidade de colunas para o controle,
                // porque se a soma das colunas da label e controle forem maior que 12,
                // teremos que igualar a 12, para isso, tiramos a diferença na 
                // quantidade de colunas do controle.

                if (($lCXL + $cXL) > 12) {
                    $diff = ($lCXL + $cXL) - 12;
                    $cXL = $cXL - $diff;
                }

                if (($lCMD + $cMD) > 12) {
                    $diff = ($lCMD + $cMD) - 12;
                    $cMD = $cMD - $diff;
                }

                if (($lCLG + $cLG) > 12) {
                    $diff = ($lCLG + $cLG) - 12;
                    $cLG = $cLG - $diff;
                }
            } else {
                $htmlBefore .= "
                    <label class=\"col-sm-12\">{$title} {$required} {$help}</label>" . EOL;
            }
        }


        $htmlBefore .= "
            <div class=\"
                col-sm-12 
                col-md-" . $cMD . " 
                col-lg-" . $cLG . " 
                col-xl-" . $cXL . " 
                d-flex flex-column justify-content-center
                \" >" . EOL;

        /*
                
                d-flex 
                flex-column 
                justify-content-center 
                align-items-start
                */

        $htmlBefore .= $this->getFieldProp($_name, 'before_control');

        $controlHTML = '';
        $class = '';

        $attributes = $this->props2HtmlAttr($this->fields[$_name]);

        if ($errors = $this->getFieldProp($_name, 'errors')) {
            $class .= ' is-invalid';
            $htmlErrors .= TAB . TAB . "<div class=\"invalid-tooltip\">" . EOL;
            foreach ($errors as $error) {
                $htmlErrors .= TAB . TAB . "<div>$error</div>" . EOL;
            }
            $htmlErrors .= TAB . TAB . "</div>" . EOL;
        }

        $htmlAfter .= $htmlErrors;


        switch ($this->getFieldProp($_name, 'type')) {
            case 'color':
            case 'number':
            case 'email':
            case 'time':
            case 'url':
            case 'date':
            case 'datetime-local':
            case 'text':
            case 'phone':
            case 'password': {

                    if ($this->getFieldProp($_name, 'prepend') || $this->getFieldProp($_name, 'append')) {
                        $htmlBefore .= "<div class=\"input-group\">" . EOL;
                    }

                    if ($prepend = $this->getFieldProp($_name, 'prepend')) {
                        $htmlBefore .= "<div class=\"input-group-prepend\">" . EOL;
                        $htmlBefore .= "<span class=\"input-group-text\">{$prepend}</span>" . EOL;
                        $htmlBefore .= "</div>" . EOL;
                    }

                    if ($append = $this->getFieldProp($_name, 'append')) {
                        $htmlAfter .= "<div class=\"input-group-append\">" . EOL;
                        $htmlAfter .= "<span class=\"input-group-text\">{$append}</span>" . EOL;
                        $htmlAfter .= "</div>" . EOL;
                    }

                    if ($this->getFieldProp($_name, 'prepend') || $this->getFieldProp($_name, 'append')) {
                        $htmlAfter .= "</div>" . EOL;
                    }

                    $value = $this->getFieldValue($_name);
                    if ($this->getFieldProp($_name, 'type') == 'datetime-local') {
                        $value = date("Y-m-d\TH:i:s", strtotime($value));
                    }

                    $controlHTML .= "<input type=\"" . $this->getFieldProp($_name, 'type') . "\" class=\"form-control$class\"$attributes value=\"$value\">" . EOL;
                    break;
                }
            case 'textarea': {
                    $controlHTML = "<textarea class=\"form-control$class\"$attributes>" . $this->getFieldValue($_name) . "</textarea>" . EOL;
                    break;
                }
            case 'hidden': {
                    $controlHTML = "<input type=\"hidden\"$attributes" . ">" . EOL;
                    break;
                }
            case 'checkbox': {
                    $controlHTML = '
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input' . $class . '" type="checkbox"' . $attributes . ' ' . ($this->getFieldValue($_name) == '1' ? ' checked="checked"' : '') . '>
                        <label class="custom-control-label" for="' . $_name . '">' . $this->getFieldProp($_name, 'checkbox-title') . '</label>
                    </div>';
                    break;
                }
            case 'switch': {
                    $controlHTML = '
                    <div class="custom-control custom-switch">
                        <input class="custom-control-input' . $class . '" type="checkbox"' . $attributes . ' ' . ($this->getFieldValue($_name) == '1' ? ' checked="checked"' : '') . '>                    
                        <label class="custom-control-label" for="' . $_name . '">' . $this->getFieldProp($_name, 'switch-title') . '</label>
                    </div>            
                    
                    ';
                    break;
                }
            case 'radio': {
                    $controlHTML = "<input type=\"radio\"$attributes" . ($this->getFieldValue($_name) == '1' ? ' checked="checked"' : '') . ">" . EOL;
                    break;
                }
            case 'select': {
                    $controlHTML = "<select class=\"form-control$class\"$attributes>" . EOL;
                    $fieldValue = $this->getFieldValue($_name);

                    if ($optionsQuery = $this->getFieldProp($_name, 'options-query')) {
                        $optionValue = $this->getFieldProp($_name, 'options-value', 'id');
                        $optionLabel = $this->getFieldProp($_name, 'options-label', '{name}');
                        foreach (Model::getAllByQuery($optionsQuery) as $model) {

                            $label = Text::strCompile($optionLabel, $model);

                            $value = $model->{camelize("get_$optionValue")}();
                            $controlHTML .= TAB . "<option value=\"$value\" " . ($fieldValue == $value ? 'selected="selected"' : '') . ">$label</option>" . EOL;
                        }
                    } elseif ($options = $this->getFieldProp($_name, 'options')) {
                        foreach ($options as $value => $label) {
                            $controlHTML .= TAB . "<option value=\"$value\" " . ($fieldValue == $value ? 'selected="selected"' : '') . ">$label</option>" . EOL;
                        }
                    }
                    $controlHTML .= "</select>" . EOL;
                    break;
                }
            case 'selects': {
                    $controlHTML = "<select multiple=\"multiple\" class=\"form-control$class\"$attributes>" . EOL;
                    $fieldValue = $this->getFieldValue($_name);
                    if ($options = $this->getFieldProp($_name, 'multiple_options')) {
                        foreach ($options as $option) {
                            $controlHTML .= TAB . "<option value=\"" . $option['value'] . "\" " . ($fieldValue == $option['value'] || $option['selected'] ? 'selected="selected"' : '') . ">" . $option['label'] . "</option>" . EOL;
                        }
                    }
                    $controlHTML .= "</select>" . EOL;
                    break;
                }
            case 'file': {
                    $controlHTML = '                    
                    <div class="custom-file">
                        <input type="file" class="custom-file-input' . $class . '"' . $attributes . '>
                        <label class="custom-file-label" for="customFile">Arquivo</label>
                    </div>' . EOL;
                    break;
                }

            case 'multiple': {

                    $multipleHead = '<thead>' . EOL;
                    $multipleTemplate = '<tr>';
                    $count = 0;
                    $multipleTemplateControlHiddens = '';
                    $multipleTemplateFields = explode("#", str_replace("\n", "", $this->getFieldProp($_name, 'fields')));
                    $fieldWidthTotal = 0;
                    foreach ($multipleTemplateFields as $field) {
                        $field = trim($field);
                        if (!$field) continue;
                        $fieldProperties = [];
                        foreach (explode(";", $field) as $property) {
                            $property = explode("=", trim($property));
                            if (isset($property[0])) {
                                $fieldProperties[trim($property[0])] = isset($property[1]) ? $property[1] : "";
                            }
                        }
                        if (!isset($fieldProperties["type"])) return;

                        $headerClass = (isset($fieldProperties["header-class"]) ? $fieldProperties["header-class"] : '');
                        $fieldWidth = (isset($fieldProperties["width"]) ? $fieldProperties["width"] : 'auto');
                        $fieldAttributes = ' name="' . $_name . '[##COUNT##][' . (isset($fieldProperties["name"]) ? $fieldProperties["name"] : $count) . ']" ';
                        $fieldAttributes .= ' field="' . $fieldProperties["name"] . '" ';
                        //   $fieldAttributes .= ' required ';
                        //$fieldClassId = " field " . (isset($fieldProperties["name"]) ? $fieldProperties["name"] : "field-" . $count);


                        $fieldAttributes .= HTML::props2HtmlAttr($fieldProperties);
                        $multipleTemplateControl = '';

                        if (isset($fieldProperties["price"])) {
                            $price = explode(",", $fieldProperties["price"]);
                            $fieldAttributes .= ' price=""';
                            if (isset($price[0]) && $price[0]) $fieldAttributes .= ' price-prefix="' . $price[0] . '"';
                            if (isset($price[1]) && $price[1]) $fieldAttributes .= ' price-centsSeparator="' . $price[1] . '"';
                            if (isset($price[2]) && $price[2]) $fieldAttributes .= ' price-thousandsSeparator="' . $price[2] . '"';
                            $this->includeJSLib('price');
                        }

                        if (isset($fieldProperties["mask"])) {
                            $mask = $fieldProperties["mask"];
                            if (!isset($fieldProperties["mask-multiple"]) || $fieldProperties["mask-multiple"] != 'true') {
                                $mask = "\'$mask\'";
                            }

                            $fieldAttributes .= ' data-inputmask="\\\'mask\\\': ' . $mask . '"';

                            $this->includeJSLib('mask');
                        }

                        switch ($fieldProperties["type"]) {
                            case "hidden":
                                $multipleTemplateControlHiddens .= '
                                        <input type="hidden" ' . $fieldAttributes . '>
                                    ';
                                break;
                            case "time":
                            case "datetime-local":
                            case "text":

                                if (isset($fieldProperties['prepend']) || isset($fieldProperties['append'])) {
                                    $multipleTemplateControl .= "<div class=\"input-group\">" . EOL;
                                    if (isset($fieldProperties['prepend'])) {
                                        $multipleTemplateControl .= "<div class=\"input-group-prepend\">" . EOL;
                                        $multipleTemplateControl .= "<span class=\"input-group-text\">{$fieldProperties['prepend']}</span>" . EOL;
                                        $multipleTemplateControl .= "</div>" . EOL;
                                        $multipleTemplateControl .= '
                                        <input type="text" ' . $fieldAttributes . ' class="form-control">
                                    ';
                                    }
                                    if (isset($fieldProperties['append'])) {
                                        $multipleTemplateControl .= '
                                            <input type="text" ' . $fieldAttributes . ' class="form-control">
                                        ';
                                        $multipleTemplateControl .= "<div class=\"input-group-append\">" . EOL;
                                        $multipleTemplateControl .= "<span class=\"input-group-text\">{$fieldProperties['append']}</span>" . EOL;
                                        $multipleTemplateControl .= "</div>" . EOL;
                                    }
                                    $multipleTemplateControl .= "</div>" . EOL;
                                } else {
                                    $multipleTemplateControl .= '
                                        <input type="' . $fieldProperties["type"] . '" ' . $fieldAttributes . ' class="form-control">
                                    ';
                                }
                                break;
                            case "checkbox":
                                $multipleTemplateControl .= '
                                            <div class="form-check">
                                                <input class="form-check-input position-static" type="checkbox" ' . $fieldAttributes . ' ' . (isset($fieldProperties["default"]) && $fieldProperties["default"] ? ' checked="checked"' : '') . '>
                                            </div>                                            
                                        ';
                                break;
                            case "select":
                                $options = [];
                                if (isset($fieldProperties['options'])) {
                                    foreach (explode(',', $fieldProperties['options']) as $option) {
                                        $option = explode(':', $option);
                                        $msv = trim(isset($option[0]) ? $option[0] : '');
                                        $msl = trim(isset($option[1]) ? $option[1] : '');
                                        array_push($options, '<option value="' . ($msv) . '">' . $msl . '</option>');
                                    }
                                }
                                $multipleTemplateControl .= '
                                    <select ' . $fieldAttributes . ' class="form-control">
                                    ' . implode("", $options) . '
                                    </select>
                                ';
                                break;
                            case "number":
                                $multipleTemplateControl .= '
                                            <input type="number" ' . $fieldAttributes . ' class="form-control">
                                        ';
                                break;
                            case "color":
                                $multipleTemplateControl .= '
                                                <input type="color" ' . $fieldAttributes . ' class="form-control">
                                            ';
                                break;
                        }
                        if ($fieldProperties["type"] != 'hidden') {;
                            $multipleTemplate .= '<td ' . HTML::props2HtmlAttr($fieldProperties, '~') . ' ' . HTML::props2HtmlAttr($fieldProperties, '_') . ' style="width: ' . $fieldWidth . ';">' . $multipleTemplateControl . '</td>';
                            $multipleHead .= '<th ' . HTML::props2HtmlAttr($fieldProperties, '~') . ' ' . HTML::props2HtmlAttr($fieldProperties, '-') . ' style="width: ' . $fieldWidth . 'px;">' . (isset($fieldProperties["title"]) ? $fieldProperties["title"] : '') . '</th>';
                            $fieldWidthTotal += intval($fieldWidth);
                        }
                        $count++;
                    }
                    $multipleTemplate .= '
                        <td style="text-align: center; width: 150px;">
                            ' . $multipleTemplateControlHiddens . '
                            <button type="button" class="btn btn-sm btn-default ' . $_name . '-remove"><i class="fa fa-fw fa-trash"></i></button>
                            <button type="button" class="btn btn-sm btn-default ' . $_name . '-up"><i class="fa fa-fw fa-chevron-up"></i></button>
                            <button type="button" class="btn btn-sm btn-default ' . $_name . '-down"><i class="fa fa-fw fa-chevron-down"></i></button>
                        </td>';
                    $multipleTemplate .= '</tr>';

                    $multipleHead .= '<th style="width: 25px;"></th>';
                    $multipleHead .= '</thead>' . EOL;


                    $controlHTML = '
                    <div ' . $attributes . '>
                    <div class="table-responsive">
                    <table  id="' . $_name . '-table" class="table table-bordered table-sm table-hover table-striped" 
                    style="width: ' . $fieldWidthTotal . 'px;">' . EOL .
                        $multipleHead . EOL .
                        '<tbody></tbody>
                        </table>
                        </div>
                        <div><button class="btn btn-sm btn-primary" type="button" id="' . $_name . '-add"><i class="fa fa-fw fa-plus"></i></button></div>
                        </div>
                        ' . EOL;

                    $multipleTemplate = str_replace("\n", "", $multipleTemplate);
                    $multipleTemplate = str_replace("\r", "", $multipleTemplate);

                    $script = '
                        const ' . $_name . 'Template = \'' . $multipleTemplate . '\';
                    ';

                    $values = $this->getFieldValue($_name);

                    $initialValues = '[]';
                    if ($values) {
                        $initialValues = json_encode($values);
                        $script .= '
                            const ' . $_name . 'InitialValues = ' . $initialValues . ';
                            ' . $_name . 'InitialValues.forEach(function(values){
                                    const count = $("#' . $_name . '-table tbody tr").length;
                                    $("#' . $_name . '-table tbody").append(' . $_name . 'Template.replace(/##COUNT##/g, count));
                                    $("#' . $_name . '-table tbody tr:last-child [field]").each(function(index){
                                        const field = $(this).attr("field");
                                        if($(this).is(":checkbox")){
                                            $(this).prop("checked", values[field] == 1 || values[field] == "on" );
                                        } else {
                                            $(this).val(values[field] === null ? 0 : values[field]);
                                        }
                                    });
                            });
                            applyPlugins();
                            ';
                    }

                    $script .= '
                        $("#' . $_name . '-add").click(function(){
                            const count = $("#' . $_name . '-table tbody tr").length;                        
                            $("#' . $_name . '-table tbody").append(' . $_name . 'Template.replace(/##COUNT##/g, count)); 
                            $("#' . $_name . '-table tbody tr:last-child [field]:first").focus();   
                            applyPlugins();
                        });
                        $("#' . $_name . '-table tbody").on("keypress", "[field]", function(key){
                            if(key.charCode == 13){
                                key.preventDefault();
                                if($(this).closest("td").next("td").find("[field]").length){
                                    $(this).closest("td").next("td").find("[field]").focus()
                                } else {
                                    $("#' . $_name . '-add").focus()
                                }
                            }
                        });
                        $("#' . $_name . '-table").on("click", ".' . $_name . '-remove", function(){
                            if(confirm("Você tem certeza que deseja remover?")){
                                $(this).closest("tr").remove();
                            }
                        });   
                        
                        $("#' . $_name . '-table").on("click", ".' . $_name . '-up, .' . $_name . '-down", function(){
                            var row = $(this).parents("tr:first");
                            if ($(this).is(".' . $_name . '-up")) {
                                row.insertBefore(row.prev());
                            } else {
                                row.insertAfter(row.next());
                            }
                        });                        

                    ';

                    $this->setFormProp('script', $this->getFormProp('script') . $script);
                    break;
                }

            default: {
                    if (isset($this->customTypes[$this->getFieldProp($_name, 'type')])) {
                        $call = $this->customTypes[$this->getFieldProp($_name, 'type')];
                        $controlHTML .= $call($_name, $attributes, $this->getFieldValue($_name));
                    }
                }
        }


        if ($help_block = $this->getFieldProp($_name, 'help_block')) {
            $hbTag = 'div';
            if ($this->getFieldProp($_name, 'type') == 'checkbox') $hbTag = 'span';
            $htmlAfter .= "<$hbTag class=\"help-block text-muted\">{$help_block}</$hbTag>" . EOL;
        }



        //$fieldsHTML .= $horizontalLabel ? TAB . TAB . "</div>" . EOL : '';

        $htmlAfter .= TAB . $this->getFieldProp($_name, 'after_control') . EOL;
        $htmlAfter .= TAB . '</div>' . EOL;
        $htmlAfter .= TAB . '</div>' . EOL;
        if ($this->getFieldProp($_name, 'bc')) {
            $htmlAfter .= TAB . '</div>' . EOL;
        }

        if ($this->getFieldProp($_name, 'ber')) {
            $htmlAfter .= TAB . '</div>' . EOL;
        }

        $htmlAfter .= TAB . $this->getFieldProp($_name, 'after') . EOL;

        if ($this->getFieldProp($_name, 'render') && isset($this->renders[$this->getFieldProp($_name, 'render')])) {
            $call = $this->renders[$this->getFieldProp($_name, 'render')];
            $controlHTML = $call($_name, $this->fields[$_name], $this->getFieldValue($_name));
        }


        $controlHTML = $_onlyControl ? $controlHTML . $htmlErrors : $htmlBefore . $controlHTML . $htmlAfter;

        return $controlHTML;
    }

    /**
     * Obtem um ou mais controles delimitados por $_form e $_to
     *
     * @param $_from A partir deste controle
     * @param $_to Até este controle
     * @param bool $_stylizer Se true estiliza todos os controles
     * @return string
     *
     */
    public function getFormControls($_from, $_to, $_stylizer = true)
    {
        $fieldsHTML = '';
        $between = false;

        foreach (array_keys($this->fields) as $name) {
            if (!$between && ($name == $_from)) {
                $between = true;
            }
            if ($between) {
                $fieldsHTML .= $this->getFormControl($name, $_stylizer);
            }
            if ($between && ($name == $_to)) {
                $between = false;
            }
        }
        return $fieldsHTML;
    }

    public function getFormControlsAll($_onlyControl = false)
    {
        $fieldsHTML = '';

        foreach (array_keys($this->fields) as $name) {
            $fieldsHTML .= $this->getFormControl($name, $_onlyControl);
        }
        return $fieldsHTML;
    }


    public function getMsgStatus()
    {
        return $this->msgStatus;
    }

    public function getHTML()
    {
        $fieldsHTML = '';

        if ($this->fields) {
            foreach (array_keys($this->fields) as $name) {
                $fieldsHTML .= $this->getFormControl($name);
            }
        }
        $formHTML = '';

        $formHTML .= $this->open();

        if ($this->props['hidden_form'] && $this->msgStatus) {
            $formHTML = $this->msgStatus . EOL;
        } else {

            $structHTML = '';

            //$structHTML = $this->stylizer->stylizeStruct($this->props['css_framework_style'], $fieldsHTML);

            $structHTML = "
                <div class=\"card " . $this->getFormProp('card_class') . "\">" . ($this->getFormProp('title') ? "<div class=\"card-header\">" . $this->getFormProp('title') . "</div>" : "") . "
                    <div class=\"card-body pb-0\">
                        " . $this->getMsgStatus() . "                    
                        " . ($this->getFormProp('before_text') ? "<div class=\"card-text\">" . $this->getFormProp('before_text') . "</div>" : "") .
                $fieldsHTML . ($this->getFormProp('after_text') ? "<div class=\"card-text my-3\">" . $this->getFormProp('after_text') . "</div>" : "") . "                
                        " . ($this->getFormProp('show_required') && $this->requiredCount > 0 ? '<div class="info-required mb-3">' . $this->getFormProp('info_required') . '</div>' : '') . "
                    </div>
                    <div class=\"card-footer\">
                        
                        
                        <button " . ($this->isFormProp('ajax') ? "type=\"button\" id=\"btn-" . $this->getFormProp('name') . "-submit\"" : "") . " class=\"" . $this->getFormProp('submit_class') . "\">" . $this->getFormProp('submit_label') . "</button>
                        " . ($this->getFormProp('button_new') ? "<button name=\"" . $this->getFormProp('name') . "-create-new\" value=\"true\" class=\"" . $this->getFormProp('submit_class_new') . "\">" . $this->getFormProp('submit_label') . " e adicionar novo</button>" : "") . "
                        " . ($this->getFormProp('cancel_url') ? "<a class=\"btn btn-default\" href=\"" . $this->getFormProp('cancel_url') . "\">Cancelar</a>" : "") . "

                        ".($this->getFormProp('after_submit') ? '<div>'.$this->getFormProp('after_submit').'</div>' : "")."

                    </div>
                </div>";

            $formHTML .= $structHTML;
            $formHTML .= $this->close();
        }

        return $formHTML;
    }

    public function done($_type = 'insert', $_redirect = '')
    {
        if ($_type === true) {
            $_type = 'success';
        }
        if ($_type === false) {
            $_type = 'error';
        }
        $token = bin2hex(openssl_random_pseudo_bytes(64));
        $_SESSION["__formsToken__"][$this->props['name']][$token] = $_type;
        $qTkn = $this->props['name'] . 'tkn';
        $redirectTo = $_redirect ? $_redirect : $_SERVER['REQUEST_URI'];
        $parts = explode('?', $redirectTo);
        $toUrl = $parts[0];
        if (isset($parts[1])) {
            parse_str($parts[1], $vars);
            $vars[$qTkn] = $token;
            $toUrl .= '?' . http_build_query($vars);
        } else {
            $toUrl .= "?$qTkn=$token";
        }
        if ($this->isFormProp('ajax')) {
            $_GET[$this->props['name'] . 'tkn'] = $token;
            echo $this->getHTML();
        } else {
            header('location: ' . $toUrl);
        }
        exit;
    }

    /**
     * Verifica se o formulário foi enviado.
     *
     * @return bool
     */
    public function submitted()
    {
        if (($this->props['method'] == "post") && isset($_POST["{$this->props['name']}-submitted"])) {
            return true;
        }
        if (($this->props['method'] == "get") && isset($_GET["{$this->props['name']}-submitted"])) {
            return true;
        }
        return false;
    }

    public function load($_from)
    {
        if (null == $_from)
            return false;

        if ($this->fields) {
            foreach ($this->fields as $name => $props) {
                if ($_from instanceof Model && $_from->fieldExist($name)) {
                    $this->fields[$name]['default'] = $_from->{camelize("get_$name")}();
                } elseif (is_array($_from) && isset($_from[$name])) {
                    $this->fields[$name]['default'] = $_from[$name];
                } elseif (isset($_from->$name)) {
                    $this->fields[$name]['default'] = $_from->$name;
                } else {
                    $subObjs = explode('__', $name);
                    $curObj = $_from;
                    if (count($subObjs) > 1) {
                        foreach ($subObjs as $subObj) {
                            if (!isset($curObj->$subObj)) break;
                            $curObj = $curObj->$subObj;
                        }
                        $value = is_object($curObj) ? null : $curObj;
                        $this->fields[$name]['default'] = $value !== null ? $value : $this->fields[$name]['default'];
                    }
                }
            }
        }
        return true;
    }

    public function store($_to, $ignoreControls = [])
    {
        if (null == $_to)
            return false;

        foreach ($this->fields as $name => $props) {
            if (
                in_array($name, $ignoreControls) ||
                (isset($props['save']) && $props['save'] == 'false') ||
                isset($props['html']) ||
                (isset($props['disabled']) && $props['disabled'] == 'true')
            )
                continue;

            if ($_to instanceof Model) {
                $_to->{camelize("set_$name")}($this->getFieldValue($name));
            } else {
                $subObjs = explode('__', $name);
                if (count($subObjs) > 1) {
                    $curObj = $_to;
                    for ($i = 0; $i < count($subObjs) - 1; $i++) {
                        $subObj = $subObjs[$i];
                        if (!isset($curObj->$subObj)) {
                            $curObj->$subObj = new stdClass();
                        }
                        $curObj = $curObj->$subObj;
                    }
                    $curObj->{$subObjs[$i]} = $this->getFieldValue($name);
                } else {
                    $_to->$name = $this->getFieldValue($name);
                }
            }
        }
        return true;
    }

    public function validate()
    {
        $pass = false;
        if ($this->submitted()) {

            $this->setFormProp('create-new', $this->getFieldValue($this->getFormProp('name') . '-create-new') ? true : false);

            $pass = true;

            foreach ($this->fields as $fieldName => $fieldProps) {


                $value = $this->getFieldValue($fieldName);
                if (isset($fieldProps['@required']) && ($fieldProps['@required'] === false) && ($value == '')) {
                    continue;
                }

                if (isset($fieldProps['disabled']) && ($fieldProps['disabled'] == 'true')) {
                    continue;
                }
                if ($fieldProps["type"] == 'file' && (isset($value['name']))) {
                    $value = $value['name'];
                }
                $fpass = true;
                foreach ($fieldProps as $fieldPropName => $fieldPropValue) {

                    $propMsg = $this->getFieldProp($fieldName, '#' . substr($fieldPropName, 1), $this->getFormProp(substr($fieldPropName, 1) . '_msg'));
                    $propTitle = $this->getFieldProp($fieldName, 'title');

                    switch ($fieldPropName) {
                        case  '@required': {
                                if ($fieldPropValue === false)
                                    break;

                                if ($value == null) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }

                            //name;==;1
                        case  '@required-if': {
                                if ($fieldPropValue === false)
                                    break;
                                $parts = explode(";", $fieldPropValue);

                                if (count($parts) > 2) {
                                    $compare = "return '" . $this->getFieldValue($parts[0]) . "'" . $parts[1] . "'" . $parts[2] . "';";
                                    $result = eval($compare);
                                    if ($result && $value == null) {
                                        $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                        $fpass = false;
                                    }
                                }
                                break;
                            }

                        case '@file_max': {
                                if (is_array($value)) {
                                    if ($value['size'] > $fieldPropValue) {
                                        $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle, Number::formatBytes($fieldPropValue));
                                        $fpass = false;
                                    }
                                }
                                break;
                            }
                        case '@file_type': {
                                if (is_array($value)) {
                                    $typesAllowed = explode(';', $fieldPropValue);
                                    if (!in_array($value['type'], $typesAllowed)) {
                                        $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle, $fieldPropValue);
                                        $fpass = false;
                                    }
                                }
                                break;
                            }
                        case '@min-length': {
                                if (mb_strlen($value) < $fieldPropValue) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle, $fieldPropValue);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@max-length': {
                                if (mb_strlen($value) > $fieldPropValue) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle, $fieldPropValue);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@max': {
                                if (intval($value) > $fieldPropValue) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle, $fieldPropValue);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@min': {
                                if (intval($value) < $fieldPropValue) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle, $fieldPropValue);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@url': {
                                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@email': {
                                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@hour': {
                                if (!preg_match("/^(?:2[0-4]|[01][1-9]|10|00):([0-5][0-9])$/", $value)) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@domain': {
                                if (!preg_match("@^([a-z0-9]([a-z0-9]|(\-[a-z0-9]))*\.)+[a-z]+$@i", $value)) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@alpha_numeric': {
                                if (!preg_match('"^[a-zA-Z0-9_]*$"', $value)) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@numeric': {
                                if (!preg_match('"^[0-9_]*$"', $value)) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@name': {
                                if (!preg_match("~^(?:[\p{L}\p{Mn}\p{Pd}\'\x{2019}]+\s[\p{L}\p{Mn}\p{Pd}\'\x{2019}]+\s?)+$~u", $value)) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@phone': {
                                if (!preg_match($this->phoneRegex, $value)) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@matches': {
                                if ($this->getFieldValue($fieldPropValue) != $value) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg, $propTitle, $this->getFieldProp($fieldPropValue, 'title'));
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@must_be': {
                                if ($fieldPropValue != $value) {
                                    $this->fields[$fieldName]['errors'][] = sprintf($propMsg);
                                    $fpass = false;
                                }
                                break;
                            }
                        case '@custom_validation': {
                                $closureReturn = $fieldPropValue($value);
                                if (true !== $closureReturn) {
                                    $fpass = false;
                                    $this->fields[$fieldName]['errors'][] = $closureReturn;
                                }
                                break;
                            }
                    }

                    if (!$fpass) {
                        $pass = false;
                        break;
                    }
                }
            }

            if (!$pass && $this->isFormProp('ajax')) {
                echo $this->getHTML();
                exit;
            }
        }
        return $pass;
    }
}
