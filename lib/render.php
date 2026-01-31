<?php
//DEFINE GLOBALS
$ACP_LINK = "";
$INCLUDE_MOBILE = "";
$BACK_TO_GALLERY = '';
$forum_path = "";
$forum_links = "";
$categories = "";
$last_update = "";
//$OG_TAGS = "<!--";
$OG_TAGS = "";
$TOPICtitle = "";
$tags = "";
$tagsE = "";
$tags_js = "";
$links_list = "";
$prev_link = "";
$next_link = "";
$links_list_sub = "";
$forum_info = null;
$forum_links_tabs = "";
$first_image = "";


function render_logs($type)
{
    $logs = get_table_contents('log', 'ALL', " WHERE type='" . $type . "'");
    $ret = "";
    for ($i = 0; $i < count_null_as_zero($logs); $i++) {
        $ret .= '<tr><td><a href="../profile.php?uid=' . $logs[$i]['user_id'] . '">' . $logs[$i]['user'] . '</a></td><td>' . $logs[$i]['ip'] . '</td><td>' . $logs[$i]['action'] . '</td><td>' . $logs[$i]['message'] . '</td><td>' . $logs[$i]['time'] . '</td></tr>';
    }
    return $ret;
}


function render_acp_links()
{
    global $current_user, $site_settings, $ACP_LINK, $forum_id_const;
    if (has_permission_class($current_user['permissions']['global'], "a_")) {
        $ACP_LINK .= '<a class="lpadding white" href="./lib/acp.php">Administration Control Panel </a>';
    }
    if (has_permission_class($current_user['permissions']['global'], "m_")) {
        $ACP_LINK .= '<a class="lpadding white" href="./lib/mcp.php?f=' . $forum_id_const . '">Moderator Control Panel </a>';
    }
    if ($current_user['uid'] > 1 && $current_user['user']['user_password'] != "") {
        $ACP_LINK .= ' <a class="lpadding white" href="./lib/ucp.php">User settings </a> <img class="icon16" src="./theme/' . $site_settings['template'] . '/icons/settings.png">';
    }
}

function generate_og_tags()
{
    global $CURRENT_TOPIC, $forum_info, $site_settings, $NO_id, $topic_content, $pid_image, $forum_id_const;
    $protocol = $_SERVER['SERVER_PORT'] == '80' ? "http" : "https";
    //$_SERVER['HTTP_HOST']
    $path = explode('/', $_SERVER['SCRIPT_NAME']);
    array_pop($path);
    $path = implode('/', $path);
    $url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $path . "/";
    $baseurl = StringTrimRight($url, 1);
    if (isset($_GET['p'])) {
        $url .= '?p=' . $_GET['p'];
    } else if (isset($_GET['id']) && !$NO_id) {
        $url .= '?id=' . $_GET['id'];
    } else {
        $url .= '?f=' . $forum_id_const;
    }

    $meta = '<meta name="url" property="og:url" content="' . $url . '" />' . "\n";
    $meta .= ' <meta property="og:type"   content="article" /> ' . "\n";
    $meta .= '<meta name="title" property="og:title" content="' . $site_settings['site_name'] . " - " . $forum_info[0]['forum_name'] . '" />' . "\n";

    if (isset($_GET['p'])) {
        $post = post_get_info($_GET['p']);
        $postname = $post[0]['post_title'];
        $meta .= '<meta property="og:description" content="' . $postname . '">' . "\n";
    } else if (strlen($CURRENT_TOPIC[0]['title']) > 0 && !($NO_id && $forum_info[0]['forum_type'] == '2')) {
        $meta .= '<meta property="og:description" content="' . $CURRENT_TOPIC[0]['title'] . '">' . "\n";
    } else {
        $meta .= '<meta property="og:description" content="' . $forum_info[0]['description'] . '">' . "\n";
    }

    if ($forum_info[0]['forum_type'] == '2') {
        if (isset($_GET['p'])) {
            if (is_array($pid_image)) {
                foreach ($pid_image as $key => $value) {
                    if ($key == $_GET['p']) {
                        $meta .= '	<meta property="og:image" content="' . $baseurl . $value . '" />' . "\n";
                    }
                }
            }
        } else if (strlen($CURRENT_TOPIC[0]['title']) > 0 && !$NO_id) {
            $keys = array_keys($pid_image);
            $meta .= '	<meta property="og:image" content="' . $baseurl . $pid_image[$keys[0]] . '" />' . "\n";
        } else {
            global $thumbs;
            $meta .= '<meta property="og:image" content="' . $baseurl . '/images/large/' . $thumbs[0]['actual_name'] . '">' . "\n";
        }
    }
    return $meta;
}


