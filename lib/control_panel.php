<?php
//die(phpinfo());
//error_reporting(E_ALL);
error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR);
$OPT_NOLIST = true;
$tab_id = stripslashes(strip_tags($_GET["id"]));
$user_name = stripslashes(strip_tags($_GET["u"]));
$action = $_GET["a"];
$group = stripslashes(strip_tags($_GET["g"]));
$notification_back_link = '<br> <a href="#" onclick="location.href = document.referrer;">Go back</a> OR <a href="./">Go to board index</a>';
if (!function_exists("ConnectdataBase")) {
    include_once("../settings.php");
    include_once("./funcs.php");
    include_once("./lng/english.php");
    include_once "../globals.php";
    include_once("./groups.php");
    include_once("./users.php");
    include_once("./tobase.php");
    include_once("./view_forum.php");
    include_once("./view_topic.php");
    include_once("./modules.php");
    include_once("./security.php");
    include_once("./render.php");
    include_once("./permissions.php");
    include_once "./bbcode.php";
}


if (!$GLOBALS_DEFINED == true) {
    include_once("../globals.php");
}
if (!function_exists("CreateUser")) {
    include_once("./login.php");
}

//~ if($action == "") {$action = "user";}
$login_url = "./auth.php";
$NOTICE = "";
//$groupsTableHTML = groups_get_table_html();
$load = array('File');

$tab_file[0] = "general.html";
$tab_file[1] = "users.html";
$tab_file[2] = "images.html";
$tab_file[3] = "Style.html";

$action_file["user"] = "users_manage.html";
$action_file["group"] = "GroupsManage.html";
$action_file["group_permissions"] = "GropPermissions.html";
$action_file["groupmembers"] = "group_members.html";
//~ $action_file["banuser"] = "bansManage.html";
$action_file["banuser"] = "banuser.html";
$action_file["rank"] = "manage_ranks.html";
$action_file["adduser"] = "AddUser.html";
$acp_action = "welcome.html";
$VALid_MODULE_NAMES = array('ANY', 'acp', 'mcp', 'ucp');
$MODULE_LIST = array();

$MAIN_PAGE = array(
    'ucp' => '../theme/' . $site_settings['template'] . '/ucp/ucp.html',
    'mcp' => '../theme/' . $site_settings['template'] . '/mcp/mcp.html',
    'acp' => './acp/acp.html'
);

$PAGE_TITLE = array(
    'ucp' => 'User Control Panel',
    'mcp' => 'Moderation Control Panel',
    'acp' => 'Administration Control Panel'
);

$FILE_PATH = array(
    'ucp' => '../theme/' . $site_settings['template'] . '/ucp/' . $acp_action,
    'mcp' => '../theme/' . $site_settings['template'] . '/mcp/' . $acp_action,
    'acp' => './acp/' . $acp_action
);


if (!($_SERVER['HTTP_REFERER'] == "" || stristr($_SERVER["HTTP_REFERER"], $_SERVER["HTTP_HOST"]))) {
    display_error("warning", "It looks like you have been directed here from some other site.<br>For security reasons this software does not allow such requests. If you think that you see this message by accident please contact board administrator.<br><br><a href=\"../\">Go to board index.</a>");
}

//~ group_get_member_count(0);

if (!isset($_GET['a'])) {
    $_GET['a'] = 'None';
}

$ACTION_ALLOWED = false;
$MODULE_NAME = "";
$TEMPLATE_VARS = array();
$MODULE_TITLE = "NOT DEFINED";
sanitarize_input();

define_user();

$forum_id_const = 0;
if (isset($_GET['p'])) {
    if ($_GET['p'] != '0') {
        $tmp = post_get_forum($_GET['p']);
        if ($tmp != null) {//if deleted
            $forum_id_const = $tmp;
        }
    }
}

$current_user['permissions'][$forum_id_const] = array();
if ($forum_id_const != '0') {
    $current_user['permissions'][$forum_id_const] = permissions_to_string(user_get_permissions($current_user['uid'], $forum_id_const));
}


$login_form = get_login_form();
if (!has_permission_class($current_user['permissions']['global'], "a_") && $CURRENT_MODULE == "acp") {
    $FILE_PATH[$CURRENT_MODULE] = '../theme/' . $site_settings['template'] . '/ucp/failure_module.html';
    $notification = "You do not have any administrative permissions.<a href=\"../\">Go to board index</a>";
    load($MAIN_PAGE[$CURRENT_MODULE], $PAGE_TITLE[$CURRENT_MODULE], $FILE_PATH[$CURRENT_MODULE]);
    exit();
}

