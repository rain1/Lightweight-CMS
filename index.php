<?php
error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR | E_PARSE | E_USER_WARNING | E_USER_ERROR);
$post_params_form = '';

$notification_back_link = '<br> <a href="{%$redirect_to%}" >Go back</a> OR <a href="./">Go to board index</a>';


/**
 * Sets locations of 'Go back' and 'Go to board index'
 * @param string $go_back url of 'Go back' link
 * @param string $index url for 'Go to board index'
 * @return string
 */
function set_back_link($go_back = '', $index = '')
{
    global $notification_back_link;
    $lnk = '<br>';

    if ($go_back != '') {
        $lnk .= '<a href="' . $go_back . '">Go back</a>';
    } else {
        $lnk .= '<a href="#" onclick="location.href = document.referrer;">';
    }

    if ($index != '') {
        $lnk .= ' OR <a href="' . $index . '">Go to board index</a>';
    } else {
        $lnk .= ' OR <a href="./">Go to board index</a>';
    }
    $notification_back_link = $lnk;
    return $notification_back_link;
}


if (file_exists('settings.php')) {
    include_once 'settings.php';
} else {
    die(file_get_contents('./install/install.html'));
}

include_once './lib/funcs.php';
include_once './lib/groups.php';
include_once './lib/users.php';
include_once './globals.php';
include_once './lib/login.php';
include_once './lib/view_forum.php';
include_once './lib/view_topic.php';
include_once './lib/tobase.php';
include_once './lib/bbcode.php';
include_once './lib/permissions.php';
include_once './lib/forms.php';
include_once './lib/render.php';
include_once './lib/security.php';

if (!file_exists('./lib/lng/' . $site_settings['language'] . '.php')) {
    $site_settings['language'] = 'english';
}
include_once './lib/lng/' . $site_settings['language'] . '.php';
sanitarize_input();
pre_checks();
define_user();
$forum_links = get_allowed_forums('0');
check_empty_site();
post_checks();
render_back_link();

render_forum_path();
render_search();
render_acp_links();
generate_og_tags();
render_mobile_css();
render_last_update();
define_bbcodes();
$posts_js = '';
/**
 * Handles user actions
 */