function render_attachment($attachments, $post, $render_image = false, $render_navigation = false)
{
    global $site_settings, $root_dir, $forum_info, $prev_link, $next_link, $pid_image, $current_user, $first_image;
    $pid_image = array();
    $exif_file = load_template_file($root_dir . "/theme/" . $site_settings['template'] . "/exif.html");
    $exif_file = template_replace($exif_file, array());

    $attachments_tmp = "";
    $image = "";
    $exif_tmp = "";
    $nav = "";
    if ($render_navigation) {
        $nav = '<a class="nvgt" id="prev" href="' . $prev_link . '"><img class="icon24" src="' . $root_dir . '/theme/' . $site_settings['template'] . '/icons/previous.png"></a><a class="nvgt" id="next" href="' . $next_link . '"><img class="icon24" src="' . $root_dir . '/theme/' . $site_settings['template'] . '/icons/next.png"></a>';
    }

    $attachment_file = load_template_file($root_dir . "/theme/" . $site_settings['template'] . "/attachment.html");
    $image_found = false;
    if($attachments != null){
    for ($j = 0; $j < count_null_as_zero($attachments); $j++) {
        if ($forum_info['0']['forum_type'] == 2 && $attachments[$j]['is_image'] == 1 && !$image_found) {
            $image_found = true;
            $image_width = $attachments[$j]['width'];
            $image_height = $attachments[$j]['height'];
            $image = '<img id="post_img' . $post['id'] . '" onclick="fullscreenOfPost(' . $post['id'] . ')" class="thumb_large" src="./images/large/' . $attachments[$j]['actual_name'] . '" alt="{ALT}">';
            $image = '<div class="large_container"><div class="large_container_inner">' . $image . $nav . '</div></div>';
            $nav = "";
            $exif = json_decode($attachments[$j]['exif_info'], true);
            $pid_image[$posts[$i]['id']] = '/images/large/' . $attachments[$j]['actual_name'];
            if($first_image == ""){
               $first_image = '/images/large/' . $attachments[$j]['actual_name'];
            }
            $can_download = $site_settings['allow_download'] && has_permission($current_user['permissions'][$forum_info[0]['forum_id']], 'f_can_download') && has_permission($current_user['permissions']['global'],'u_download_files') || has_permission($current_user['permissions']['global'], 'a_manage_attachments');
            $replacements = array(
                '{date_taken}' => date($site_settings['time_format'], $exif['FileDateTime']),
                '{model}' => $exif['Model'],
                '{iso}' => $exif['ISOSpeedRatings'],
                '{post_id}' => $post['id'],
                '{exposure}' => $exif['ExposureTime'],
                '{aperture}' => $exif['ApertureFNumber'],
                '{focal_length}' => $exif['FocalLength'],
                '{focal_length_35mm}' => $exif['FocalLengthIn35mmFilm'],
                '{download_link}' => $attachments[$j]['id'],
                '{download_name}' => $attachments[$j]['file_name'],
                '{download_allowed}' => $can_download,
                '{size}' => bytes_to_size($attachments[$j]['size']),
                '{download_count}' => $attachments[$j]['downloads']
            );

            $exif_tmp = strtr($exif_file, $replacements);
            $exif_tmp = replace_if($exif_tmp);

        } else {
            $replacements = array(
                '{id}' => $attachments[$j]['id'],
                '{file_name}' => $attachments[$j]['file_name'],
                '{size}' => bytes_to_size($attachments[$j]['size']),
                '{downloads}' => $attachments[$j]['downloads']
            );
            $attachments_tmp .= strtr($attachment_file, $replacements);
        }
    }
    }
    if ($attachments_tmp != "<br><br>" && $attachments_tmp != "") {
        $attachments_tmp = "<br><br><b>Attachments:</b><br>" . $attachments_tmp;
    }

    return array(
        'attachments' => $attachments_tmp,
        'exif' => $exif_tmp,
        'image' => $image
    );
}