if (!has_permission_class(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "m_") && $CURRENT_MODULE == "mcp") {
    $FILE_PATH[$CURRENT_MODULE] = '../theme/' . $site_settings['template'] . '/ucp/failure_module.html';
    $notification = "You do not have any moderator permissions.<a href=\"../\">Go to board index</a>";
    load($MAIN_PAGE[$CURRENT_MODULE], $PAGE_TITLE[$CURRENT_MODULE], $FILE_PATH[$CURRENT_MODULE]);
    exit();
}

if (has_permission($current_user['permissions']['global'], 'u_view_only')) {
    error_push_title("Notice:");
    error_push_body("This account have been set to readonly therefore any changes you make will be lost.");
    $NOTICE = error_get();
    $_POST = array();
}

function xcp_get_link_collection($tab_info)
{
    global $language;
    for ($i = 0; $i < count($tab_info); $i++) {
        if ($tab_info[$i]['module_id'] == $_GET['id']) {
            for ($j = 0; $j < count($tab_info[$i]['Modules']); $j++) {
                $links[] = array("text" => $language['tab_menu'][$tab_info[$i]['Modules'][$j]['name']], "action" => $tab_info[$i]['Modules'][$j]['name']);
            }
        }
    }
    return array_to_js($links, 'ACP_LeftMenu', true, true);
}

function xcp_action($allowed, $action)
{
    global $notification, $acp_action, $MODULE_NAME, $TEMPLATE_VARS, $MODULE_TITLE;
    if ($action != 'None') {
        if ($allowed) {
            $module = NEW $MODULE_NAME;
            $module->main($action);
            $acp_action = $module->template . ".html";
            $TEMPLATE_VARS = $module->vars;
            $MODULE_TITLE = $module->page_title;
        } else {
            $notification = 'You do not have permission to access module "' . $MODULE_NAME . '".';
            $acp_action = "failure_module.html";
        }
    }
}

$module_collection = module_get_tabs($CURRENT_MODULE);
if ($_GET['a'] == 'None' && !isset($_GET['id'])) {
    if (count($module_collection) >= 1) {
        if (count($module_collection[0]['Modules']) >= 1) {
            $_GET['a'] = $module_collection[0]['Modules'][0]['name'];
            $_GET['id'] = $module_collection[0]['module_id'];
            $ACTION_ALLOWED = true;
            $MODULE_NAME = $MODULE_LIST[0];
        }
    }
}

if (!isset($NO_MODULE)) {//for special actions such as registration/login
    xcp_action($ACTION_ALLOWED, $_GET['a']);
}

if (!isset($_GET['id'])) {
    if (isset($_GET['a'])) {
        for ($i = 0; $i < count($module_collection); $i++) {
            for ($j = 0; $j < count($module_collection[$i]['Modules']); $j++) {
                if ($module_collection[$i]['Modules'][$j]['name'] == $_GET['a']) {
                    $_GET['id'] = $module_collection[$i]['module_id'];
                    break 2;
                }
            }
        }
    } else {
        $_GET['id'] = $module_collection[0]['module_id'];
    }
}

$TABS_DATA_JS = module_build_tabs($module_collection);
$menu_data_js = xcp_get_link_collection($module_collection);

$FILE_PATH = array(
    'ucp' => '../theme/' . $site_settings['template'] . '/ucp/' . $acp_action,
    'mcp' => '../theme/' . $site_settings['template'] . '/mcp/' . $acp_action,
    'acp' => './acp/' . $acp_action
);

$footer_text = parse_bbcode($site_settings['footer_text'], array(), array(), true, true);

function load($main_page, $title, $module_file)
{
    global $acp_action, $current_user, $CURRENT_MODULE, $load, $CONTENT_OUT;

    if (strlen($tab_id) < 1) {
        $tab_id = 1;
    }
    $content = file_get_contents($main_page);//Read xCP
    for ($i = 1; $i < 5; $i++) {//Draw tabs
        if ($tab_id == $i) {
            $content = str_replace("{SELECTED" . $i . "}", "Selected", $content);
        } else {
            $content = str_replace("{SELECTED" . $i . "}", "NotSelected", $content);
            $tab_text[$i] = '<a href="acp.php?id=' . $i . '">' . $tab_text[$i] . "</a>";
        }
    }
    $content = str_replace("{title}", $title, $content);

    if ($current_user['permissions']['global'] == null && $CURRENT_MODULE == 'acp') {
        list($acp_action, $notification) = success_message_ex(false,
            '',
            'You do not have any administrative permissions',
            '', './acp.php');
    }
    $content = str_replace("{FILE_PATH}", $module_file, $content);
    $content = template_replace(str_replace("{BODY}", $file_data, $content), $load);

    $CONTENT_OUT = $content;

}