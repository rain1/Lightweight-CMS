<?php

$forum_links = null;
$allow_attachment = 'false';
$NO_id = !isset($_GET["id"]) ? true: false;
$commentS_enabled = 'true';
$not_locked = false;
$topics = null;

if (!function_exists('get_magic_quotes_gpc')) {
    function get_magic_quotes_gpc()
    {
        return (bool) ini_get('magic_quotes_gpc');
    }
}

function pre_checks(){
    global $allow_attachment, $current_user, $forum_id_const, $site_settings;
    if(get_magic_quotes_gpc() != false){
        display_error("Error", "Magic quotes must be turned off to run this software");
    }
    if(has_permission($current_user['permissions'][$forum_id_const],'f_can_attach')){
        $allow_attachment = 'true';
    }   
}

function check_empty_site(){
    global $forum_links, $site_settings, $login_form, $notification, $milliseconds, $generation_time, $last_update;
    if($forum_links==null && stristr($_SERVER["SCRIPT_FILENAME"], "index")){
        render_acp_links();
        generate_og_tags();
        render_mobile_css();
        $login_form = get_login_form();
        $load = array('File');
        $milliseconds2 = round(microtime(true) * 1000);
        $generation_time = ($milliseconds2 - $milliseconds)/1000;
        render_last_update();
        $acp_action = "./theme/".$site_settings['template']."/ucp/failure_module.html";
        $notification = "This board does not have any forums<br>Please if you think you see this message by accident, please contact board administrator.";;
        $content = file_get_contents("./theme/".$site_settings['template']."/main.html");
        $content = str_replace("{title}", $site_settings['site_name'], $content);
        $content = str_replace("{INJECT_FILE}",$acp_action,$content);
        $content = template_replace($content,$load);
        print($content);
        exit();
    }
}


function post_checks() {
    global $current_user, $forum_id_const, $NOTICE, $IS_BANNED, $allow_attachment, $not_locked;
    get_forum_id();
    $current_user['permissions'][$forum_id_const] = permissions_to_string(user_get_permissions($current_user['uid'],$forum_id_const));
    if(!has_permission($current_user['permissions'][$forum_id_const],"f_read_forum")){
        display_error("Error","You do not have permission to view this forum");
    }
    if(has_permission($current_user['permissions']['global'],'u_view_only')){
        error_push_title("Notice:");
        error_push_body("This account have been set to readonly therefore any changes you make will be lost.");
        $NOTICE = error_get();
        $_POST = array();
    }
    
    if($IS_BANNED){
        $_POST = array(); //make sure that banned cant make any 
    }
    
    if(has_permission($current_user['permissions'][$forum_id_const],'f_can_attach')){
        $allow_attachment = 'true';
    }
    
    $not_locked = topic_is_locked($_GET['id'])==0 ? true : false;
}



function get_forum_id(){
    global $forum_id_const, $forum_links, $topics;
    $forum_id_const = $forum_links[0]['forum_id'];
    if(isset($_GET['f'])){
        $forum_id_const = intval($_GET['f']);
    }
    if(!forum_exists($forum_id_const)){
        display_error("Error","Forum does not exist");
    }
    if((!isset($_GET['id']) || $_GET['id'] == '0' && count($_GET) < 3)
            && forum_get_type($forum_id_const) != '0'){ //forum
        $topics = forum_get_allowed_topics($forum_id_const,-1,-1,false);
        $_GET['id']=$topics[0]['topic_id'];
    }elseif(isset($_GET['id']) && $_GET['id'] != '0'){
        $forum_id_const = topic_get_forum($_GET['id']);
    }


    if(isset($_GET['p'])){
        if($_GET['p'] != '0'){
            $tmp = post_get_forum($_GET['p']);
            if($tmp != null){//if deleted
                $forum_id_const = $tmp;
                $topics = get_table_contents("","","",false,"SELECT topic_id FROM post WHERE id='".$_GET['p']."'");
                $_GET['id']=$topics[0]['topic_id'];
            }
        }
    }
}

function define_forum_permissions(){
    global $current_user, $forum_id_const;
    $current_user['permissions'][$forum_id_const] = permissions_to_string(user_get_permissions($current_user['uid'],$forum_id_const));
}

function secure_url($url) {
    return $url;
}

function secure_facebook($fb) {
    return $fb;
}