function render_thumbs($thumbs)
{
    global $thumbnails, $forum_id_const, $attachment_list, $CURRENT_TOPIC, $FORUM_ACTIONS, $acp_action, $site_settings, $POPULAR_TAGS, $forum_info;
    $thumbnails = "";
    //<a href="./?id='.$thumbs[$i]['topic_id'].'&f='.$forum_id_const.'"></a>
    if ($thumbs !== false) {
        for ($i = 0; $i < count_null_as_zero($thumbs); $i++) {
            if (!isset($thumbs[$i]['topic_id'])) {
                $thumbs[$i]['topic_id'] = $thumbs[$i]['id'];
            }
            $thumbnails .= '<div class="thumb"><div class="thumb_container"><a href="./?p=' . $thumbs[$i]['id'] . '"><img src="./images/small/' . $thumbs[$i]['actual_name'] . '" alt="' . $thumbs[$i]['post_title'] . " " . $thumbs[$i]['hashtags'] . '"></a></div><span class="post_time">By <a href="./profile.php?uid=' . $thumbs[$i]['user_id'] . '">' . $thumbs[$i]['username'] . '</a> on ' . date($site_settings['time_format'], $thumbs[$i]['time']) . '</span><br><strong class="img_title"><a href="./?p=' . $thumbs[$i]['id'] . '">' . $thumbs[$i]['post_title'] . '</a></strong><br>' . render_hashtags($thumbs[$i]['hashtags']) . '</div>';
        }
    }

    $tags = get_table_contents("", "", "", false, "SELECT tag FROM hashtags WHERE forum_id = '" . $forum_id_const . "' ORDER BY hit_count DESC LIMIT 0 , 10");

    $POPULAR_TAGS = "";
    if ($_GET['a'] != 'search' && $tags != null) {
        for ($i = 0; $i < count_null_as_zero($tags); $i++) {
            $POPULAR_TAGS .= '<a class="hashtag" href="./?a=search&type=' . $forum_info[0]['forum_type'] . '&q=' . $tags[$i]['tag'] . '">#' . str_replace("_", " ", $tags[$i]['tag']) . "</a> ";
        }
        $POPULAR_TAGS = '<div style="text-align: center;font-size: 20px;" >Popular tags: ' . $POPULAR_TAGS . '<br><br></div>';
    }
    $attachment_list = "var AttachmentList = [];";
    $CURRENT_TOPIC = topic_get_info($_GET['id']);
    $acp_action = "./theme/" . $site_settings['template'] . "/view_gallery_thumbs.html";

    if (!$thumbs) {
        $thumbnails = "There are no topics or posts in this forum.";
    }

    $FORUM_ACTIONS = forum_get_actions($forum_id_const, $_GET['id']);
}

function render_galleries($thumbs)
{
    global $galleries, $render_albums, $forum_id_const, $attachment_list, $CURRENT_TOPIC, $FORUM_ACTIONS, $acp_action, $site_settings, $POPULAR_TAGS, $forum_info;
    $galleries = "";
    if ($thumbs !== false) {
        $render_albums = count_null_as_zero($thumbs) > 0;
        for ($i = 0; $i < count_null_as_zero($thumbs); $i++) {
            if (!isset($thumbs[$i]['topic_id'])) {
                $thumbs[$i]['topic_id'] = $thumbs[$i]['id'];
            }
            $galleries .= '<div class="thumb album_thumb"><div class="thumb_container"><a href="./?f=' . $thumbs[$i]['forum_id'] . '"><img src="./images/small/' . $thumbs[$i]['actual_name'] . '" alt="' . $thumbs[$i]['post_title'] . " " . $thumbs[$i]['hashtags'] . '"></a></div><span class="post_time">By <a href="./profile.php?uid=' . $thumbs[$i]['user_id'] . '">' . $thumbs[$i]['username'] . '</a> on ' . date($site_settings['time_format'], $thumbs[$i]['time']) . '</span><br><strong class="img_title"><a href="./?f=' . $thumbs[$i]['forum_id'] . '">' . $thumbs[$i]['post_title'] . '</a></strong><br>' . render_hashtags($thumbs[$i]['hashtags']) . '</div>';
        }
    }

    if (!$thumbs) {
        $galleries = "";
    }

}

