<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: support@codono.com
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();

/**
 * HtmlTag library drive
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Taglib
 * @author    liu21st <liu21st@gmail.com>
 */
class TagLibHtml extends TagLib
{
    // labeldefinition
    protected $tags = array(
        // labeldefinition： attr Property list close It is closed (0 or1 default1) alias labelSlug level Nesting level
        'editor' => array('attr' => 'id,name,style,width,height,type', 'close' => 1),
        'select' => array('attr' => 'name,options,values,output,multiple,id,size,first,change,selected,dblclick', 'close' => 0),
        'grid' => array('attr' => 'id,pk,style,action,actionlist,show,datasource', 'close' => 0),
        'list' => array('attr' => 'id,pk,style,action,actionlist,show,datasource,checkbox', 'close' => 0),
        'imagebtn' => array('attr' => 'id,name,value,type,style,click', 'close' => 0),
        'checkbox' => array('attr' => 'name,checkboxes,checked,separator', 'close' => 0),
        'radio' => array('attr' => 'name,radios,checked,separator', 'close' => 0)
    );

    /**
     * editorTag resolution Insert visual editor
     * format: <html:editor id="editor" name="remark" type="FCKeditor" style="" >{$vo.remark}</html:editor>
     * @access public
     * @param string $attr Tag attributes
     * @return string|void
     */
    public function _editor($attr, $content)
    {
        $tag = $this->parseXmlAttr($attr, 'editor');
        $id = !empty($tag['id']) ? $tag['id'] : '_editor';
        $name = $tag['name'];
        $style = !empty($tag['style']) ? $tag['style'] : '';
        $width = !empty($tag['width']) ? $tag['width'] : '100%';
        $height = !empty($tag['height']) ? $tag['height'] : '320px';
        //   $content    =   $tag['content'];
        $type = $tag['type'];
        switch (strtoupper($type)) {
            case 'FCKEDITOR':
                $parseStr = '<!-- Editor calls the start --><script type="text/javascript" src="__ROOT__/Public/Js/FCKeditor/fckeditor.js"></script><textarea id="' . $id . '" name="' . $name . '">' . $content . '</textarea><script type="text/javascript"> var oFCKeditor = new FCKeditor( "' . $id . '","' . $width . '","' . $height . '" ) ; oFCKeditor.BasePath = "__ROOT__/Public/Js/FCKeditor/" ; oFCKeditor.ReplaceTextarea() ;function resetEditor(){setContents("' . $id . '",document.getElementById("' . $id . '").value)}; function saveEditor(){document.getElementById("' . $id . '").value = getContents("' . $id . '");} function InsertHTML(html){ var oEditor = FCKeditorAPI.GetInstance("' . $id . '") ;if (oEditor.EditMode == FCK_EDITMODE_WYSIWYG ){oEditor.InsertHtml(html) ;}else	alert( "FCKYou must be inWYSIWYGmode!" ) ;}</script> <!-- Editor call ended -->';
                break;
            case 'FCKMINI':
                $parseStr = '<!-- Editor calls the start --><script type="text/javascript" src="__ROOT__/Public/Js/FCKMini/fckeditor.js"></script><textarea id="' . $id . '" name="' . $name . '">' . $content . '</textarea><script type="text/javascript"> var oFCKeditor = new FCKeditor( "' . $id . '","' . $width . '","' . $height . '" ) ; oFCKeditor.BasePath = "__ROOT__/Public/Js/FCKMini/" ; oFCKeditor.ReplaceTextarea() ;function resetEditor(){setContents("' . $id . '",document.getElementById("' . $id . '").value)}; function saveEditor(){document.getElementById("' . $id . '").value = getContents("' . $id . '");} function InsertHTML(html){ var oEditor = FCKeditorAPI.GetInstance("' . $id . '") ;if (oEditor.EditMode == FCK_EDITMODE_WYSIWYG ){oEditor.InsertHtml(html) ;}else	alert( "FCKYou must be inWYSIWYGmode!" ) ;}</script> <!-- Editor call ended -->';
                break;
            case 'EWEBEDITOR':
                $parseStr = "<!-- Editor calls the start --><script type='text/javascript' src='__ROOT__/Public/Js/eWebEditor/js/edit.js'></script><input type='hidden'  id='{$id}' name='{$name}'  value='{$conent}'><iframe src='__ROOT__/Public/Js/eWebEditor/ewebeditor.htm?id={$name}' frameborder=0 scrolling=no width='{$width}' height='{$height}'></iframe><script type='text/javascript'>function saveEditor(){document.getElementById('{$id}').value = getHTML();} </script><!-- Editor call ended -->";
                break;
            case 'NETEASE':
                $parseStr = '<!-- Editor calls the start --><textarea id="' . $id . '" name="' . $name . '" style="display:none">' . $content . '</textarea><iframe ID="Editor" name="Editor" src="__ROOT__/Public/Js/HtmlEditor/index.html?ID=' . $name . '" frameBorder="0" marginHeight="0" marginWidth="0" scrolling="No" style="height:' . $height . ';width:' . $width . '"></iframe><!-- Editor call ended -->';
                break;
            case 'UBB':
                $parseStr = '<script type="text/javascript" src="__ROOT__/Public/Js/UbbEditor.js"></script><div style="padding:1px;width:' . $width . ';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript"> showTool(); </script></div><div><TEXTAREA id="UBBEditor" name="' . $name . '"  style="clear:both;float:none;width:' . $width . ';height:' . $height . '" >' . $content . '</TEXTAREA></div><div style="padding:1px;width:' . $width . ';border:1px solid silver;float:left;"><script LANGUAGE="JavaScript">showEmot();  </script></div>';
                break;
            case 'KINDEDITOR':
                $parseStr = '<script type="text/javascript" src="__ROOT__/Public/Js/KindEditor/kindeditor.js"></script><script type="text/javascript"> KE.show({ id : \'' . $id . '\'  ,urlType : "absolute"});</script><textarea id="' . $id . '" style="' . $style . '" name="' . $name . '" >' . $content . '</textarea>';
                break;
            default :
                $parseStr = '<textarea id="' . $id . '" style="' . $style . '" name="' . $name . '" >' . $content . '</textarea>';
        }

        return $parseStr;
    }