function do_action()
{
    global $topic_data, $current_user, $forum_id_const, $site_settings, $acp_action, $language, $no_link, $yes_link, $confirm, $editor, $form_id, $notification, $title_warning, $not_locked, $target_forums, $tags, $attachment_list, $report_details, $msg, $notification_back_link, $forum_info, $posts_js, $attachment_list_ex, $lock_post, $post_params_form;
    $not_edit_locked = null;
    $post == null;
    if(isset($_GET['p'])){
        $post = post_get_info($_GET['p']);
        $not_edit_locked = $post[0]['edit_locked'] == '0';
    }
    switch ($_GET['a']) {
        case 'setorder':
            if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_reorder')) {
                post_set_order($_GET['p'], $_POST['order']);
                die('success');
            } else {
                die('fail');
            }
        case 'login':
            if ($forum_info[0]['forum_password'] == $_POST['password']) {
                $_SERVER['REQUEST_URI'] = str_replace('&a=login', '', $_SERVER['REQUEST_URI']);
                $sid = secure_input($_COOKIE['Session']);
                $result = @_mysql_prepared_query(array(
                    "query" => "SELECT session_id FROM sessions WHERE session_id = :sid",
                    "params" => array(
                        ":sid" => $sid
                    )
                ));
                $session_id = _mysql_result($result, 0);
                _mysql_prepared_query(array(
                    "query" => "INSERT INTO forum_session VALUES (:fid, :uid, :sid);",
                    "params" => array(
                        ":fid" => $forum_id_const,
                        ":uid" => $current_user['uid'],
                        ":sid" => $session_id
                    )
                ));
                die('Access granted<br>Click link belelow to continue: <a href=".' . $_SERVER['REQUEST_URI'] . '">.' . $_SERVER['REQUEST_URI'] . '</a>');
            } else {
                die('Access denied: wrong password.');
            }
            break;
        case 'preview':
            $action = 'postmessage';
            render_preview($action);
            break;
        case 'postmessage':
            $affected = post_action($_POST['title'], $_POST['Editor'], $_GET['id'], $current_user['uid'], $current_user['name'], $forum_id_const, $_GET['p'], $_POST['hashtags'], '0', true, true, true, true, $not_locked, $not_edit_locked, true, true, $_POST['lock']);
            if ($affected > 0) {
                $post_forum = post_get_forum($affected);
                _mysql_prepared_query(array(
                    "query" => "UPDATE attachments SET form=0, post_id=:pid, topic_id=:tid, forum_id=:fid WHERE form=:form_id",
                    "params" => array(
                        ":pid" => $affected,
                        ":tid" => post_get_topic($affected),
                        ":fid" => $post_forum,
                        ":form_id" => $_GET['form']
                    )
                ));
                $tags_list = explode(' ', $_POST['hashtags']);
                for ($i = 0; $i < count($tags_list); $i++) {
                    if ($tags_list[$i] != '') {
                        _mysql_prepared_query(array(
                            "query" => "INSERT INTO hashtags"
                                . " SELECT * FROM (SELECT DISTINCT :tag as tag, :fid as forum_id, '1' as hit_count, '1' as use_count) AS tmp"
                                . " WHERE NOT EXISTS ("
                                . " SELECT * FROM hashtags  WHERE  tag=:tag AND forum_id = :fid"
                                . ") LIMIT 1",
                            "params" => array(
                                ":tag" => $tags_list[$i],
                                ":fid" => $post_forum
                            )
                        ));
                    }
                }
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                $notification = $msg . set_back_link('./?p=' . $affected);
                redirect(3, './?f=' . $forum_id_const . '&p=' . $affected);
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification .= '<br> <a href="#" onclick="history.go(-1);">Go back</a> OR <a href="./">Go to board index</a>';
            }
            break;
        case 'edit' :
            $editor = post_get_info($_GET['p']);
            $editor[0]['data'] = decode_input($editor[0]['data']);
            $post[0]['data'] = decode_input($post[0]['data']);
            $topic_data = $title_warning . '<h2 style="margin: 0px 0px 1em 0px; padding: 0px;">' . $post[0]['post_title'] . '</h2><br>' . parse_bbcode($post[0]['data'], bbcode_to_regex($tags, 'bbcode', 'bbcode_html'), array(), true, true);
            if (topic_check_permissions('edit_post', $not_locked, $not_edit_locked, $post[0]['forum_id'])) {
                $acp_action = './theme/' . $site_settings['template'] . '/ajaxpost.html';
                $form_id = form_add('postmessage');
                $attachments = post_get_attachments($_GET['p']);
                $attachment_list = array_copy_dimension($attachments, 'id');
                $attachment_list = array_to_js($attachment_list, 'AttachmentList');
                $attachment_list_ex = array_to_js($attachments, 'AttachmentListEx', true, true);
                if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_reorder')) {
                    if ($editor[0]['edit_locked'] == '1') {
                        $lock_post = $language['ui']['lock_edit'] . ': <input checked="checked" name="lock" type="checkbox">';
                    } else {
                        $lock_post = $language['ui']['lock_edit'] . ': <input name="lock" type="checkbox">';
                    }
                }
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['edit'] . $notification_back_link;
            }
            break;
        case 'new':
            $editor = post_get_info($_GET['p']);
            $topic_data = '';
            $_GET['p'] = 0;
            $_GET['id'] = 0;
            $attachment_list = 'var AttachmentList = [];';
            $attachment_list_ex = 'var AttachmentListEx = [];';
            if (has_permission($current_user['permissions'][$forum_id_const], 'f_start_new')) {
                $form_id = form_add('postmessage');
                $acp_action = './theme/' . $site_settings['template'] . '/ajaxpost.html';
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['new'] . $notification_back_link;
            }
            if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_reorder')) {
                $lock_post = $language['ui']['lock_edit'] . ': <input name="lock" type="checkbox">';
            }
            break;
        case 'reply':
            $editor = topic_get_info($_GET['id']);
            $editor[0]['post_title'] = 'Re: ' . $editor[0]['title'];
            $topic_data = '';
            if ($not_locked && has_permission($current_user['permissions'][$forum_id_const], 'f_can_reply')) {
                $form_id = form_add('postmessage');
                $acp_action = './theme/' . $site_settings['template'] . '/ajaxpost.html';
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['reply'] . $notification_back_link;
            }
            break;
        case 'approve':
            if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_approve_posts')) {
                if (isset($_GET['p'])) {
                    post_approve($_GET['p']);
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $notification = $language['notifications']['approve_success'] . $notification_back_link;
                    redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                }
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['approve'] . $notification_back_link;
            }
            break;
        case 'viewreport':
            if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_close_reports||f_can_report')) {
                if (isset($_GET['p'])) {
                    $report_details = post_view_report($_GET['p']);
                    if ($report_details) {
                        $report_details['reporter'] = user_get_info_by_id($report_details['reporter']);
                        $acp_action = './theme/' . $site_settings['template'] . '/view_report.html';
                    } else {
                        $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                        $notification = $language['notifications']['fail_view_report'] . $notification_back_link;
                    }
                }
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['close_report'] . $notification_back_link;
            }
            break;
        case 'report':
            if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_close_reports||f_can_report')) {
                if (isset($_GET['p'])) {
                    if (isset($_POST['report_msg'])) {
                        if ($_POST['report_msg'] != '') {
                            if (post_open_report($_GET['p'], $_POST['report_msg'])) {
                                $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                                $notification = $language['notifications']['report_success'] . $notification_back_link;
                                redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                            } else {
                                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                                $notification = $language['notifications']['fail_report'] . $notification_back_link;
                            }
                        } else {
                            $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                            $notification = $language['notifications']['empty_report'] . $notification_back_link;
                        }
                    } else {
                        $acp_action = './theme/' . $site_settings['template'] . '/forms/report.html';
                    }
                }
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['report'] . $notification_back_link;
            }
            break;
        case 'closereport':
            if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_close_reports')) {
                if (isset($_GET['p'])) {
                    if (post_report_close($_GET['p'])) {
                        $name = post_get_info($_GET['p']);
                        $name = $name[0]['post_title'];
                        log_event('MODERATOR', $current_user['name'], $_SERVER['REMOTE_ADDR'], 'CLOSE REPORT', 'Closed report for post <a href="../?p=' . $_GET['p'] . '">' . $name . '</a></a>');
                        $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                        $notification = $language['notifications']['report_close_success'] . $notification_back_link;
                        redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    } else {
                        $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                        $notification = $language['notifications']['fail_close_report'] . $notification_back_link;
                    }
                }
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['close_report'] . $notification_back_link;
            }
            break;
        case 'delete':
            if (has_permission($current_user['permissions'][$forum_id_const], 'f_delete_own')
                && post_get_owner($_GET['p']) == $current_user['uid']
                && $not_locked
                && $not_edit_locked
                || has_permission($current_user['permissions']['global'], 'm_delete_posts')
                || has_permission($current_user['permissions'][$forum_id_const], 'm_delete_posts')
            ) {
                if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
                    $topic_id = post_get_topic_id($_GET['p']);
                    $ret = post_delete($_GET['p']);
                    $topic_exists = topic_exists($topic_id);
                    $url = './';
                    if ($ret == 0) {
                        if ($topic_exists) {
                            $url = str_replace('&a=delete', '', '?id=' . $topic_id);
                        } else {
                            $url = str_replace('&a=delete', '', '?f=' . $forum_id_const);
                        }
                        $url = str_replace('&confirm=yes', '', $url);
                    }
                    $question_mark = stristr($url, '?') ? '' : '?';
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $back_link = set_back_link('./?id=' . $topic_id);
                    if (!$topic_exists) {
                        $back_link = set_back_link('./?f=' . $forum_id_const);
                    }
                    $notification = $language['notifications']['post_delete'] . $back_link;
                    redirect(3, '' . $url . $question_mark . '&f=' . $forum_id_const);
                } else {
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/confirm.html';
                    $confirm = 'Are you sure you want to delete this post?';
                    $no_link = $_SERVER['HTTP_REFERER'];
                    $yes_link = $_SERVER['REQUEST_URI'] . '&confirm=yes';
                }
            } else {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['delete_denied'] . $notification_back_link;
            }
            break;
        case 'mod':
            switch ($_POST['action']) {
                case 'reorder':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_reorder')) {
                        $acp_action = './theme/' . $site_settings['template'] . '/reorder.html';
                        $posts = topic_get_data($_GET['id']);
                        for ($i = 0; $i < count($posts); $i++) {
                            $posts[$i]['data'] = StringLeft($posts[$i]['data'], 200);
                        }
                        $posts_js = array_to_js($posts, 'posts', true, true);
                    } else {
                        $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                        $notification = $language['notifications']['reorder_fail'] . $notification_back_link;
                        redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    }
                    break;
                case 'hide':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_hide_topic')) {
                        topic_hide($_GET['id'], 1);
                    }
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $notification = $language['notifications']['hide_success'] . $notification_back_link;
                    redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    break;
                case 'unhide':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_hide_topic')) {
                        topic_hide($_GET['id'], 0);
                    }
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $notification = $language['notifications']['unhide_success'] . $notification_back_link;
                    redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    break;
                case 'lock':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_lock_posts')) {
                        topic_lock($_GET['id'], 1);
                    } elseif (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'f_lock_own')) {
                        if (topic_get_owner($_GET['id']) == $current_user['uid']) {
                            topic_lock($_GET['id'], 1);
                        }
                    }
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $notification = $language['notifications']['lock_success'] . $notification_back_link;
                    redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    break;
                case 'unlock':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_lock_posts')) {
                        topic_lock($_GET['id'], 0);
                    } elseif (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'f_lock_own')) {
                        if (topic_get_owner($_GET['id']) == $current_user['uid']) {
                            topic_lock($_GET['id'], 0);
                        }
                    }
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $notification = $language['notifications']['unlock_success'] . $notification_back_link;
                    redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    break;
                case 'move':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_move_posts')) {
                        if (isset($_POST['fid']) && intval($_POST['fid']) > 0) {
                            $allowed_forums = get_allowed_forums('-1', true);
                            $is_allowed = false;
                            for ($i = 0; $i < count($allowed_forums); $i++) {
                                if ($allowed_forums[$i]['forum_id'] == $_POST['fid']) {
                                    $is_allowed = true;
                                    break;
                                }
                            }
                            if ($is_allowed) {
                                $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                                $notification = $language['notifications']['move_success'] . $notification_back_link;
                                topic_set_forum($_GET['id'], $_POST['fid']);
                                topic_move_attachments($_GET['id'], $_POST['fid']);
                                redirect(3, './?f=' . $_POST['fid'] . '&id=' . $_GET['id']);
                            } else {
                                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                                $notification = $language['notifications']['move_fail'] . $notification_back_link;
                                redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                            }
                        } else {
                            $target_forums = get_allowed_forums_combo();
                            $acp_action = './theme/' . $site_settings['template'] . '/forms/move_topic.html';
                        }

                    }
                    break;
                case 'normal':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'f_post_ann||f_post_sticky')) {
                        topic_set_type($_GET['id'], 0);
                    }
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $notification = $language['notifications']['set_normal'] . $notification_back_link;
                    redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    break;
                case 'sticky':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'f_post_sticky')) {
                        topic_set_type($_GET['id'], 1);
                    }
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $notification = $language['notifications']['set_sticky'] . $notification_back_link;
                    redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    break;
                case 'announce':
                    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'f_post_ann')) {
                        topic_set_type($_GET['id'], 2);
                    }
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                    $notification = $language['notifications']['set_ann'] . $notification_back_link;
                    redirect(3, './?f=' . $forum_id_const . '&id=' . $_GET['id']);
                    break;
                case 'delete':
                    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
                        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'm_delete_posts')) {
                            topic_delete($_GET['id']);
                        } elseif (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), 'f_delete_own')) {
                            if ($not_locked && topic_get_owner($_GET['id']) == $current_user['uid']) {
                                topic_delete($_GET['id']);
                            }
                        }
                        $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                        $notification = $language['notifications']['topic_delete'] . set_back_link('./?f=' . $forum_id_const);
                        redirect(3, './?f=' . $forum_id_const);
                    } else {
                        $acp_action = './theme/' . $site_settings['template'] . '/ucp/confirm_post.html';
                        $confirm = 'Are you sure you want to delete this topic?';
                        $no_link = $_SERVER['HTTP_REFERER'];
                        $yes_link = $_SERVER['REQUEST_URI'] . '&confirm=yes';
                        $post_params_form = post_to_form();
                    }
                    break;
                default:
                    $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                    $notification = $language['notifications']['unknown_action'] . $notification_back_link;
                    break;

            }
            break;
        case 'search':
            include_once './lib/search.php';
            break;
        case 'solve':
            $topic_owner = 'false';
            if (topic_get_owner(post_get_topic($_GET['p'])) == $current_user['uid']) {
                $topic_owner = 'true';
            }
            if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), '((f_mark_solved&&' . $topic_owner . ')||m_mark_solved)&&' . $post[0]['solved'] . '==0')) {
                post_set_solved($post[0]['id'], '1');
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                $notification = $language['notifications']['solve_success'] . $notification_back_link;
                redirect(3, './?f=' . $forum_id_const . '&p=' . $post[0]['id']);
            }
            break;
        case 'unsolve':
            $topic_owner = 'false';
            if (topic_get_owner(post_get_topic($_GET['p'])) == $current_user['uid']) {
                $topic_owner = 'true';
            }
            if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), '((f_mark_solved&&' . $topic_owner . ')||m_mark_solved)&&' . $post[0]['solved'] . '==1')) {
                post_set_solved($post[0]['id'], '0');
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/success_module.html';
                $notification = $language['notifications']['unsolve_success'] . $notification_back_link;
                redirect(3, './?f=' . $forum_id_const . '&p=' . $post[0]['id']);
            }
            break;
        default:
            if (isset($_GET['a'])) {
                $acp_action = './theme/' . $site_settings['template'] . '/ucp/failure_module.html';
                $notification = $language['notifications']['unknown_action'] . $notification_back_link;
            }
            break;
    }
}