function render_hashtags($str)
{
    global $forum_info;
    if ($str == "") {
        return "";
    }
    $arr = explode(" ", $str);
    $ret = "";
    $type = isset($_GET['type']) ? $_GET['type'] : $forum_info[0]['forum_type'];
    for ($i = 0; $i < count_null_as_zero($arr); $i++) {
        $ret .= '<a class="hashtag" href="./?a=search&type=' . $type . '&q=' . $arr[$i] . '">#' . str_replace("_", " ", $arr[$i]) . "</a> ";
    }
    return $ret . "";
}

function render_likes($pid)
{
    global $current_user;
    $result =_mysql_prepared_query(array(
        "query" => "SELECT COUNT(*) AS c FROM likes WHERE post_id=:pid",
        "params" => array(
            ":pid" => $pid
        )
    ));
    $likes = _mysql_result($result, 0);
    if ($likes == "0") {
        return '<a class="lpadding" href="#" onclick="Like(this, ' . $likes . ', ' . $pid . ');return false;">Like</a>';
    } else {
        $result = _mysql_prepared_query(array(
            "query" => "SELECT COUNT(*) AS c FROM likes WHERE post_id=:pid AND user_id=:uid",
            "params" => array(
                ":pid" => $pid,
                ":uid" => $current_user['uid']
            )
        ));
        $has_liked = _mysql_result($result, 0);
        if ($has_liked == "0") {
            return $likes . ' people like this <a href="#" onclick="Like(this,' . $likes . ', ' . $pid . ');return false;">Like</a>';
        } else {
            if ($likes > 1) {
                return "You and " . ($likes - 1) . ' people like this <a class="lpadding" href="#" onclick="UnLike(this,' . $likes . ', ' . $pid . ');return false;">Unlike</a>';
            } else {
                return 'You like this <a href="#" onclick="UnLike(this,' . $likes . ', ' . $pid . ');">Unlike</a>';
            }
        }
    }
}

function render_mobile_css()
{
    global $INCLUDE_MOBILE, $root_dir, $site_settings;
    if (stristr($_SERVER['HTTP_USER_AGENT'], "mobile")) {
        $INCLUDE_MOBILE = '<link rel="stylesheet" type="text/css" href="' . $root_dir . '/theme/' . $site_settings['template'] . '/mobile.css"/>';
    }
}


function render_back_link()
{
    global $BACK_TO_GALLERY, $forum_id_const;
    if (isset($_GET['a']) && $_GET['a'] == 'search') {
        $BACK_TO_GALLERY = '';
    } else {
        $BACK_TO_GALLERY = '<div style="position: relative; z-index: 1; text-align: center;"><a style="font-size: 20px;" href="./?f=' . $forum_id_const . '">Back to gallery</a></div>';
    }
}