    /**
     * imageBtnTag resolution
     * format: <html:imageBtn type="" value="" />
     * @access public
     * @param string $attr Tag attributes
     * @return string|void
     */
    public function _imageBtn($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'imageBtn');
        $name = $tag['name'];                //name
        $value = $tag['value'];                //Writing
        $id = isset($tag['id']) ? $tag['id'] : '';                //ID
        $style = isset($tag['style']) ? $tag['style'] : '';                //Style Name
        $click = isset($tag['click']) ? $tag['click'] : '';                //Click on
        $type = empty($tag['type']) ? 'button' : $tag['type'];                //Push buttonTypes of

        if (!empty($name)) {
            $parseStr = '<div class="' . $style . '" ><input type="' . $type . '" id="' . $id . '" name="' . $name . '" value="' . $value . '" onclick="' . $click . '" class="' . $name . ' imgButton"></div>';
        } else {
            $parseStr = '<div class="' . $style . '" ><input type="' . $type . '" id="' . $id . '"  name="' . $name . '" value="' . $value . '" onclick="' . $click . '" class="button"></div>';
        }

        return $parseStr;
    }

    /**
     * imageLinkTag resolution
     * format: <html:imageLink type="" value="" />
     * @access public
     * @param string $attr Tag attributes
     * @return string|void
     */
    public function _imgLink($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'imgLink');
        $name = $tag['name'];                //name
        $alt = $tag['alt'];                //Writing
        $id = $tag['id'];                //ID
        $style = $tag['style'];                //Style Name
        $click = $tag['click'];                //Click on
        $type = $tag['type'];                //Click on
        if (empty($type)) {
            $type = 'button';
        }
        $parseStr = '<span class="' . $style . '" ><input title="' . $alt . '" type="' . $type . '" id="' . $id . '"  name="' . $name . '" onmouseover="this.style.filter=\'alpha(opacity=100)\'" onmouseout="this.style.filter=\'alpha(opacity=80)\'" onclick="' . $click . '" align="absmiddle" class="' . $name . ' imgLink"></span>';

        return $parseStr;
    }

    /**
     * selectTag resolution
     * format: <html:select options="name" selected="value" />
     * @access public
     * @param string $attr Tag attributes
     * @return string|void
     */
    public function _select($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'select');
        $name = $tag['name'];
        $options = $tag['options'];
        $values = $tag['values'];
        $output = $tag['output'];
        $multiple = $tag['multiple'];
        $id = $tag['id'];
        $size = $tag['size'];
        $first = $tag['first'];
        $selected = $tag['selected'];
        $style = $tag['style'];
        $ondblclick = $tag['dblclick'];
        $onchange = $tag['change'];

        if (!empty($multiple)) {
            $parseStr = '<select id="' . $id . '" name="' . $name . '" ondblclick="' . $ondblclick . '" onchange="' . $onchange . '" multiple="multiple" class="' . $style . '" size="' . $size . '" >';
        } else {
            $parseStr = '<select id="' . $id . '" name="' . $name . '" onchange="' . $onchange . '" ondblclick="' . $ondblclick . '" class="' . $style . '" >';
        }
        if (!empty($first)) {
            $parseStr .= '<option value="" >' . $first . '</option>';
        }
        if (!empty($options)) {
            $parseStr .= '<?php  foreach($' . $options . ' as $key=>$val) { ?>';
            if (!empty($selected)) {
                $parseStr .= '<?php if(!empty($' . $selected . ') && ($' . $selected . ' == $key || in_array($key,$' . $selected . '))) { ?>';
                $parseStr .= '<option selected="selected" value="<?php echo $key ?>"><?php echo $val ?></option>';
                $parseStr .= '<?php }else { ?><option value="<?php echo $key ?>"><?php echo $val ?></option>';
                $parseStr .= '<?php } ?>';
            } else {
                $parseStr .= '<option value="<?php echo $key ?>"><?php echo $val ?></option>';
            }
            $parseStr .= '<?php } ?>';
        } else if (!empty($values)) {
            $parseStr .= '<?php  for($i=0;$i<count($' . $values . ');$i++) { ?>';
            if (!empty($selected)) {
                $parseStr .= '<?php if(isset($' . $selected . ') && ((is_string($' . $selected . ') && $' . $selected . ' == $' . $values . '[$i]) || (is_array($' . $selected . ') && in_array($' . $values . '[$i],$' . $selected . ')))) { ?>';
                $parseStr .= '<option selected="selected" value="<?php echo $' . $values . '[$i] ?>"><?php echo $' . $output . '[$i] ?></option>';
                $parseStr .= '<?php }else { ?><option value="<?php echo $' . $values . '[$i] ?>"><?php echo $' . $output . '[$i] ?></option>';
                $parseStr .= '<?php } ?>';
            } else {
                $parseStr .= '<option value="<?php echo $' . $values . '[$i] ?>"><?php echo $' . $output . '[$i] ?></option>';
            }
            $parseStr .= '<?php } ?>';
        }
        $parseStr .= '</select>';
        return $parseStr;
    }

    /**
     * checkboxTag resolution
     * format: <html:checkbox checkboxes="" checked="" />
     * @access public
     * @param string $attr Tag attributes
     * @return string|void
     */
    public function _checkbox($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'checkbox');
        $name = $tag['name'];
        $checkboxes = $tag['checkboxes'];
        $checked = $tag['checked'];
        $separator = $tag['separator'];
        $checkboxes = $this->tpl->get($checkboxes);
        $checked = $this->tpl->get($checked) ? $this->tpl->get($checked) : $checked;
        $parseStr = '';
        foreach ($checkboxes as $key => $val) {
            if ($checked == $key || in_array($key, $checked)) {
                $parseStr .= '<input type="checkbox" checked="checked" name="' . $name . '[]" value="' . $key . '">' . $val . $separator;
            } else {
                $parseStr .= '<input type="checkbox" name="' . $name . '[]" value="' . $key . '">' . $val . $separator;
            }
        }
        return $parseStr;
    }

    /**
     * radioTag resolution
     * format: <html:radio radios="name" checked="value" />
     * @access public
     * @param string $attr Tag attributes
     * @return string|void
     */
    public function _radio($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'radio');
        $name = $tag['name'];
        $radios = $tag['radios'];
        $checked = $tag['checked'];
        $separator = $tag['separator'];
        $radios = $this->tpl->get($radios);
        $checked = $this->tpl->get($checked) ? $this->tpl->get($checked) : $checked;
        $parseStr = '';
        foreach ($radios as $key => $val) {
            if ($checked == $key) {
                $parseStr .= '<input type="radio" checked="checked" name="' . $name . '[]" value="' . $key . '">' . $val . $separator;
            } else {
                $parseStr .= '<input type="radio" name="' . $name . '[]" value="' . $key . '">' . $val . $separator;
            }

        }
        return $parseStr;
    }

    /**
     * listTag resolution
     * format: <html:grid datasource="" show="vo" />
     * @access public
     * @param string $attr Tag attributes
     * @return string
     */
    public function _grid($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'grid');
        $id = $tag['id'];                       //formID
        $datasource = $tag['datasource'];               //Listto showdatasourceVoListname
        $pk = empty($tag['pk']) ? 'id' : $tag['pk'];//Primary key Name,The default isid
        $style = $tag['style'];                    //Style Name
        $name = !empty($tag['name']) ? $tag['name'] : 'vo';                 //VoObjects Name
        $action = !empty($tag['action']) ? $tag['action'] : false;                   //whetherdisplayFeaturesoperating
        $key = !empty($tag['key']) ? true : false;
        if (isset($tag['actionlist'])) {
            $actionlist = explode(',', trim($tag['actionlist']));    //DesignationFeaturesList
        }

        if (substr($tag['show'], 0, 1) == '$') {
            $show = $this->tpl->get(substr($tag['show'], 1));
        } else {
            $show = $tag['show'];
        }
        $show = explode(',', $show);                //ListdisplayFieldList

        //ComputeformofRownumber
        $colNum = count($show);
        if (!empty($action)) $colNum++;
        if (!empty($key)) $colNum++;

        //displayStart
        $parseStr = "<!-- Think System components start list -->\n";
        $parseStr .= '<table id="' . $id . '" class="' . $style . '" cellpadding=0 cellspacing=0 >';
        $parseStr .= '<tr><td height="5" colspan="' . $colNum . '" class="topTd" ></td></tr>';
        $parseStr .= '<tr class="row" >';
        //Listneedto showField
        $fields = array();
        foreach ($show as $val) {
            $fields[] = explode(':', $val);
        }

        if (!empty($key)) {
            $parseStr .= '<th width="12">No</th>';
        }
        foreach ($fields as $field) {//displayDesignationofField
            $property = explode('|', $field[0]);
            $showname = explode('|', $field[1]);
            if (isset($showname[1])) {
                $parseStr .= '<th width="' . $showname[1] . '">';
            } else {
                $parseStr .= '<th>';
            }
            $parseStr .= $showname[0] . '</th>';
        }
        if (!empty($action)) {//in caseDesignationdisplayoperatingFeaturesRow
            $parseStr .= '<th >operating</th>';
        }
        $parseStr .= '</tr>';
        $parseStr .= '<volist name="' . $datasource . '" id="' . $name . '" ><tr class="row" >';    //stand bymouseA mobile unit line colorVariety specificMethodsjsDefined

        if (!empty($key)) {
            $parseStr .= '<td>{$i}</td>';
        }
        foreach ($fields as $field) {
            //displaydefinitionofListField
            $parseStr .= '<td>';
            if (!empty($field[2])) {
                // stand byListFieldlinkFeatures specificmethodbyJSfunctionachieve
                $href = explode('|', $field[2]);
                if (count($href) > 1) {
                    //DesignationlinkpassofField Values
                    // Support for multipleFieldtransfer
                    $array = explode('^', $href[1]);
                    if (count($array) > 1) {
                        foreach ($array as $a) {
                            $temp[] = '\'{$' . $name . '.' . $a . '|addslashes}\'';
                        }
                        $parseStr .= '<a href="javascript:' . $href[0] . '(' . implode(',', $temp) . ')">';
                    } else {
                        $parseStr .= '<a href="javascript:' . $href[0] . '(\'{$' . $name . '.' . $href[1] . '|addslashes}\')">';
                    }
                } else {
                    //If not specifieddefaultPass number values
                    $parseStr .= '<a href="javascript:' . $field[2] . '(\'{$' . $name . '.' . $pk . '|addslashes}\')">';
                }
            }
            if (strpos($field[0], '^')) {
                $property = explode('^', $field[0]);
                foreach ($property as $p) {
                    $unit = explode('|', $p);
                    if (count($unit) > 1) {
                        $parseStr .= '{$' . $name . '.' . $unit[0] . '|' . $unit[1] . '} ';
                    } else {
                        $parseStr .= '{$' . $name . '.' . $p . '} ';
                    }
                }
            } else {
                $property = explode('|', $field[0]);
                if (count($property) > 1) {
                    $parseStr .= '{$' . $name . '.' . $property[0] . '|' . $property[1] . '}';
                } else {
                    $parseStr .= '{$' . $name . '.' . $field[0] . '}';
                }
            }
            if (!empty($field[2])) {
                $parseStr .= '</a>';
            }
            $parseStr .= '</td>';

        }
        if (!empty($action)) {//displayFeaturesoperating
            if (!empty($actionlist[0])) {//displayDesignationofFeaturesitem
                $parseStr .= '<td>';
                foreach ($actionlist as $val) {
                    if (strpos($val, ':')) {
                        $a = explode(':', $val);
                        if (count($a) > 2) {
                            $parseStr .= '<a href="javascript:' . $a[0] . '(\'{$' . $name . '.' . $a[2] . '}\')">' . $a[1] . '</a>&nbsp;';
                        } else {
                            $parseStr .= '<a href="javascript:' . $a[0] . '(\'{$' . $name . '.' . $pk . '}\')">' . $a[1] . '</a>&nbsp;';
                        }
                    } else {
                        $array = explode('|', $val);
                        if (count($array) > 2) {
                            $parseStr .= ' <a href="javascript:' . $array[1] . '(\'{$' . $name . '.' . $array[0] . '}\')">' . $array[2] . '</a>&nbsp;';
                        } else {
                            $parseStr .= ' {$' . $name . '.' . $val . '}&nbsp;';
                        }
                    }
                }
                $parseStr .= '</td>';
            }
        }
        $parseStr .= '</tr></volist><tr><td height="5" colspan="' . $colNum . '" class="bottomTd"></td></tr></table>';
        $parseStr .= "\n<!-- Think System list of components end -->\n";
        return $parseStr;
    }

    /**
     * listTag resolution
     * format: <html:list datasource="" show="" />
     * @access public
     * @param string $attr Tag attributes
     * @return string
     */
    public function _list($attr)
    {
        $tag = $this->parseXmlAttr($attr, 'list');
        $id = $tag['id'];                       //formID
        $datasource = $tag['datasource'];               //Listto showdatasourceVoListname
        $pk = empty($tag['pk']) ? 'id' : $tag['pk'];//Primary key Name,The default isid
        $style = $tag['style'];                    //Style Name
        $name = !empty($tag['name']) ? $tag['name'] : 'vo';                 //VoObjects Name
        $action = $tag['action'] == 'true' ? true : false;                   //whetherdisplayFeaturesoperating
        $key = !empty($tag['key']) ? true : false;
        $sort = $tag['sort'] == 'false' ? false : true;
        $checkbox = $tag['checkbox'];                 //whetherdisplayCheckbox
        if (isset($tag['actionlist'])) {
            $actionlist = explode(',', trim($tag['actionlist']));    //DesignationFeaturesList
        }

        if (substr($tag['show'], 0, 1) == '$') {
            $show = $this->tpl->get(substr($tag['show'], 1));
        } else {
            $show = $tag['show'];
        }
        $show = explode(',', $show);                //ListdisplayFieldList

        //ComputeformofRownumber
        $colNum = count($show);
        if (!empty($checkbox)) $colNum++;
        if (!empty($action)) $colNum++;
        if (!empty($key)) $colNum++;

        //displayStart
        $parseStr = "<!-- Think System components start list -->\n";
        $parseStr .= '<table id="' . $id . '" class="' . $style . '" cellpadding=0 cellspacing=0 >';
        $parseStr .= '<tr><td height="5" colspan="' . $colNum . '" class="topTd" ></td></tr>';
        $parseStr .= '<tr class="row" >';
        //Listneedto showField
        $fields = array();
        foreach ($show as $val) {
            $fields[] = explode(':', $val);
        }
        if (!empty($checkbox) && 'true' == strtolower($checkbox)) {//in caseDesignationneeddisplaycheckboxRow
            $parseStr .= '<th width="8"><input type="checkbox" id="check" onclick="CheckAll(\'' . $id . '\')"></th>';
        }
        if (!empty($key)) {
            $parseStr .= '<th width="12">No</th>';
        }
        foreach ($fields as $field) {//displayDesignationofField
            $property = explode('|', $field[0]);
            $showname = explode('|', $field[1]);
            if (isset($showname[1])) {
                $parseStr .= '<th width="' . $showname[1] . '">';
            } else {
                $parseStr .= '<th>';
            }
            $showname[2] = isset($showname[2]) ? $showname[2] : $showname[0];
            if ($sort) {
                $parseStr .= '<a href="javascript:sortBy(\'' . $property[0] . '\',\'{$sort}\',\'' . ACTION_NAME . '\')" title="according to' . $showname[2] . '{$sortType} ">' . $showname[0] . '<eq name="order" value="' . $property[0] . '" ><img src="../Public/images/{$sortImg}.gif" width="12" height="17" border="0" align="absmiddle"></eq></a></th>';
            } else {
                $parseStr .= $showname[0] . '</th>';
            }

        }
        if (!empty($action)) {//in caseDesignationdisplayoperatingFeaturesRow
            $parseStr .= '<th >operating</th>';
        }

        $parseStr .= '</tr>';
        $parseStr .= '<volist name="' . $datasource . '" id="' . $name . '" ><tr class="row" ';    //stand bymouseA mobile unit line colorVariety specificMethodsjsDefined
        if (!empty($checkbox)) {
            $parseStr .= 'onmouseover="over(event)" onmouseout="out(event)" onclick="change(event)" ';
        }
        $parseStr .= '>';
        if (!empty($checkbox)) {//in caseneeddisplaycheckbox Then in each rowbeginningdisplaycheckbox
            $parseStr .= '<td><input type="checkbox" name="key"	value="{$' . $name . '.' . $pk . '}"></td>';
        }
        if (!empty($key)) {
            $parseStr .= '<td>{$i}</td>';
        }
        foreach ($fields as $field) {
            //displaydefinitionofListField
            $parseStr .= '<td>';
            if (!empty($field[2])) {
                // stand byListFieldlinkFeatures specificmethodbyJSfunctionachieve
                $href = explode('|', $field[2]);
                if (count($href) > 1) {
                    //DesignationlinkpassofField Values
                    // Support for multipleFieldtransfer
                    $array = explode('^', $href[1]);
                    if (count($array) > 1) {
                        foreach ($array as $a) {
                            $temp[] = '\'{$' . $name . '.' . $a . '|addslashes}\'';
                        }
                        $parseStr .= '<a href="javascript:' . $href[0] . '(' . implode(',', $temp) . ')">';
                    } else {
                        $parseStr .= '<a href="javascript:' . $href[0] . '(\'{$' . $name . '.' . $href[1] . '|addslashes}\')">';
                    }
                } else {
                    //If not specifieddefaultPass number values
                    $parseStr .= '<a href="javascript:' . $field[2] . '(\'{$' . $name . '.' . $pk . '|addslashes}\')">';
                }
            }
            if (strpos($field[0], '^')) {
                $property = explode('^', $field[0]);
                foreach ($property as $p) {
                    $unit = explode('|', $p);
                    if (count($unit) > 1) {
                        $parseStr .= '{$' . $name . '.' . $unit[0] . '|' . $unit[1] . '} ';
                    } else {
                        $parseStr .= '{$' . $name . '.' . $p . '} ';
                    }
                }
            } else {
                $property = explode('|', $field[0]);
                if (count($property) > 1) {
                    $parseStr .= '{$' . $name . '.' . $property[0] . '|' . $property[1] . '}';
                } else {
                    $parseStr .= '{$' . $name . '.' . $field[0] . '}';
                }
            }
            if (!empty($field[2])) {
                $parseStr .= '</a>';
            }
            $parseStr .= '</td>';

        }
        if (!empty($action)) {//displayFeaturesoperating
            if (!empty($actionlist[0])) {//displayDesignationofFeaturesitem
                $parseStr .= '<td>';
                foreach ($actionlist as $val) {
                    if (strpos($val, ':')) {
                        $a = explode(':', $val);
                        if (count($a) > 2) {
                            $parseStr .= '<a href="javascript:' . $a[0] . '(\'{$' . $name . '.' . $a[2] . '}\')">' . $a[1] . '</a>&nbsp;';
                        } else {
                            $parseStr .= '<a href="javascript:' . $a[0] . '(\'{$' . $name . '.' . $pk . '}\')">' . $a[1] . '</a>&nbsp;';
                        }
                    } else {
                        $array = explode('|', $val);
                        if (count($array) > 2) {
                            $parseStr .= ' <a href="javascript:' . $array[1] . '(\'{$' . $name . '.' . $array[0] . '}\')">' . $array[2] . '</a>&nbsp;';
                        } else {
                            $parseStr .= ' {$' . $name . '.' . $val . '}&nbsp;';
                        }
                    }
                }
                $parseStr .= '</td>';
            }
        }
        $parseStr .= '</tr></volist><tr><td height="5" colspan="' . $colNum . '" class="bottomTd"></td></tr></table>';
        $parseStr .= "\n<!-- Think System list of components end -->\n";
        return $parseStr;
    }
}