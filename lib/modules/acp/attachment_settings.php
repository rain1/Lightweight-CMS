<?php
class attachment_settings{
    var $module_info = array(
        'title' => "logs",
        'MODULES' => array(
            array('name' => 'attachment_settings','permissions' => 'a_manage_attachment_settings'),
        )
    );

    function main($module){
        global $language;

        $lng = array(
            'general_labels' => $language['setting_name'],
            'general_descriptions' => $language['setting_description'],
        );

        $lng_js = array_to_js($lng,"lang",true, true);

        switch($module){
            case 'attachment_settings':
                if(isset($_GET['mode'])){
                    if($_GET['mode'] == "boardsettingssave"){
                        if(to_base_general("attach")){
                            $this->template = "success_module";
                            $this->vars=array(
                                'SUCCESSMSG' => "general settings have been successfully updated. <br><br><a href='./acp.php?id=".$_GET['id']."&a=".$_GET['a']."' style='color: #EEEEEE;'><b>Click here to go back</b></a>"
                            );
                        }else{
                            $this->template = "failure_module";
                            $this->vars=array(
                                'SUCCESSMSG' => "Failed to update general settings. Please retry. <br><br><a href='./acp.php?id=".$_GET['id']."&a=".$_GET['a']."' style='color: #EEEEEE;'><b>Click here to go back</b></a>"
                            );
                        }
                    }
                    break;
                }
                $this->page_title = $language['module_titles']['manage_attachments'];
                $this->template = "board_settings";
                $permissions = get_table_contents("general", 'ALL'," WHERE readonly=0 AND class='attach'");
                $js = array_to_js($permissions,"general",true, true);
                $this->vars=array(
                    'settings' => $js,
                    'LANGUAGE' => $lng_js,
                );
            break;
        }
    }
}