$login_form = get_login_form();
$load = array('File', 'if');
$include = false;
if ($forum_info[0]['forum_password'] == '') {
    $include = true;
} else {
    if (
        (isset($_GET['f']) && $_GET['f'] > 0) ||
        (!isset($_GET['f']) &&
            (isset($_GET['p']) || isset($_GET['id']))
        )
    ) {
        $sid = secure_input($_COOKIE['Session']);
        $result = @_mysql_prepared_query(array(
            "query" => "SELECT session_id FROM forum_session WHERE session_id = :sid AND forum_id=:fid",
            "params" => array(
                ":sid" => $sid,
                ":fid" => $forum_id_const
            )
        ));
        $session_id = _mysql_result($result, 0);
        if ($session_id == $sid) {
            $include = true;
        } else {
            $acp_action = './theme/' . $site_settings['template'] . '/password.html';
            do_action();
        }
    } else {
        $include = true;
    }
}

if ($include) {
    if ($forum_info[0]['forum_type'] == '1' || $forum_info[0]['forum_type'] == '3') { //site || blog
        include_once './lib/view_site.php';
    } else if ($forum_info[0]['forum_type'] == '2') { //gallery
        include_once './lib/view_gallery.php';
    } else if ($forum_info[0]['forum_type'] == '0') { //gallery
        include_once './lib/forum.php';
    }
}

