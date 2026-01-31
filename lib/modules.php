<?php
function module_list_childs($class = 'ANY', $id= 0,$inc_disabled = false)
{
    global $VALid_MODULE_NAMES;
    if(!in_array($class,$VALid_MODULE_NAMES)){
        return array();
    }
    $class_where = '';
    if($class != 'ANY'){
        $class_where = " AND class='".$class."'";
    }
    if($inc_disabled){
        $query = "SELECT * FROM modules WHERE parent_id = ".$id.$class_where ;
    }else{
        $query  = "SELECT * FROM modules WHERE parent_id = ".$id." AND enabled = 1".$class_where ;
    }
    $result = _mysql_query($query);
    $ret = array();
    while ($row = _mysql_fetch_assoc($result)) {
        $ret[] = array("module_name" => $row['module_name'],"module_id" => $row['module_id']);
    }
    _mysql_free_result($result);
    return $ret;
}


function module_list_all($class)
{
    global $VALid_MODULE_NAMES;
    if(!in_array($class,$VALid_MODULE_NAMES)){
        return array();
    }
    $result = _mysql_query("SELECT * FROM modules WHERE class='".$class."'");
    $ret = array();
    while ($row = _mysql_fetch_assoc($result)) {
        $ret[] = array(
            "module_name" => $row['module_name'],
            "module_id" => $row['module_id'],
            "enabled" => $row['enabled'],
            "parent_id" => $row['parent_id'],
            "type" => $row['type']
        );
    }
    _mysql_free_result($result);
    return $ret;
}


function module_get_tabs($class){
    global $ACTION_ALLOWED,$MODULE_NAME,$current_user,$MODULE_LIST,$forum_id_const ;
    $ret = module_list_childs($class);
    global $VALid_MODULE_NAMES;
    if(!in_array($class,$VALid_MODULE_NAMES)){
        return array();
    }
    for ($i = 0; $i < count($ret);$i++){
        $childs = module_list_childs($class, $ret[$i]['module_id']);
        if (count($childs) > 0){
            for ($j = 0; $j < count($childs);$j++){
                $module_name = strtolower($childs[$j]['module_name']);
                if( file_exists("./modules/".$class."/".$module_name .".php")){
                    include_once("./modules/".$class."/".$module_name .".php");
                    $module = NEW $module_name;
                    $permissions_merged = $current_user['permissions']['global'];
                    if($class == 'mcp'){
                        $permissions_merged = array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$forum_id_const]);
                    }
                    for($w = 0; $w < count($module->module_info['MODULES']); $w++)
                    {
                        if(has_permission($permissions_merged,$module->module_info['MODULES'][$w]['permissions']))//User chas permission to access module
                        {
                            if(! in_array($module_name,$MODULE_LIST) ){
                                $MODULE_LIST[] = $module_name;
                            }
                            $_modules[] = $module->module_info['MODULES'][$w];
                            if($_GET['a'] != '""'){
                                if($module->module_info['MODULES'][$w]['name'] == $_GET['a']){
                                    $ACTION_ALLOWED = true;
                                    $MODULE_NAME = $module_name;
                                }
                            }
                        }
                    }
                    $modules[] = array(
                        'module_id' => $ret[$i]['module_id'],
                        'module_name' => $ret[$i]['module_name'],
                        'Modules' => $_modules
                    );
                    unset($_modules);
                }
            }
        }
    }
    $clened = array();
    for($i = 0; $i < count($modules); $i++){
        if($modules[$i]['Modules'] != array()){
            $clened[] =  $modules[$i];
        }
    }
    return $clened;
}

function module_build_tabs($tab_info){
    global $language;
    $ids = array();
    for($i = 0; $i < count($tab_info); $i++)
    {
        if(!in_array($tab_info[$i]['module_id'],$ids)){
            $ret[] = array("link"=>$tab_info[$i]['module_id'],'text'=>$language['tabs'][$tab_info[$i]['module_name']]);
            $ids[] = $tab_info[$i]['module_id'];
        }
    }
    return array_to_js($ret,'Tabsdata',true, true);
}

function module_get_name_by_id($id){
    $res = _mysql_query("SELECT module_name FROM modules WHERE module_id='".$id."'");
    return _mysql_result($res, 0);
}