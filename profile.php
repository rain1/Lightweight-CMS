<?php
error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR | E_PARSE | E_USER_WARNING | E_USER_ERROR);
$notification_back_link = '<br> <a href="#" onclick="history.go(-1);">Go back</a> OR <a href="./">Go to board index</a>';

if (file_exists("settings.php")) {
    include_once "settings.php";
} else {
    die(file_get_contents("./install/InstallerReq.html"));
}

include_once "./lib/funcs.php";
include_once "./lib/groups.php";
include_once "./lib/users.php";
include_once "./globals.php";
include_once "./lib/login.php";
include_once "./lib/view_forum.php";
include_once "./lib/view_topic.php";
include_once "./lib/tobase.php";
include_once "./lib/bbcode.php";
include_once "./lib/permissions.php";
include_once "./lib/forms.php";
include_once "./lib/render.php";
include_once "./lib/lng/english.php";
include_once './lib/security.php';

sanitarize_input();
pre_checks();
define_user();
render_acp_links();
generate_og_tags();
render_mobile_css();
render_forum_path();
render_last_update();
define_bbcodes();

$login_form = get_login_form();
$load = array('File', 'if');
if (!has_permission($current_user['permissions']['global'], 'u_view_profiles')) {
    render_acp_links();
    generate_og_tags();
    render_mobile_css();
    $login_form = get_login_form();
    $load = array('File', 'if');
    $acp_action = "./theme/" . $site_settings['template'] . "/ucp/failure_module.html";
    $notification = "You do not have permission to view user profiles";
    $content = file_get_contents("./theme/" . $site_settings['template'] . "/main.html");
    $content = str_replace("{title}", $site_settings['site_name'], $content);
    $content = str_replace("{INJECT_FILE}", $acp_action, $content);
    $content = template_replace($content, $load);
    print ($content);
    exit();
}


$acp_action = "./theme/" . $site_settings['template'] . "/ucp/profile.html";
$notification = $language['notifications']['unknown_action'] . $notification_back_link;

$user = user_get_info_by_id($_GET['uid']);
$user[0]['user_rank'] = ranks_get_by_id($user[0]['user_rank']);
$user[0]['user_default_group'] = group_get_info_by_id($user[0]['user_default_group']);
$user[0]['user_rankimage'] = "";
$user[0]['user_email'] = str_replace("@", " [at] ", $user[0]['user_email']);
if ($user[0]['user_show_mail'] != "1") {
    $user[0]['user_email'] = "hidden";
}

$user[0]['user_facebook'] = '<a href="https://www.facebook.com/' . $user[0]['user_facebook'] . '">' . $user[0]['user_facebook'] . '</a>';
if ($user[0]['user_show_facebook'] != "1") {
    $user[0]['user_facebook'] = "hidden";
}

if ($user[0]['user_rank'][0]['image'] != "") {
    $user[0]['user_rankimage'] = '<img src="./ranks/' . $user[0]['user_rank'][0]['image'] . '"><br>';
}

if ($user[0]['user_avatar'] == "") {
    $user[0]['user_avatar'] = './theme/' . $site_settings['template'] . "/icons/default_profile.png";
}

$user[0]['about'] = parse_bbcode($user[0]['about'], bbcode_to_regex($tags, 'bbcode', 'bbcode_html'), array(), true, true);
$user[0]['user_signature'] = parse_bbcode($user[0]['user_signature'], bbcode_to_regex($tags, 'bbcode', 'bbcode_html'), array(), true, true);

$groups = user_get_groups_full($_GET['uid'], false);


$groups = group_remove_hidden_selective($groups);


$groups_str = "";
for ($i = 0; $i < count($groups); $i++) {
    if ($groups[$i]['color'] == "") {
        $groups[$i]['color'] = "inherit";
    }
    $groups_str .= '<a href="./memberlist.php?g=' . $groups[$i]['user_group_id'] . '" style="color: ' . $groups[$i]['color'] . ';" >' . $groups[$i]['name'] . '</a>, ';
}
$groups_str = StringTrimRight($groups_str, 2);
$user[0]['GroupList'] = $groups_str;
if ($user[0]['user_rank'] == null) {
    if ($user[0]['user_default_group'][0]['rank'] > 0) {
        $rank = ranks_get_by_id($user[0]['user_default_group'][0]['rank']);
        if ($rank[0]['image'] != "") {
            $user[0]['user_rankimage'] = '<img src="./ranks/' . $rank[0]['image'] . '"><br>';
        }
        $user[0]['user_rank'][0]['name'] = $rank[0]['name'];
    } else {
        $user[0]['user_rank'][0]['name'] = $user[0]['user_default_group'][0]['description'];
    }
}


$OG_TAGS .= generate_og_tags();

$content = file_get_contents("./theme/" . $site_settings['template'] . "/main.html");
$content = str_replace("{title}", $site_settings['site_name'], $content);
$content = str_replace("{INJECT_FILE}", $acp_action, $content);
$milliseconds2 = round(microtime(true) * 1000);
$generation_time = ($milliseconds2 - $milliseconds) / 1000;

$content = template_replace($content, $load);
print ($content);