function render_forum_path()
{
    global $forum_path, $forum_links, $categories, $forum_id_const, $forum_info, $root_dir, $site_settings, $links_list_sub, $forum_links_tabs, $MODULE_TITLE, $PAGE_TITLE, $CURRENT_MODULE;
    $forum_info = forum_get_info($forum_id_const);
    if ($forum_info[0]["display"] == "0") {
        $forum_path .= $forum_info[0]["forum_name"];
    }

    $forum_path = forum_get_path_linked($forum_id_const);
    $forum_links_list = get_allowed_forums($forum_info[0]['parent_id']);
    for ($i = 0; $i < count_null_as_zero($forum_links_list); $i++) {
        if ($forum_links_list[$i]['forum_id'] == $forum_id_const) {
            $forum_path .= '<a id="selected_forum" href="./?f=' . $forum_links_list[$i]['forum_id'] . '">' . $forum_links_list[$i]['forum_name'] . '</a> ';
            $forum_links_tabs .= '<a id="selected_forum" href="./?f=' . $forum_links_list[$i]['forum_id'] . '">' . $forum_links_list[$i]['forum_name'] . '</a> ';
        } else {
            $forum_links_tabs .= '<a href="./?f=' . $forum_links_list[$i]['forum_id'] . '">' . $forum_links_list[$i]['forum_name'] . '</a> ';
        }
    }

    $forum_path = '<a href="' . $root_dir . '/">' . $site_settings['site_name'] . '</a> > ' . $forum_path;

    if(isset($MODULE_TITLE)){
        $forum_path .= $PAGE_TITLE[$CURRENT_MODULE] . ' > ' . $MODULE_TITLE;
    }

    $forum_path .= "<br>";

    if ($_GET['a'] == 'search') {
        $forum_path = '<script>document.getElementById("ACP_ACTIONS_MENU").style.display = "none";</script>';
    } else {
        $categories = render_navigation($links_list_sub, $forum_links);
    }

}

function render_last_update()
{
    global $last_update, $site_settings;
    $allowed_forums_list = array_copy_dimension(get_allowed_forums("-1", false, "ALL"), "forum_id");
    $result =_mysql_prepared_query(array(
        "query" => "SELECT GREATEST(time, edit_time) AS latest FROM post WHERE forum_id IN (:forum_list) ORDER BY latest DESC LIMIT 0,1",
        "params" => array(
            ":forum_list" => $allowed_forums_list
        )
    ));

    $last_update = @_mysql_result($result, 0);
    if (!$last_update) {
        $last_update = "Never";
    } else {
        $last_update = date($site_settings['time_format'], $last_update);
    }
}

function render_search()
{
    global $current_user, $forum_id_const, $links_list, $prev_link, $next_link, $links_list_sub, $topics, $forum_info;
    if ($_GET['a'] != 'search') {
        $topics = forum_get_allowed_topics($forum_id_const);

        $links_list = "";
        $prev_link = "#";
        $next_link = "#";


        $forum_list = get_allowed_forums($forum_id_const);
        $links_list_sub = count_null_as_zero($forum_list) > 0 ? "" : "None<br>";
        for ($i = 0; $i < count_null_as_zero($forum_list); $i++) {
            $links_list_sub .= '<a href="./?f=' . $forum_list[$i]['forum_id'] . '">' . $forum_list[$i]['forum_name'] . '</a><br>';
        }
        $links_list_sub .= "<br>";

        if ($topics != null) {
            $prev_link = str_replace(array(" ", "&"), array("_", "&amp;"), '?id=' . $topics[0]['topic_id']);
            $next_link = str_replace(array(" ", "&"), array("_", "&amp;"), '?id=' . $topics[0]['topic_id']);
            for ($i = 0; $i < count_null_as_zero($topics); $i++) {
                $add_item = false;
                $class = "";
                if ($topics[$i]['hidden'] == "0") {
                    $add_item = true;
                }
                if ($add_item == false && has_permission(array_merge_nulls_as_empty_array($current_user['permissions'][$forum_id_const], $current_user['permissions']['global']), 'f_view_hidden||m_hide_topic')) {
                    $add_item = true;
                    $class = "hidden";
                }
                if ($add_item) {
                    $current = '';
                    if ($topics[$i]['topic_id'] == $_GET['id']) {
                        $prev = $i == 0 ? count_null_as_zero($topics) - 1 : $i - 1;
                        $next = $i == (count_null_as_zero($topics) - 1) ? 0 : $i + 1;
                        $prev_link = str_replace(array(" ", "&"), array("_", "&amp;"), '?id=' . $topics[$prev]['topic_id']);
                        $next_link = str_replace(array(" ", "&"), array("_", "&amp;"), '?id=' . $topics[$next]['topic_id']);
                        $current = 'id="current_topic"';
                    }
                    $links_list .= '<a ' . $current . ' class="topic_type' . $topics[$i]['type'] . ' ' . $class . '" href="' . str_replace(array(" ", "&"), array("_", "&amp;"), '?id=' . $topics[$i]['topic_id']) . '">' . $topics[$i]['title'] . '</a><br/>' . "\n";
                }
            }
        }
    }
}

