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
$forum_links = get_allowed_forums("0");
check_empty_site();
post_checks();
define_forum_permissions();

render_search();
render_acp_links();
generate_og_tags();
render_mobile_css();
render_back_link();
render_forum_path();
render_last_update();
define_bbcodes();

$login_form = get_login_form();
$load = array('File');
if (!has_permission($current_user['permissions']['global'], 'u_view_members')) {
    render_acp_links();
    generate_og_tags();
    render_mobile_css();
    $login_form = get_login_form();
    $load = array('File');
    $acp_action = "./theme/" . $site_settings['template'] . "/ucp/failure_module.html";
    $notification = "You do not have permission to view member list";
    $content = file_get_contents("./theme/" . $site_settings['template'] . "/main.html");
    $content = str_replace("{title}", $site_settings['site_name'], $content);
    $content = str_replace("{INJECT_FILE}", $acp_action, $content);
    $content = template_replace($content, $load);
    print($content);
    exit();
}


$acp_action = "./theme/" . $site_settings['template'] . "/ucp/memberlist.html";
$notification = $language['notifications']['unknown_action'] . $notification_back_link;

if (isset($_GET['g'])) {
    $user_list = get_table_contents("", "", "", False, "SELECT username, users.user_id, user_join_date, user_facebook, user_email, last_active, ranks.image, description, groups.name AS gname, ranks.name AS rname, user_show_mail, user_show_facebook, user_avatar, user_color, r2.image AS r2img, r2.name AS r2name, user_post_count, groups.rank AS grank FROM users
LEFT JOIN ranks
ON ranks.id = users.user_rank
LEFT JOIN groups
ON groups.id = users.user_default_group
LEFT JOIN ranks AS r2
ON groups.rank = r2.id
LEFT JOIN user_groups
ON user_groups.user_id = users.user_id
WHERE user_password != '' AND user_group_id = '" . $_GET['g'] . "'", array("user_join_date", "last_active"));
    $group_data = group_get_info_by_id($_GET['g']);
} else {
    $user_list = get_table_contents("", "", "", False, "SELECT username, user_id, user_join_date, user_facebook, user_email, last_active, ranks.image, description, groups.name AS gname, ranks.name AS rname, user_show_mail, user_show_facebook, user_avatar, user_color, r2.image AS r2img, r2.name AS r2name, user_post_count, groups.rank AS grank FROM users
LEFT JOIN ranks
ON ranks.id = users.user_rank
LEFT JOIN groups
ON groups.id = users.user_default_group
LEFT JOIN ranks AS r2
ON groups.rank = r2.id
WHERE user_password != ''", array("user_join_date", "last_active"));
}

$member_rows = "";

$root = ".";
if ($_GET["ajax"] == "1") {
    $root = "..";
}


for ($i = 0; $i < count($user_list); $i++) {

    $user_list[$i]['user_email'] = str_replace("@", " [at] ", $user_list[$i]['user_email']);
    $user_list[$i]['user_facebook'] = '<a href="https://www.facebook.com/' . $user_list[$i]['user_facebook'] . '">' . $user_list[$i]['user_facebook'] . '</a>';

    if ($user_list[$i]['user_show_facebook'] != "1") {
        $user_list[$i]['user_facebook'] = "hidden";
    }
    if ($user_list[$i]['user_show_mail'] != "1") {
        $user_list[$i]['user_email'] = "hidden";
    }
    if ($user_list[$i]['image'] != "") {
        $user_list[$i]['user_rankimage'] = '<img src="./ranks/' . $user_list[$i]['image'] . '">';
    } else {
        $user_list[$i]['user_rankimage'] = "";
    }
    if ($user_list[$i]['user_avatar'] == "") {
        $user_list[$i]['user_avatar'] = $root . '/theme/' . $site_settings['template'] . "/icons/default_profile.png";
    }
    $user_list[$i]['user_avatar'] = '<img style="max-width: 32px; max-height: 32px;" src="' . $user_list[$i]['user_avatar'] . '">';

    $user_list[$i]['rank'] = "";
    if ($user_list[$i]['rname'] != "") {
        $user_list[$i]['rank'] = $user_list[$i]['rname'];
    } else {
        if ($user_list[$i]['grank'] > 0) {
            $user_list[$i]['rank'] = $user_list[$i]['r2name'];
            if ($user_list[$i]['r2img'] != "") {
                $user_list[$i]['user_rankimage'] = '<img src="' . $root . '/ranks/' . $user_list[$i]['r2img'] . '">';
            }

        } else {
            if ($user_list[$i]['description'] != "") {
                $user_list[$i]['rank'] = $user_list[$i]['description'];
            }
        }
    }

    if ($user_list[$i]['user_rankimage'] != "") {
        $user_list[$i]['rank'] = $user_list[$i]['user_rankimage'] . "<br>" . $user_list[$i]['rank'];
    }

    if ($user_list[$i]['user_color'] == "") {
        $user_list[$i]['user_color'] = "inherit";
    }

    $select = "";
    $select_head = "";
    if (isset($_GET['mode'])) {
        if ($_GET['mode'] == "one") {
            $select = '<td><input type="button" value="Select" onclick="selectUser(\'' . str_replace("'", "\'", $user_list[$i]['username']) . '\')"></td>';
        } elseif ($_GET['mode'] == "many") {
            $select = '<td><input type="checkbox" name="' . str_replace('"', '\"', $user_list[$i]['username']) . '"></td>';
        }
    }


    $member_rows .= '<tr>' . $select . '<td>' . $user_list[$i]['user_avatar'] . '</td><td><a style="color: ' . $user_list[$i]['user_color'] . '" href="' . $root . '/profile.php?uid=' . $user_list[$i]['user_id'] . '">' . $user_list[$i]['username'] . '</a></td><td>' . $user_list[$i]['rank'] . '</td><td>' . $user_list[$i]['user_post_count'] . '</td><td>' . $user_list[$i]['user_join_date'] . '</td><td>' . $user_list[$i]['last_active'] . '</td><td>' . $user_list[$i]['user_email'] . '</td><td>' . $user_list[$i]['user_facebook'] . '</td></tr>';
}


$select_head = isset($_GET['mode']) ? "<td><b>Select</b></td>" : "";
$sel_btn = isset($_GET['mode']) && $_GET['mode'] == "many" ? '<button onclick="selectUsers();" >Select users</button>' : "";

$list_color = "inherit";
$list_name = "All members";

if (isset($_GET['g'])) {
    $list_name = $group_data[0]['name'];
    if ($group_data[0]['color'] != "") {
        $list_color = $group_data[0]['color'];
    }
}

if ($_GET["ajax"] == "1") {
    $content = file_get_contents("./theme/" . $site_settings['template'] . "/ucp/memberlist.html");
    $content = template_replace($content, $load);
    print($content);
    //line 790
} else {
    $OG_TAGS .= generate_og_tags();
    //end
    $content = file_get_contents("./theme/" . $site_settings['template'] . "/main.html");
    $content = str_replace("{title}", $site_settings['site_name'], $content);
    $content = str_replace("{INJECT_FILE}", $acp_action, $content);
    $milliseconds2 = round(microtime(true) * 1000);
    $generation_time = ($milliseconds2 - $milliseconds) / 1000;

    $content = template_replace($content, $load);
    print($content);
}

