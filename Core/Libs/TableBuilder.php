<?php

namespace Core\Libs;

use ArgumentCountError;
use Core\App;
use Core\DB;
use Core\Model;

class TableBuilder
{
    private $params = [
        "filter",
        "page",
        "search",
        "ord"
    ];
    private $props = [
        'class' => 'table',
        'id_name' => 'id',
        'name' => 'tbl',
        'basic_url' => '?',
        'show_header' => true,
        'show_search' => false,
        'show_filters' => false,
        'show_info' => true,
        'max_per_page' => 15,
        'icon_sort_asc' => ' <span><i class="fa fa-sort-asc"></i></span>',
        'icon_sort_desc' => ' <span><i class="fa fa-sort-desc"></i></span>',
        'info_class' => 'tableview-info',
        'select_options' => [],
        'render_functions' => [],
    ];
    private $columns = [];

    public function __construct($_config)
    {
        $this->props = array_merge($this->props, $_config);

        if (isset($_config['columns'])) {
            unset($this->props['columns']);
            $this->parseColumns($_config['columns']);
        }
    }

    public function setTableProp($_name, $_value)
    {
        $this->props[$_name] = $_value;
    }

    public function getTableProp($_name)
    {
        return isset($this->props[$_name]) ? $this->props[$_name] : '';
    }

    public function addRowCustomRender($_closure)
    {
        $this->props["custom_row_render"] = $_closure;
    }

    public function addColumnCustomRender($_name, $_closure)
    {
        $this->columns[$_name]['custom_render'] = $_closure;
    }

    public function setActions($_closure)
    {
        $this->props['actions'] = $_closure;
    }

    public function addSelectOptions($_title, $_url, $_confirm = null)
    {
        array_push($this->props['select_options'], ['title' => $_title, 'url' => $_url, 'confirm' => $_confirm]);
    }

    private function parseColumns($_columns)
    {
        $this->columns = null;
        foreach ($_columns as $name => $props) {
            if (!$name) continue;
            $column = [
                'name' => $name,
            ];
            $r = Text::str2Props($props);
            $this->columns[$name] = array_merge($column, $r);
        }
    }

    private function props2HtmlAttr($_props, $_start = '_')
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


    public function __toString()
    {
        return $this->getHTML();
    }

    // genParams("filter", 1, ["page", "query", "order", "order-column"]);
    // genParams("page", 1);
    // genParams("query", 1, ["page"]);
    public function genParams($_name, $_value, $_resets = [])
    {
        $p = [];
        if ($_name) {
            $p[] = $this->props['name'] . "-" . $_name . "=" . $_value;
        }
        foreach ($this->params as $param) {
            if ($param != $_name) {
                if (isset($_GET[$this->props['name'] . "-" . $param]) && !in_array($param, $_resets)) {
                    $p[] = $this->props['name'] . "-" . $param . "=" . $_GET[$this->props['name'] . "-" . $param];
                }
            }
        }
        return $this->props['basic_url'] . implode("&", $p);
    }

