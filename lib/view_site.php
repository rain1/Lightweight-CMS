<?php


//~ function forum_get_actions($id){
    //~ global $current_user;
    //~ if(has_permission($current_user['permissions'][1],"f_start_new")){
        //~ return ' <a href="./index.php?p=0&a=new">new</a>';
    //~ }
    //~ return '';
//~ }

//CODE 


if(isset($_GET['a'])){
    do_action();
}else{
    if($_GET['id']=='0'){
        $topics = forum_get_allowed_topic($forum_id_const,0,1);
        $_GET['id']=$topics[0]['topic_id'];
    }
    $mod_tools = get_mod_tools();
    $attachment_list = "var AttachmentList = [];";

    $CURRENT_TOPIC = topic_get_info($_GET['id']);
    //~ $TOPICtitle
    $topic_content = topic_get_data($_GET['id']);
    if(!$topics){
        $acp_action = "./theme/".$site_settings['template']."/view_site.html";
        if($forum_info[0]['forum_type']== '3'){
            $acp_action = "./theme/".$site_settings['template']."/view_blog.html";
        }
        $topic_data  = "There are no topics or posts in this forum.";
    }else{
        $acp_action = "./theme/".$site_settings['template']."/view_site.html";
        if($forum_info[0]['forum_type']== '3'){
            $acp_action = "./theme/".$site_settings['template']."/view_blog.html";
        }
        $topic_data = display_topic($topic_content,$tags,$CURRENT_TOPIC);
    }

    $FORUM_ACTIONS = forum_get_actions($forum_id_const,$_GET['id']);
}

function display_topic($posts,$tags,$topic, $no_permissions = false){
    global $forum_id_const, $current_user, $site_settings;
    $ret = "";
    topic_inc_views($topic[0]['topic_id']);
    for($i = 0; $i < count($posts); $i++){
        if($posts[$i]['is_approved']==1 || has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$forum_id_const]) ,"m_approve_posts")){
            if($no_permissions){
                $actions = "";
            }else{
                $actions = topic_get_post_actions($posts[$i], true, false, $topic); 
            }

            $posts[$i]['data'] = decode_input($posts[$i]['data'] );
            if($site_settings['allow_bbcode'] == "1" && $posts[$i]['bbcode'] == "1"){
                $posts[$i]['data'] = parse_bbcode($posts[$i]['data'],bbcode_to_regex($tags,'bbcode','bbcode_html'),array(),true,true);
            }
            $attachment_html = "";
            if(has_permission($current_user['permissions'][$forum_id_const],'f_can_download') && has_permission($current_user['permissions']['global'],'u_download_files')  && $site_settings['allow_download'] == "1"){
                $attachments = post_get_attachments($posts[$i]['id']);
                $attachment_html .= "<br><br>";
                $attach = render_attachment($attachments,$posts[$i],true, false);
                $attachment_html = $attach['attachments'];
            }
            $posts[$i]['data'].=$attachment_html ;
            $share = "";
            if(has_permission($current_user['permissions'][$forum_id_const] ,"f_share")){
                $share = '<span class="share '.$posts[$i]['id'].'"></span>';
            }
            $like = "";
            if(has_permission($current_user['permissions'][$forum_id_const] ,"f_like")){
                $like = '<span>'.render_likes($posts[$i]['id']).'</span>';
            }
            $class = '';
            if($posts[$i]['solved']){
                //$class = ' solved';
                $posts[$i]['post_title'] = '<img src="./theme/'.$site_settings['template'].'/icons/check.png" class="solved icon24">'.$posts[$i]['post_title'];
            }
            $ret .= '<div class="post_container'.$class.'"><div><div style="float:right; text-align: left;">'.$actions.'</div><a href="./?p='.$posts[$i]['id'].'"><h2 id="post'.$posts[$i]['id'].'" style="margin: 0px 0px 1em 0px; padding: 0px;">'.$posts[$i]['post_title'].'</h2></a><br><span class="post_time">By <a href="./profile.php?uid='.$posts[$i]['user_id'].'">'.$posts[$i]['username']."</a> on ".$posts[$i]['time'].'</span></div><br><div class="post_content">'.$posts[$i]['data'].'</div><br/>
                <div><span id="ht'.$posts[$i]['id'].'" class="'.$posts[$i]['hashtags'].'" style="float: left;">'.render_hashtags($posts[$i]['hashtags']).'</span><div style="text-align: right;">'.$share.' '.$like.'</div></div><br></div>';
        }
    }
    return $ret;
}
