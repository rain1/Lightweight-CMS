<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

if (!function_exists("ConnectdataBase")){
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
include_once("./permissions.php");
include_once "./bbcode.php";
}
if (!$GLOBALS_DEFINED == true){include_once("../globals.php");}
if (!function_exists("CreateUser")){include_once("./login.php");}
sanitarize_input(true, false);


define_user();

if($IS_BANNED){
    $_POST = array();
    unset($_GET['a']);
}




$library_path = "./lib";
$forum_links = get_allowed_forums();

$forum_id_const = $forum_links[0]['forum_id'];
if(isset($_GET['f'])){
    $forum_id_const = intval($_GET['f']);
}
if(!forum_exists($forum_id_const)){
    display_error("Error","Forum does not exist");
}
if(!isset($_GET['id'])){
    die("Something went wrong");
}elseif($_GET['id'] != '0'){
    $forum_id_const = topic_get_forum($_GET['id']);
}

$comments = forum_get_info($forum_id_const);
$comments = $comments[0]['comments'];

if($comments=='0'){
    die("comments_not_exist");
}

if(isset($_GET['a']) && $_GET['a'] == 'test'){
    die("comments_exist");
}


$current_user['permissions'][$forum_id_const] = permissions_to_string(user_get_permissions($current_user['uid'],$forum_id_const));


if(!has_permission($current_user['permissions'][$forum_id_const],"f_read_forum")){
    die("forum_denied");
}

$current_user['permissions'][$comments] = permissions_to_string(user_get_permissions($current_user['uid'],$comments ));

if(!has_permission($current_user['permissions'][$comments],"f_read_forum")){
    die("comments_denied");
}


/*
 * POST
 * a - action
 * p - post
 * Editor
 * report_msg - p6hjus
 * GET
 * edit
 * new
 * approve
 * viewreport
 * report
 * closereport
 * delete confirm=yes
 */
