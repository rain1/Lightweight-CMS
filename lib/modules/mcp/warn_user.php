<?php
class warn_user{
    var $module_info = array(
        'title' => "Post info",
        'MODULES' => array(
            array('name' => 'warn_user','permissions' => 'm_issue_warning'),
            array('name' => 'list_warn','permissions' => 'm_issue_warning')
        )
    );

    function main($module){
	global $current_user, $notification_back_link, $language;
        $this->page_title = $language['module_titles']['warn_user'];
        switch($module){
            case 'warn_user':
                if($_GET['mode'] == "delete"){
                    if(isset($_POST['warn'])){
                        $query = "SELECT warn.id, users.username,  users.user_id, points FROM warn
                                    LEFT JOIN users
                                    ON warn.user_id = users.user_id
                                    WHERE  warn.id = :warn_id";
                        $result = _mysql_prepared_query(array(
                            "query" => $query,
                            "params" => array(
                                ":warn_id" => $_POST['warn']
                            )
                        ));
                        $arr = _mysql_fetch_assoc($result);
                        if(is_array($arr)){
                            log_event('MODERATOR', $current_user['name'], $_SERVER['REMOTE_ADDR'], "UNWARN USER", 'Removed '.$arr['points'].' warn from user <a href="../profile.php?uid='.$arr['user_id'].'">'.$arr['username'].'</a>');
                            _mysql_prepared_query(array(
                                "query" => "DELETE FROM warn WHERE id = :warn_id",
                                "params" => array(
                                    ":warn_id" => $_POST['warn']
                                )
                            ));
                            _mysql_prepared_query(array(
                                "query" => "UPDATE users SET user_warn=user_warn - :points WHERE user_id=:uid",
                                "params" => array(
                                    ":points" => $arr['points'],
                                    ":uid" => $arr['user_id']
                                )
                            ));
                            $this->template = "success_module";
                            $this->vars=array(
                                'SUCCESSMSG' => $language['notifications']['warn_remove'].$notification_back_link
                            );
                            break;
                        }
                    }
                }
                if(isset($_GET['uid'])){
                    $uinfo = user_get_info_by_id($_GET['uid']);
                    if($uinfo){
                        if(isset($_POST['points'])){
                            _mysql_prepared_query(array(
                                "query" => "INSERT INTO warn VALUES (NULL, :uid, 0, :time, :reason, :points, :verbal)",
                                "params" => array(
                                    ":uid" => $_GET['uid'],
                                    ":time" => time(),
                                    ":reason" => $_POST['reason'],
                                    ":points" => $_POST['points'],
                                    ":verbal" => checkbox_to_int($_POST['verbal'])
                                )
                            ));
                            _mysql_prepared_query(array(
                                "query" => "UPDATE users SET user_warn=user_warn + :points WHERE user_id=:uid",
                                "params" => array(
                                    ":points" => $_POST['points'],
                                    ":uid" => $_GET['uid'],
                                )
                            ));
                            log_event('MODERATOR', $current_user['name'], $_SERVER['REMOTE_ADDR'], "WARN USER", 'Warned user <a href="../profile.php?uid='.$_GET['uid'].'">'.$uinfo[0]['username'].'</a>');
                            $this->template = "success_module";
                            $this->vars=array(
                                'SUCCESSMSG' => $language['notifications']['warn_success'].'<br> <a href="'.build_url_relative(array('id','a')).'">back</a> '
                            );
                            break;
                        }
                    }else{
                        $this->template = "failure_module";
                        $this->vars=array(
                            'SUCCESSMSG' => $language['notifications']['warn_invalid_post']
                        );
                    }
                    break;
                }
                if(isset($_GET['p'])){
                    $post_info = post_get_info($_GET['p']);
                    if($post_info){
                        if(isset($_POST['points'])){
                            log_event('MODERATOR', $current_user['name'], $_SERVER['REMOTE_ADDR'], "WARN USER", 'Warned user <a href="../profile.php?uid='.$post_info[0]['user_id'].'">'.$post_info[0]['username'].'</a> for post <a href="'.$post_info[0]['id'].'">'.$post_info[0]['post_title'].'</a>');
                            _mysql_prepared_query(array(
                                "query" => "INSERT INTO warn VALUES (NULL, :uid, :pid, :time, :reason, :points, :verbal)",
                                "params" => array(
                                    ":uid" => $post_info[0]['user_id'],
                                    ":pid" => $post_info[0]['id'],
                                    ":time" => time(),
                                    ":reason" => $_POST['reason'],
                                    ":points" => $_POST['points'],
                                    ":verbal" => checkbox_to_int($_POST['verbal'])
                                )
                            ));
                            _mysql_prepared_query(array(
                                "query" => "UPDATE users SET user_warn=user_warn+:points WHERE user_id=:uid",
                                "params" => array(
                                    ":points" => $_POST['points'],
                                    ":uid" => $post_info[0]['user_id']
                                )
                            ));
                            $this->template = "success_module";
                            $this->vars=array(
                                'SUCCESSMSG' => $language['notifications']['warn_success']."<br> <a href=\"".$_SERVER['REQUEST_URI']."\">back</a> "
                            );
                            break;
                        }
                        //$columns = array("id","user_id","time","message","points","type","post_title");
                        $warn_js = json_encode(get_table_contents("","","",false,"SELECT warn.*, COALESCE(post.post_title,'') AS post_title FROM warn LEFT JOIN post ON post.id = warn.post_id AND post.user_id=warn.user_id WHERE warn.user_id=".$post_info[0]['user_id'],array('time')));
                        $post_info[0]['topic'] = topic_get_info($post_info[0]['topic_id']);
                        $post_info[0]['poster'] = user_get_info_by_id($post_info[0]['user_id']);
                        $tags= get_table_contents(bbcode,'ALL');
                        $attachment_html = "";
                        $attachments = post_get_attachments($post_info[0]['id'] );
                        $attachment_html .= "<br><br>";
                        for($j = 0; $j < count($attachments); $j++){
                            $attachment_html  .= '<div class="attachment"><a href="./lib/upload.php?a=download&file='.$attachments[$j]['id'].'">'.$attachments[$j]['file_name'].'</a><br>size: '.$attachments[$j]['size'].' bytes, downloaded '.$attachments[$j]['downloads'].' time(s)</div>';
                        }
                        $post_info[0]['data'] = parse_bbcode($post_info[0]['data'],bbcode_to_regex($tags,'bbcode','bbcode_html'),array(),true,true);
                        $post_info[0]['forum'] = forum_get_info($post_info[0]['forum_id']);
                        $post_info[0]['attach'] = $attachment_html;
                        $this->page_title = $language['module_titles']['warn_user'];
                        $this->template = "warn_post";
                        $this->vars=$post_info;
                        $this->vars['m_change_post_author']=strval(has_permission($current_user['permissions']['global'],'m_change_post_author'));
                        $this->vars['WARN']=$warn_js;
                    }else{
                        $this->template = "failure_module";
                        $this->vars=array(
                            'SUCCESSMSG' => $language['notifications']['warn_invalid_post']
                        );
                    }
                }else{
                    if(isset($_POST['username'])){
                        $uid = user_get_id_by_name($_POST['username']);
                        if($uid){
                            $query = "SELECT warn.*, COALESCE(post.post_title,'') AS post_title FROM warn LEFT JOIN post ON post.id = warn.post_id AND post.user_id=warn.user_id WHERE warn.user_id=".$uid;
                            $warn_list = get_table_contents("","","",false, $query, array('time'));
                            $warn_js = json_encode($warn_list);
                            $this->template = "warn_user";
                            $this->vars['WARN']=$warn_js;
                            $this->vars['USER']=user_get_info_by_id($uid);
                        }else{
                            $this->template = "failure_module";
                            $this->vars=array(
                                'SUCCESSMSG' => $language['notifications']['warn_user_not_found']." <br> <a href=\"".$_SERVER['REQUEST_URI']."\">back</a>"
                            );
                        }
                    }else{
                        $this->template = "select_user";
                        $this->vars=array(
                        );
                    }
                }
                break;
            case "list_warn":
                $query = "SELECT warn.*, COALESCE(post.post_title,'') AS post_title, users.username, users.user_warn FROM users,warn LEFT JOIN post ON post.id = warn.post_id AND post.user_id=warn.user_id WHERE users.user_id=warn.user_id";
                $warnings = get_table_contents("","","",false, $query, array("time"));
                $warn_js = json_encode($warnings);
                $this->template = "warn_list";
                $this->vars=array(
                    'WARN' => $warn_js
                );
                break;
        }
    }
}