function render_navigation($links_list_sub, $forum_links)
{
    $ret = "";
    if ($links_list_sub != "None<br>") {
        $ret .= '<div class="sub_cats">Subcategories:<br> ' . $links_list_sub . '</div><br>';
    }
    return $ret;
}

function define_bbcodes()
{
    global $tags, $tagsE, $tags_js;
    $tags = get_table_contents("bbcode", 'ALL');
    $tagsE = array();

    for ($i = 0; $i < count_null_as_zero($tags); $i++) {
        if ($tags[$i]['bbcode_show']) {
            $tagsE[] = $tags[$i];
        }
    }
    $len = count_null_as_zero($tagsE);
    $code = 'bbcode["' . $len . '"] = [];
    bbcode["' . $len . '"]["bbcode_hint"] = "Code";
    bbcode["' . $len . '"]["bbcode"] = "[code={lang}]{text}[/code]";
    bbcode["' . $len . '"]["attrib_func"] = "selectLanguage";';
    $len++;
    $code .= 'bbcode["' . $len . '"] = [];
    bbcode["' . $len . '"]["bbcode_hint"] = "Attach";
    bbcode["' . $len . '"]["bbcode"] = "[attach=]";
    bbcode["' . $len . '"]["attrib_func"] = "selectAttach";';
    $tags_js = array_to_js($tagsE, 'bbcode', true, true);
    $tags_js .= $code;
}


function get_mod_tools()
{
    global $forum_id_const, $current_user, $not_locked, $language;
    $not_locked_str = $not_locked ? 'true' : 'false';
    $is_owner = topic_get_owner($_GET['id']) == $current_user['uid'] ? "true" : "false";
    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]),
        "(f_lock_own&&$is_owner)||m_hide_topic||m_lock_posts||m_move_posts||f_post_ann||f_post_sticky||m_delete_posts||(f_delete_own&&$is_owner&&$not_locked_str)")) {
        $form = '
    <form action="?id=' . $_GET['id'] . '&a=mod" method="post" style="padding-top: 3px;">
    ' . $language['ui']['actions'] . ':
    <select name="action">';
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "(f_lock_own&&$is_owner)||m_lock_posts")) {
            if (topic_is_locked($_GET['id']) == 1) {
                $form .= '<option value="unlock">Unlock topic</option>';
            } else {
                $form .= '<option value="lock">Lock topic</option>';
            }
        }
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "m_move_posts")) {
            $form .= '<option value="move">Move topic</option>';
        }
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "f_post_ann||f_post_sticky")) {
            $form .= '<option value="normal">Change to normal</option>';
        }
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "f_post_sticky")) {
            $form .= '<option value="sticky">Change to sticky</option>';
        }
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "f_post_ann")) {
            $form .= '<option value="announce">Change to announcement</option>';
        }
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "m_delete_posts||(f_delete_own&&$is_owner&&$not_locked_str)")) {
            $form .= '<option value="delete">Delete topic</option>';
        }
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "m_reorder")) {
            $form .= '<option value="reorder">Change posts order</option>';
        }
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "m_hide_topic")) {
            if (topic_is_hidden($_GET['id']) == 0) {
                $form .= '<option value="hide">hide topic</option>';
            } else {
                $form .= '<option value="unhide">Unhide topic</option>';
            }
        }
        $form .= '</select>
    <input type="submit" value="Go">
    </form>';
        return $form;
    } else {
        return '';
    }

}

//has_permission($current_user['permissions'][$forum_id_const],'U_VIEWhidden')
/*
'name' => Paarameter name,
'input_name' => name parameter value,
'description' => Parameter description,
'type' => checkbox, text, input or combo,
'data' => array defining combo elements,
'value' =>  value
 */
