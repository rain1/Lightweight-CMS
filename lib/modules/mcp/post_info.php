<?php
class post_info{
    var $module_info = array(
        'title' => "Post info",
        'MODULES' => array(
            array('name' => 'post_info','permissions' => 'm_view_post_details')
            //array('name' => 'CHANGE_POSTER','permissions' => 'm_change_post_author')
        )
    );

    function main($module){
	global $current_user, $language;
        switch($module){
            case 'post_info':
                if(isset($_POST['username']) && $_POST['username'] != "" && isset($_GET['p'])){
                        if(has_permission($current_user['permissions']['global'],'m_change_post_author')){
                                $user = user_get_info_by_name($_POST['username']);
                                if($user){
                                    log_event('MODERATOR', $current_user['name'], $_SERVER['REMOTE_ADDR'], "POST INFO", $current_user['name']." changed post ".$_GET['p']." author to ".$_POST['username']);
                                    post_set_owner($_GET['p'],$user);
                                    $this->template = "success_module";
                                    $this->vars=array(
                                            'SUCCESSMSG' => "Owner successfully changed.<br> <a href=\"".$_SERVER['REQUEST_URI']."\">back</a> "
                                    );
                                }else{
                                    $this->template = "failure_module";
                                    $this->vars=array(
                                            'SUCCESSMSG' => "User wasn't found. <br> <a href=\"".$_SERVER['REQUEST_URI']."\">back</a>"
                                    );
                                }
                        }else{
                            $this->template = "failure_module";
                            $this->vars=array(
                                'SUCCESSMSG' => "You donot have permission to set poster. <br> <a href=\"".$_SERVER['REQUEST_URI']."\">back</a>"
                            );
                        }
                break;
		}
                $post_info = null;
                if (isset($_GET['p'])) {
                $post_info = post_get_info($_GET['p']);
                }
                if($post_info){
                    $post_info[0]['topic'] = topic_get_info($post_info[0]['topic_id']);
                    $post_info[0]['poster'] = user_get_info_by_id($post_info[0]['user_id']);
                    $tags= get_table_contents(bbcode,'ALL');
                    $attachment_html = "";
                    $attachments = post_get_attachments($post_info[0]['id']);
                    $attachment_html .= "<br><br>";
                    for($j = 0; $j < count($attachments); $j++){
                        $attachment_html  .= '<div class="attachment"><a href="./lib/upload.php?a=download&file='.$attachments[$j]['id'].'">'.$attachments[$j]['file_name'].'</a><br>size: '.$attachments[$j]['size'].' bytes, downloaded '.$attachments[$j]['downloads'].' time(s)</div>';
                    }
                    $post_info[0]['data'] = decode_input($post_info[0]['data']);
                    $post_info[0]['data'] = parse_bbcode($post_info[0]['data'],bbcode_to_regex($tags,'bbcode','bbcode_html'),array(),true,true);
                    $post_info[0]['forum'] = forum_get_info($post_info[0]['forum_id']);
                    $post_info[0]['attach'] = $attachment_html;
                    if($post_info[0]['user_id'] > -1){
                        $post_info[0]['editor'] =user_get_info_by_id($post_info[0]['user_id']);
                    }
                    $this->page_title = $language['module_titles']['view_post_info'];
                    $this->template = "post_details";
                    $this->vars=$post_info;
                    $this->vars['m_change_post_author']=strval(has_permission($current_user['permissions']['global'],'m_change_post_author'));
                }else{
                    $this->template = "failure_module";
                    $this->vars=array(
                        'SUCCESSMSG' => $language['notifications']['warn_invalid_post']
                    );
                }
            break;
        }
    }
}