$OG_TAGS .= generate_og_tags();
//$OG_TAGS .= '-->';
if ($links_list_sub == 'None<br><br>') {
    $TOP_TEXT = '<b>TOPIC LIST<br> ' . $FORUM_ACTIONS . '</b>';
    $BOTTOM_TEXT = '';
    $links_list_sub = '';
} else {
    $TOP_TEXT = '<b>SUB CATEGORIES</b>';
    $BOTTOM_TEXT = '<b>TOPIC LIST<br> ' . $FORUM_ACTIONS . '</b><br>';
}

$bbregex = bbcode_to_regex($tags, 'bbcode', 'bbcode_html');
$NOTICE = parse_bbcode($site_settings['site_announcement'], $bbregex, array(), true, true) . $NOTICE;
$footer_text = parse_bbcode($site_settings['footer_text'], $bbregex, array(), true, true);
//end
$UPLOADS_ALLOWED = '0';
$cond = $site_settings['allow_upload'] && has_permission($current_user['permissions']['global'], 'u_attach');
if ($forum_id_const > 0) {
    $cond = $cond && has_permission($current_user['permissions'][$forum_id_const], 'f_can_attach');
}
if ($cond) {
    $UPLOADS_ALLOWED = '1';
}

$main = 'main.html';
if ($forum_info[0]['forum_type'] == '3') {
    $main = 'main_blog.html';
}
if ($forum_info[0]['forum_type'] == '0') {
    $main = 'main_forum.html';
}

$ann_file = file_get_contents('./theme/' . $site_settings['template'] . '/announcement.html');
$announcement = '';
if ($NOTICE != '') {
    $announcement = template_replace($ann_file, $load);
}

$content = file_get_contents('./theme/' . $site_settings['template'] . '/' . $main);
$content = str_replace('{title}', $site_settings['site_name'], $content);
$content = str_replace('{INJECT_FILE}', $acp_action, $content);
$milliseconds2 = round(microtime(true) * 1000);
$generation_time = ($milliseconds2 - $milliseconds) / 1000;

$content = template_replace($content, $load);
$CONTENT_OUT = $content;