function build_form($form)
{
    $form_str = '<form method="' . $form[0]['method'] . '", action="' . $form[0]['url'] . '"><table>';
    for ($i = 1; $i < count($form); $i++) {
        $left = $form[$i]['name'];
        $right = "";
        if (isset($form[$i]['description'])) {
            $left .= '<br>' . $form[$i]['description'];
        }
        switch ($form[$i]['type']) {
            case 'checkbox':
                $checked = $form[$i]['value'] == '1' ? "checked" : '';
                $right .= '<input name="' . $form[$i]['input_name'] . '" type="checkbox" ' . $checked . '>';
                break;
            case 'text':
                $right .= '<textarea cols="80" rows="5" name="' . $form[$i]['input_name'] . '">' . $form[$i]['value'] . '</textarea>';
                break;
            case 'input':
                $right .= '<input name="' . $form[$i]['input_name'] . '" value="' . $form[$i]['value'] . '">';
                break;
            case 'combo':
                $right .= '<select name="' . $form[$i]['input_name'] . '">';
                foreach ($form[$i]['data'] as $key => $value) {
                    $selected = '';
                    if ($key == $form[$i]['value']) {
                        $selected = " selected";
                    }
                    $right .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
                }
                $right .= '</select>';
                break;
            default:
                $right = 'UNKNOWN TYPE ' + $form[$i]['type'];
        }

        $form_str .= '<tr><td>' . $left . '</td><td>' . $right . '</td></tr>';
    }
    $form_str .= '</table><input value="Reset" type="reset"> <input value="Save" type="submit"></form>';
    return $form_str;
}


function render_user_rank($user_rank_id, $user_rank, $user_rank_image, $group_rank_id, $group_rank, $group_rank_image)
{
    $rank = "";
    if ($user_rank_id == "0") {
        if ($group_rank != "") {
            return $group_rank . '<br><img src="./ranks/' . $group_rank_image . '">';
        }
    } else {
        if ($group_rank != "") {
            return $user_rank . '<br><img src="./ranks/' . $user_rank_image . '">';
        }
    }
    return '';
}

function render_user_link($user)
{
    $color = "inherit";
    if ($user['user_color'] != "") {
        $color = $user['user_color'];
    }
    return '<a style="color:' . $color . ' ;" href="./profile.php?u=' . $user['user_id'] . '">' . $user['username'] . '</a>';
}

function render_preview($a = ""){
    global $editor,$action,$language,$topic_data,$attachment_list,$acp_action,$notification,$tags,$site_settings, $notification_back_link;
    if($a != ""){
        $action = $a;
    }
    $editor = post_get_info($_GET['p']);
    if(form_is_valid($_GET['form'],$action)){
        $editor[0]['data'] = decode_input($_POST['Editor']);
        $editor[0]['post_title'] = decode_input($_POST['title']);
        $title_warning = "";
        if(strlen($editor[0]['post_title']) < 3){
            $title_warning = '<b style="color: #ff0000">ERROR: title too short</b>';
        }
        $topic_data = $title_warning.'<div><h2 style="margin: 0px 0px 1em 0px; padding: 0px;">'.$editor[0]['post_title'].'</h2></div>'. parse_bbcode($editor[0]['data'],bbcode_to_regex($tags,'bbcode','bbcode_html'),array(),true,true);
        $acp_action = "./theme/".$site_settings['template']."/ajaxpost.html";
        $post_where = "";
        if($_GET['p'] > 0 ){
            $post_where = "post_id=".$_GET['p']." OR";
        }
        $attachment_list = array_copy_dimension(get_table_contents("attachments","id", " WHERE ".$post_where ." form=".$_GET['form'] ),'id');
        $attachment_list = array_to_js($attachment_list,"AttachmentList");
    }else{
        $acp_action = "./theme/".$site_settings['template']."/ucp/failure_module.html";
        $notification = $language['notifications']['form'].$notification_back_link;
    }
}