    public function getHTML($_framework = 'bootstrap42')
    {


        if (strripos($this->props['query'], 'WHERE') === false) {
            $this->props['query'] .= " WHERE 1 ";
        }

        /* QUERY */
        $search = isset($_GET[$this->props['name'] . "-search"]) ? $_GET[$this->props['name'] . "-search"] : '';
        $search = urldecode($search);
        $search = DB::escape($search);
        $searchWhere = $search ? ' AND (' . str_replace("#s", $search, $this->props['search_where']) . ')' : "";

        /* FILTER */
        $filter = isset($_GET[$this->props['name'] . "-filter"]) ? intval($_GET[$this->props['name'] . "-filter"]) : '';
        $filterWhere = $filter !== '' && isset(array_values($this->props['filters_options'])[$filter]) ? ' AND (' . array_values($this->props['filters_options'])[$filter] . ')' : '';

        /* GROUP BY */
        $groupSQL = isset($this->props['group_by']) ? " GROUP BY " . $this->props['group_by'] : '';

        /* LIMIT */
        $currentPage = isset($_GET[$this->props['name'] . "-page"]) ? intval($_GET[$this->props['name'] . "-page"]) : 1;
        $limitSQL = " LIMIT " . (($currentPage - 1) * $this->props['max_per_page']) . ", " . $this->props['max_per_page'];

        /* ORDER BY */
        $orderSQL = isset($this->props['default_ord']) ? " ORDER BY " . $this->props['default_ord'] : '';
        $columnParts = isset($_GET[$this->props['name'] . "-ord"]) ? explode("-", $_GET[$this->props['name'] . "-ord"]) : [];
        $orderColumn = isset($columnParts[0]) ? $columnParts[0] : "";
        $orderDirection = isset($columnParts[1]) ? ($columnParts[1] == "asc" ? "asc" : "desc") : "asc";
        if (
            isset($_GET[$this->props['name'] . "-ord"]) &&
            isset($this->props['orderby_options']) &&
            array_search($_GET[$this->props['name'] . "-ord"],  array_values($this->props['orderby_options'])) !== false
        ) {
            $orderSQL = " ORDER BY " . DB::escape($orderColumn) . " " . $orderDirection;
        } else {
            $orderSQL = $orderColumn &&
                array_key_exists($orderColumn, $this->columns) &&
                (!isset($this->columns[$orderColumn]['sortable']) || (isset($this->columns[$orderColumn]['sortable']) &&
                    $this->columns[$orderColumn]['sortable'] == "true")) ? " ORDER BY " . DB::escape($orderColumn) . " " . $orderDirection : $orderSQL;
        }

        /* Busca todos os items sem LIMIT*/
        $items = DB::executeQuery($this->props['query'] . " " . $searchWhere . $filterWhere . $groupSQL);
        $itemsTotal = DB::getNumRows($items);



        /* Busca todos os items usando LIMIT e ORDER BY */
        $itemsLimited = DB::executeQuery($this->props['query'] . " " . $searchWhere . $filterWhere . $groupSQL . $orderSQL . $limitSQL);
        //$itemsLimitedTotal = DB::getNumRows($itemsLimited);

        /* Total de página encontradas */
        $totalPages = $itemsTotal > 0 ? ceil($itemsTotal / $this->props['max_per_page']) : 0;


        if ($_framework == 'bootstrap42') {

            /* --------------------------- Div Info ----------------------------------------------------------------- */
            $tableInfo = '';
            $dicols = 3;
            if (!$this->props['show_filters']) {
                $dicols += 2;
            }
            if (!isset($this->props['buttons'])) {
                $dicols += 4;
            }
            if (!$this->props['show_search']) {
                $dicols += 3;
            }
            if ($this->props['show_info']) {
                $tableInfo .= '<div class="my-2">' . EOL;
                $tableInfo .= '<div class="row mx-0 align-items-center ' . $this->props['info_class'] . '" id="' . $this->props['name'] . '-info">' . EOL;
                $tableInfo .= '<div class="col-12 px-0 col-sm-4 mb-2 mb-sm-1">' . EOL;
                if ($itemsTotal) {
                    $divInfoFrom = ((($currentPage - 1) * $this->props['max_per_page']) + 1);
                    $divInfoTo = ($currentPage * $this->props['max_per_page']);
                    $divInfoTotal = $itemsTotal;
                    $divInfoTo = $divInfoTo > $divInfoTotal ? $divInfoTotal : $divInfoTo;
                    $tableInfo .= ($divInfoTotal > $this->props['max_per_page'] ? '<strong>' . $divInfoFrom . '</strong>-<strong>' . $divInfoTo . '</strong> de ' : '') . '<strong>' . $divInfoTotal . '</strong> registro' . ($divInfoTotal > 1 ? 's' : '');
                }
                $tableInfo .= '</div>' . EOL;

                $tableInfo .= '<div class="col-12 px-0 col-sm-8 d-flex align-items-center justify-content-start justify-content-sm-end">' . EOL;
                if (isset($this->props['buttons'])) {
                    $tableInfo .= '<div class="mr-1">' . EOL;
                    $tableInfo .= $this->props['buttons'] . EOL;
                    $tableInfo .= '</div>' . EOL;
                }

                if ($this->props['show_filters']) {
                    $tableInfo .= '<div class="mr-1">' . EOL;
                    $options = "";
                    $co = 0;
                    $options .= '<option value="' . $this->genParams(null, null, ['filter', 'ord', 'page']) . '">Todos</option>';
                    foreach ($this->props['filters_options'] as $filterName => $filterWhere) {
                        $options .= '<option value="' . $this->genParams('filter', $co, ['ord', 'page']) . '" ' . (isset($_GET[$this->props['name'] . "-filter"]) && $_GET[$this->props['name'] . "-filter"] == $co ? 'selected="selected"' : '') . '>' . $filterName . '</option>';
                        $co++;
                    }
                    $tableInfo .= '
                        <select class="form-control" name="' . $this->props['name'] . '-filter" onchange="window.location.href = this.value;">' .
                        $options .
                        '</select>' . EOL;
                    $tableInfo .= '</div>' . EOL;
                }

                if (isset($this->props['show_orderby'])) {
                    $tableInfo .= '<div class="mr-1">' . EOL;
                    $options = "";
                    $options .= '<option value="' . $this->genParams(null, null, ['filter', 'ord', 'page']) . '">Ordenar</option>';
                    foreach ($this->props['orderby_options'] as $ordName => $ordOrd) {
                        $options .= '<option value="' . $this->genParams('ord', $ordOrd, []) . '" ' . (isset($_GET[$this->props['name'] . "-ord"]) && $_GET[$this->props['name'] . "-ord"] == $ordOrd ? 'selected="selected"' : '') . '>' . $ordName . '</option>';
                    }
                    $tableInfo .= '
                        <select class="form-control" name="' . $this->props['name'] . '-ord" onchange="window.location.href = this.value;">' .
                        $options .
                        '</select>' . EOL;
                    $tableInfo .= '</div>' . EOL;
                }

                if ($this->props['show_search']) {
                    $tableInfo .= '<div class="">' . EOL;
                    $tableInfo .= '
                                    <div class="input-group">
                                        <input class="form-control" type="text" id="' . $this->props['name'] . '-search" placeholder="' . (isset($this->props['search_placeholder']) ? $this->props['search_placeholder'] : '') . '" value="' . (isset($_GET[$this->props['name'] . "-search"])  ? $_GET[$this->props['name'] . "-search"] : '') . '">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" id="' . $this->props['name'] . '-search-btn" type="button"><i class="fa fa-search"></i></button>
                                        </div>
                                    </div>
                               ' . EOL;
                    $tableInfo .= '</div>' . EOL;
                }
                $tableInfo .= '</div>' . EOL;

                $tableInfo .= '</div>' . EOL;
                $tableInfo .= '</div>' . EOL;
            }
            /* --------------------------- Fim Div Info ------------------------------------------------------------- */

            /* --------------------------- Table HEAD --------------------------------------------------------------- */
            $tableHeader = '';
            if ($this->props['show_header']) {
                $tableHeader .= TAB . '<thead>' . EOL;
                $columnParts = [];
                if (isset($_GET[$this->props['name'] . "-ord"])) {
                    $columnParts = explode("-", $_GET[$this->props['name'] . "-ord"]);
                }
                $currentGetOrd = isset($columnParts[0]) ? $columnParts[0] : "";
                $currentGetDir = isset($columnParts[1]) ? ($columnParts[1] == "asc" ? "asc" : "desc") : "asc";

                foreach ($this->columns as $columnName => $columnProps) {

                    $specialColumn = substr($columnName, 0, 1) == '@' ? true : false;
                    $value = isset($columnProps['title']) ? $columnProps['title'] : '';
                    if ($specialColumn) {
                        if ($columnName == '@select') {
                            $value = "<input id=\"{$this->props['name']}-check-all\" type=\"checkbox\">";
                        }
                    } elseif (!isset($columnProps['sortable']) || (isset($columnProps['sortable']) && $columnProps['sortable'] == "true")) {

                        $orderIcon = $this->props['icon_sort_asc'];
                        $direction = "desc";
                        if (($currentGetOrd == $columnName) && ($currentGetDir == "desc")) {
                            $orderIcon = $this->props['icon_sort_desc'];
                            $direction = "asc";
                        }
                        $chref = $this->genParams("ord", $columnName . "-" . $direction, ['page']);
                        $value = $value ? TAB . TAB . TAB . '<a href="' . $chref . '">' . (isset($columnProps['title']) ? $columnProps['title'] : '') . $orderIcon . '</a>' . EOL : '';
                    }
                    $tableHeader .= TAB . TAB . "<th" . ($this->props2HtmlAttr($columnProps, '-')) . ($this->props2HtmlAttr($columnProps, '~')) . (isset($columnProps['tooltip']) ? " title='{$columnProps['tooltip']}'" : '') . ">{$value}</th>" . EOL;
                }

                $tableHeader .= TAB . '</thead>' . EOL;
            }
            /* --------------------------- Fim Table HEAD------------------------------------------------------------ */


            /* --------------------------- Table BODY --------------------------------------------------------------- */
            $tableBody = '';
            $tableBody .= TAB . '<tbody>' . EOL;
            if ($itemsTotal) {
                while ($row = DB::getRow($itemsLimited)) {

                    $objModel = null;
                    if (isset($this->props['model'])) {
                        $objModelClass = $this->props['model'];
                        $objModel = new $objModelClass($row[$this->props['id_name']]);
                        $objModel->mergeModelFields($row);
                    }

                    $rowRenderValue = '';
                    if (isset($this->props['custom_row_render'])) {
                        $customRowRender = $this->props['custom_row_render'];
                        $rowRenderValue = $customRowRender($objModel ? $objModel : $row);
                    }

                    $rowProps = '';
                    if (isset($this->props['row_props'])) {
                        $rowProps = $this->props['row_props'];
                    }

                    $tableBody .= TAB . TAB . "<tr $rowProps" . ($rowRenderValue ? " " . $rowRenderValue : "") . ">";
                    foreach ($this->columns as $columnName => $columnProps) {

                        $specialColumn = substr($columnName, 0, 1) == '@' ? true : false;

                        $value = !$specialColumn ? (isset($row[$columnName]) ? $row[$columnName] : '') : '';


                        if (isset($columnProps['model']) && !$specialColumn && $value) {
                            $mp = explode(";", $columnProps['model']);
                            if (count($mp) > 0) {
                                $mclass = $mp[0];
                                $mo = new $mclass($value);
                                if ($mo->exist() && $mo->fieldExist($mp[1])) {
                                    $method = camelize("get_{$mp[1]}");
                                    $value = $mo->$method();
                                }
                            }
                        }

                        if (isset($columnProps['options']) && !$specialColumn) {
                            foreach (explode(";", $columnProps['options']) as $opt) {
                                $optp = explode(":", $opt);
                                if (count($optp) > 1 && ($optp[0] == $value)) {
                                    $value = $optp[1];
                                }
                            }
                            // $value = $customRender($row);
                        } elseif (isset($columnProps['custom_render']) && !$specialColumn) {
                            $customRender = $columnProps['custom_render'];
                            $value = $customRender($objModel ? $objModel : $row);
                        } elseif ($specialColumn) {

                            $value = '';

                            if (isset($this->props['actions'])) {
                                $value .= $this->props['actions']($objModel ? $objModel : $row);
                            }

                            if ($columnName == '@select') {
                                $value = "<input type=\"checkbox\" class=\"{$this->props['name']}-check-item\" item-id=\"{$row[$this->props['id_name']]}\" name=\"multiSelect[]\">";
                            }
                        }



                        // if (isset($columnProps['repeat']) && ($value) && !$specialColumn) {
                        //     $value = str_repeat($value, $columnProps['repeat']);
                        // }

                        if (isset($columnProps['format']) && !$specialColumn) {
                            switch ($columnProps['format']) {
                                case 'monetary': {
                                        $value = Monetary::format($value);
                                        break;
                                    }
                                case 'date': {
                                        $value = strftime(isset($columnProps['format-props']) ? $columnProps['format-props'] : '%d/%m/%Y', strtotime($value));
                                        break;
                                    }
                                case 'datetime': {
                                        $value = strftime(isset($columnProps['format-props']) ? $columnProps['format-props'] : '%d/%m/%Y %T', strtotime($value));
                                        break;
                                    }
                                case 'uppercase': {
                                        $value = strtoupper($value);
                                        break;
                                    }
                                case 'lowercase': {
                                        $value = strtolower($value);
                                        break;
                                    }
                                case 'ucfirst': {
                                        $value = ucfirst($value);
                                        break;
                                    }
                                case 'ucwords': {
                                        $value = ucwords($value);
                                        break;
                                    }
                                case 'phone': {
                                        $value = Text::formatPhone($value);
                                        break;
                                    }
                                case 'image': {
                                        $imgSrc = isset($columnProps['img-path']) ? $columnProps['img-path'] . $value : $value;
                                        $imgWidth = isset($columnProps['img-width']) ? "width='{$columnProps['img-width']}'" : '';
                                        $imgHeight = isset($columnProps['img-height']) ? "height='{$columnProps['img-height']}'" : '';
                                        $value = "<img src='$imgSrc' $imgWidth $imgHeight>";
                                        break;
                                    }
                            }
                        }

                        if (isset($columnProps['render']) && !$specialColumn) {
                            if (!isset($columnProps['renderIf']) || ($columnProps['renderIf'] == 'true')) {
                                $render = $columnProps['render'];
                                $render = str_replace('{value}', $value, $render);
                                $value = Text::strCompile($render, $objModel ? $objModel : $row, $this->props['render_functions']);
                            }
                        }
                        // Renderiza somente se existir um valor.
                        // Ex. Imprimir <img src=##image##> somente se "image" tiver valor
                        if (isset($columnProps['renderNE']) && ($value) && !$specialColumn) {
                            $value = Text::strCompile($columnProps['renderNE'], $objModel ? $objModel : $row, $this->props['render_functions']);
                        }



                        $tableBody .= TAB . TAB . TAB . "<td" . ($this->props2HtmlAttr($columnProps, '_')) . ($this->props2HtmlAttr($columnProps, '~')) . ">" . EOL;
                        $tableBody .= TAB . TAB . TAB . TAB . $value . EOL;
                        $tableBody .= TAB . TAB . TAB . "</td>" .   EOL;
                    }

                    $tableBody .= TAB . TAB . "</tr>" . EOL;
                }
            } else {
                $tableBody .= TAB . TAB . '<tr>' . EOL;
                $tableBody .= TAB . TAB . '<td style="text-align: center;" colspan="' . count($this->columns) . '">Não foram encontrados itens</td>' . EOL;
                $tableBody .= TAB . TAB . '</tr>' . EOL;
            }
            $tableBody .= TAB . '</tbody>' . EOL;
            /* --------------------------- Fim Table BODY ----------------------------------------------------------- */


            /* --------------------------- Fim Table FOOT------------------------------------------------------------ */
            $tableFooter = '';
            $tableFooter .= TAB . '<tfoot>' . EOL;

            if (isset($this->columns['@select'])) {
                $tableFooter .= TAB . TAB . "<tr>" . EOL;
                $tableFooter .= TAB . TAB . "<td" . ($this->props2HtmlAttr($columnProps, '-')) . " colspan=\"" . (count($this->columns)) . "\">" . EOL;
                $tableFooter .= TAB . TAB . "<select class=\"form-control\" id=\"{$this->props['name']}-select-actions\" style=\"width: auto;\" disabled=\"disabled\">" . EOL;
                $tableFooter .= TAB . TAB . "<option value=\"\">Ações</option>" . EOL;
                // if (isset($this->columns['@select']['options'])) {
                //     $optStrParts = explode(';', $this->columns['@select']['options']);                
                //     foreach ($optStrParts as $optStrPart) {
                //         if (!$optStrPart) continue;
                //         $optStrPart = explode(':', $optStrPart);
                //         $actionUrl = isset($optStrPart[2]) ? $optStrPart[2] : '';
                //         $tableFooter .= TAB . TAB . "<option value=\"{$optStrPart[0]}\" action-url=\"{$actionUrl}\">{$optStrPart[1]}</option>" . EOL;
                //     }
                // }


                foreach ($this->props['select_options'] as $option) {
                    $tableFooter .= TAB . TAB . "<option value=\"{$option['url']}\" confirm-msg=\"{$option['confirm']}\">{$option['title']}</option>" . EOL;
                }

                $tableFooter .= TAB . TAB . "</select>" . EOL;
                $tableFooter .= TAB . TAB . "</td>" . EOL;
                $tableFooter .= TAB . TAB . "</tr>" . EOL;
            }

            $tableFooter .= TAB . '</tfoot>' . EOL;
            /* --------------------------- Fim Table FOOT------------------------------------------------------------ */


            /* --------------------------- Paginação ---------------------------------------------------------------- */
            $tablePagination = '';
            $m_pages = ceil(10 / 2);
            $ii = 1;
            $if = $totalPages;

            if (($currentPage - $m_pages) > 0) {
                $ii = $currentPage - $m_pages;
            }
            if (($currentPage + $m_pages) < $totalPages) {
                $if = $currentPage + $m_pages;
            }


            if ($totalPages > 1) {
                $tablePagination .= TAB . '<ul class="pagination justify-content-center">' . EOL;
                if ($currentPage > 1) {

                    $tablePagination .= TAB . TAB . '<li class="page-item"><a class="page-link" href="' . $this->genParams("page", ($currentPage - 1)) . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>' . EOL;
                }

                for ($p = $ii; $p <= $if; $p++) {
                    if ($currentPage == $p) {
                        $tablePagination .= TAB . TAB . '<li class="page-item active"><a class="page-link" href="#">' . $p . '<span class="sr-only">(current)</span></a></li>' . EOL;
                    } else {
                        $tablePagination .= TAB . TAB . '<li class="page-item"><a class="page-link" href="' . $this->genParams("page", $p) . '">' . $p . '</a></li>' . EOL;
                    }
                }

                if (($currentPage + 1) <= $totalPages) {
                    $tablePagination .= TAB . TAB . '<li class="page-item"><a class="page-link" href="' . $this->genParams("page", ($currentPage + 1)) . '" aria-label="Previous"><span aria-hidden="true">&raquo;</span></a></li>' . EOL;
                }
                $tablePagination .= '</ul>' . EOL . EOL;
            }
            /* --------------------------- Fim Paginação ------------------------------------------------------------ */


            /* --------------------------- Scripts ------------------------------------------------------------------ */
            $tableScripts = '';
            $tableScripts .= "
                <script>
                    $(function(){

                        $(\"#{$this->props['name']}-search-btn\").click(function(){
                            var search = $(\"#{$this->props['name']}-search\").val().trim();
                            var url = '" . ($this->genParams(null, null, ['search', 'ord', 'page'])) . ($this->genParams(null, null, ['search', 'ord', 'page']) == '?' ? '' : '&') . "';
                            if(search){
                                url += '" . $this->props['name'] . "-search=' + encodeURIComponent(search);
                            }
                            window.location.href= url;
                        });

                        $(\"#{$this->props['name']}-search\").keypress(function(e){
                            if(e.which == 13) {
                                $(\"#{$this->props['name']}-search-btn\").click();
                            }
                        });                        

                        $(\"#{$this->props['name']}-check-all\").change(function(){
                            $(\".tbl-check-item\").prop(\"checked\", $(this).is(\":checked\")); 
                            $(\".tbl-check-item\").change();
                        });  
                        $(\".{$this->props['name']}-check-item\").change(function(){
                            if($(\".{$this->props['name']}-check-item:checked\").length){
                                $(\"#{$this->props['name']}-select-actions\").prop(\"disabled\", false); 
                            }else{ 
                                $(\"#{$this->props['name']}-select-actions\").prop(\"disabled\", true); 
                            } 
                        }); 
		                $(\".{$this->props['name']}-check-item\").change();
		                $(\"#{$this->props['name']}-select-actions\").change(function(){
                            var option = $(\"option:selected\", this);
                            var url = option.val();
                            var confirmMsg = option.attr('confirm-msg') ? option.attr('confirm-msg') : 'Você tem certeza que deseja \"' + option.html() + '\" os itens selecionados?';
			                if(url && confirm(confirmMsg)){
                                var selecteds = [];
                                $(\".{$this->props['name']}-check-item:checked\").each(function(){
                                    selecteds.push($(this).attr(\"item-id\"));
                                })                 
                                $.ajax({
                                    url: url, method : \"POST\", cache: false, data: {ids: selecteds},
                                    success: function(response) {
                                        document.location.reload();
                                    }
                                });
                            } else {
                                $(\"#{$this->props['name']}-select-actions option\").eq(0).prop('selected', true);
                            }			
		                });		               
                    })               
                </script>" . EOL;
            /* --------------------------- Fim Scripts -------------------------------------------------------------- */

            $tableHTML = '';
            $tableHTML .= '<div class="table-responsive">' . EOL;
            $tableHTML .= $tableInfo . EOL;
            $tableHTML .= '<table class="' . $this->props['class'] . '">' . EOL;
            $tableHTML .= $tableHeader . EOL;
            $tableHTML .= $tableBody . EOL;
            $tableHTML .= $tableFooter . EOL;
            $tableHTML .= '</table>' . EOL;
            $tableHTML .= $tablePagination . EOL;
            $tableHTML .= $tableScripts . EOL;

            if (isset($this->props['buttons_footer'])) {
                $tableHTML .= '<div class="text-center border-top p-4">' . EOL;
                $tableHTML .= $this->props['buttons_footer'] . EOL;
                $tableHTML .= '</div>' . EOL;
            }

            $tableHTML .= '</div>' . EOL;

            return $tableHTML;
        }
        return '';
    }
}