if(isset($_GET['a'])){
    if($_POST['website']!=""){
        $FILE_PATH[$CURRENT_MODULE] = '../theme/'.$site_settings['template'].'/ucp/failure_module.html';
        $notification = "You ar not a human <br> <a href=\"#\" onclick=\"history.go(-1)\">Retry</a> or <a href=\"../\">Go to board index</a>";
        _mysql_prepared_query(array(
            "query" => "INSERT INTO bans VALUES(NULL, 0, :ip, '', :start_time, :end_time, 'Website filled for comment','Segmentation fault', 0)",
            "params" => array(
                ":ip" => $_SERVER['REMOTE_ADDR'],
                ":start_time" => time(),
                ":end_time" => time()+$site_settings['robot_ban_length']
            )
        ));
        log_event("USER", "system", $_SERVER['REMOTE_ADDR'], "comment", "ip banned for filling in website.");
        die("comment_failed");
    }
    $post = post_get_info($_GET['p']);
    $not_edit_locked = $post['edit_locked']=='0' ? "true": "false";
    switch($_GET['a']){
        case 'edit' :
            $topic_data = $title_warning.'<h2 style="margin: 0px 0px 1em 0px; padding: 0px;">'.$post[0]['post_title'].'</h2><br>'.parse_bbcode($post[0]['data'],bbcode_to_regex($tags,'bbcode','bbcode_html'),array(),true,true);
            if(has_permission($current_user['permissions'][$comments],'f_edit_own')
            && post_get_owner($_GET['p']) == $current_user['uid']
            || has_permission($current_user['permissions']['global'],'m_edit_posts')
            || has_permission($current_user['permissions'][$comments],'m_edit_posts')){
                _mysql_prepared_query(array(
                    "query" => "UPDATE post SET data=:comment WHERE id=:pid",
                    "params" => array(
                        ":comment" => $_POST['Editor'],
                        ":pid" => $_GET['p']
                    )
                ));
                die("edit_success");
            }else{
                die("edit_failed");
            }
            break;
        case 'new':
            $topic_data = "";
            $_GET['p'] = 0;
            if(has_permission($current_user['permissions'][$comments],'f_start_new')){
                $apporoved =  '0';
                if(has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$forum_id_const]) ,"m_approve_posts||f_no_approval")){
                    $apporoved =  '1';
                }
                _mysql_prepared_query(array(
                    "query" => "INSERT INTO post VALUES (NULL, 0, :fid, :ip, :time, 0, 0, :username, :uid, 0, 0, '', 0, 0, :comment, :tid, :approved, '', 0, 0)",
                    "params" => array(
                        ":fid" => $comments,
                        ":ip" => $_SERVER['REMOTE_ADDR'],
                        ":time" => time(),
                        ":username" => $current_user['user']['username'],
                        ":uid" => $current_user['uid'],
                        ":comment" => $_POST['Editor'],
                        ":tid" => $_GET['id'],
                        ":approved" => $apporoved
                    )
                ));
                $insert_id = _mysql_insert_id();
                forum_update_statistics_relative($comments, 0, 1);
                _mysql_prepared_query(array(
                    "query" => "UPDATE users SET user_post_count=user_post_count+1 WHERE user_id=:uid",
                    "params" => array(
                        ":uid" => $current_user['uid']
                    )
                ));
                $sql = "SELECT id, time, post.user_id, data, users.username, edit_locked, forum_id,is_approved,reported, post.solved FROM post, users WHERE post.user_id=users.user_id AND post_title=".$_GET['id']." AND id='". $insert_id ."' ORDER BY time DESC";
                //die($sql);
                $posts = get_table_contents("","","",false,$sql);
                if($posts == null){
                    die("Your comment didn't make it to the server :(");
                }else{
                    $ret = "";
                    for ($i = 0; $i < count($posts); $i++) {
                        $content = htmlspecialchars($posts[$i]['data']);
                        $content =  str_replace("\n", "<br>", $content);
                        $ret .= '<div class="content" id="content'.$posts[$i]['id'].'"><span class="post_time">By '.$posts[$i]['username']." on ".date($site_settings['time_format'],$posts[$i]['time'])."</span> ".topic_get_post_actions($posts[$i],false,true).'<div id="p'.$posts[$i]['id'].'">'.$content.'</div></div>';
                    }
                    die($ret);
                }
            }else{
                die("comment_failed");
            }
            break;
        case 'approve':
            if(has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$comments]) ,"m_approve_posts")){
                if(isset($_GET['p'])){
                    post_approve($_GET['p']);
                    die("approve_success");
                }
            }else{
                die("approve_denied");
            }
            break;
        case 'viewreport':
            if(has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$comments]) ,"m_close_reports||f_can_report")){
                if(isset($_GET['p'])){
                    $report_details = post_view_report($_GET['p']);
                    if($report_details){
                        $report = file_read("../theme/".$site_settings['template']."/view_report.html");
                        $report_details['reporter'] = user_get_info_by_id($report_details['reporter'] );
                        $report = str_replace('href="./?p={%$_GET[\'p\']%}&a=closereport"', 'href="#" onclick="parseLink(\'/lib/view_comment.php?a=closereport&p='.$_GET['p'].'\'); return false;"', $report);
                        $report = str_replace('href="{%$_SERVER[\'HTTP_REFERER\']%}"', 'href="#" onclick="cancel('.$_GET['p'].'); return false;"', $report);
                        $report = str_replace('<a href="./?p={%$_GET[\'p\']%}">[View post]</a><br>', "", $report);
                        $report = str_replace('</h2>', "</h2><br>", $report);
                        $report = sting_replace_var($report);
                        die($report); // $report_details['reporter'] printida
                    }else{
                        die("report_view_fail");
                    }
                }
            }else{
                die("report_view_denied");
            }
            break;
        case 'report':
            if(has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$comments]) ,"m_close_reports||f_can_report")){
                if(isset($_GET['p'])){
                    if(isset($_POST['report_msg'])){
                        if($_POST['report_msg'] != ""){
                            if(post_open_report($_GET['p'],$_POST['report_msg'])){
                                die("report_success");
                            }else{
                                die("report_fail");
                            }
                        }else{
                            die("report_empy");
                        }
                    }else{
                        $acp_action = "./theme/".$site_settings['template']."/forms/report.html";
                    }
                }
            }else{
                die("report_denied");
            }
            break;
        case 'closereport':
            if(has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$comments]) ,"m_close_reports")){
                if(isset($_GET['p'])){
                    if(post_report_close($_GET['p'])){
                        die("close_report_success");
                    }else{
                        die("close_report_fail");
                    }
                }
            }else{
                die("close_report_denied");
            }
            break;
        case 'delete':
            $post_owner = post_get_owner($_GET['p']);
            if(has_permission($current_user['permissions'][$comments],'f_delete_own')
                && $post_owner == $current_user['uid']
                || has_permission($current_user['permissions']['global'],'m_delete_posts')
                || has_permission($current_user['permissions'][$comments],'m_delete_posts')){
                    if(isset($_GET['confirm']) && $_GET['confirm'] == 'yes'){
                        $ret = post_delete($_GET['p']);
                        _mysql_prepared_query(array(
                            "query" => "UPDATE users SET user_post_count=user_post_count-1 WHERE user_id=:uid",
                            "params" => array(
                                ":uid" => $post_owner
                            )
                        ));
                        die("delete_success");
                    }
            }else{
                die("delete_denied");
            }
            break;
        default:
            die("unknown_error");
            break;
    }
}

$approve = "AND is_approved=1 ";
if(has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$comments]) ,"m_approve_posts")){
    $approve = "";
}
$sql = "SELECT id, time, post.user_id, data, users.username, edit_locked, forum_id,is_approved,reported , post.solved FROM post, users WHERE post.user_id=users.user_id AND post_title=".$_GET['id']." ".$approve." ORDER BY time DESC";

$posts = get_table_contents("","","",false,$sql);
if($posts == null){
    die("Ther's no comments to show, be first one to comment.");
}else{
    $ret = "";
    for ($i = 0; $i < count($posts); $i++) {
        $content = htmlspecialchars($posts[$i]['data']);
        $content =  str_replace("\n", "<br>", $content);
        $ret .= '<div class="content comment" id="content'.$posts[$i]['id'].'"><span class="post_time">By <a href="./profile.php?uid='.$posts[$i]['user_id'].'">'.$posts[$i]['username']."</a> on ".date($site_settings['time_format'],$posts[$i]['time'])."</span> ".topic_get_post_actions($posts[$i],false,true).'<div id="p'.$posts[$i]['id'].'">'.$content.'</div></div>';
    }
    die($ret);
}