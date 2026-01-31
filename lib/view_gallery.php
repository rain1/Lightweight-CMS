<?php

//~ function forum_get_actions($id){
    //~ global $current_user;
    //~ if(has_permission($current_user['permissions'][1],"f_start_new")){
        //~ return ' <a href="./index.php?p=0&a=new">new</a>';
    //~ }
    //~ return '';
//~ }
//CODE 

global $galleries, $render_albums;

if(isset($_GET['a'])){
    do_action();
}else{
    if($_GET['id']=='0'){
        $topics = get_table_contents("","","",false,"SELECT topic_id FROM topic WHERE forum_id=".$forum_id_const." AND is_approved=1 ORDER BY type DESC, last_post_time DESC LIMIT 0,1");
        $_GET['id']=$topics[0]['topic_id'];
    }
    if(isset($_GET['p'])){
        $NO_id = false;
        $_GET['id'] = post_get_topic($_GET['p']);
    }
    if($NO_id){
        $query = "SELECT DISTINCT topic.topic_id, topic.forum_id,post.time,post.username, post.user_id, post.post_title, post.hashtags, actual_name, post_id, post.id\n FROM topic,post,attachments WHERE\n topic.forum_id=".$forum_id_const." AND topic.first_post_id=post.id AND attachments.post_id=post.id AND is_image=1 GROUP BY topic.topic_id ORDER BY type DESC, last_post_time DESC";
        $thumbs = get_table_contents("","","",false,$query);
        render_thumbs($thumbs);
        $children = forum_get_child_list_by_type($forum_id_const, 2);
        if(count_null_as_zero($children) > 0) {
            $albums = forum_get_info($children);
            $albums_list = array_copy_dimension($albums, "last_post_id");
            $query = "SELECT DISTINCT topic.topic_id, topic.forum_id,post.time,post.username, post.user_id, post.post_title, post.hashtags, actual_name, post_id, post.id\n FROM topic,post,attachments WHERE\n post.id IN (" . implode(",", $albums_list) . ") AND topic.first_post_id=post.id AND attachments.post_id=post.id AND is_image=1 GROUP BY topic.topic_id ORDER BY type DESC, last_post_time DESC";
            $album_thumbs = get_table_contents("", "", "", false, $query);
            render_galleries($album_thumbs);
        }
    }else{//id set
        //$parent = forum_get_parent($forum_id_const);
        if($_GET['id']=='0'){
            $topics = get_table_contents("","","",true,"SELECT topic_id FROM topic WHERE forum_id=".$forum_id_const." AND is_approved=1 ORDER BY type DESC, last_post_time DESC LIMIT 0,1");
            $_GET['id']=$topics[0]['topic_id'];
        }

        $mod_tools = get_mod_tools();
        $attachment_list = "var AttachmentList = [];";

        $CURRENT_TOPIC = topic_get_info($_GET['id']);
        $topic_content = topic_get_data($_GET['id']);
        $acp_action = "./theme/".$site_settings['template']."/view_gallery.html";
        $CURRENT_TOPIC = topic_get_info($_GET['id']);
        //~ $TOPICtitle
        $topic_content = topic_get_data($_GET['id']);

        if($_GET['id'] == 0){
            $topic_data  = "There are no topics or posts in this forum.";
        }else{
            $topic_data = display_topic($topic_content,$tags,$CURRENT_TOPIC);
        }

        $FORUM_ACTIONS = forum_get_actions($forum_id_const,$_GET['id']);
    }
}

function get_navigation_images($fourm_id, $time) {
   $prevnext = get_table_contents('', '', '', false,
            "SELECT * FROM ((
                SELECT post.id, actual_name, post.post_title, post.time
                FROM topic, post, attachments
                WHERE topic.forum_id = ".$fourm_id."
                AND topic.first_post_id = post.id
                AND post.id = attachments.post_id
                AND is_image =1
                AND post.time < ".$time."
                GROUP BY post.id
                ORDER BY type DESC, last_post_time DESC
                LIMIT 0 , 2
                )
                UNION (

                SELECT post.id, actual_name, post.post_title, post.time
                FROM topic, post, attachments
                WHERE topic.forum_id = ".$fourm_id."
                AND topic.first_post_id = post.id
                AND post.id = attachments.post_id
                AND is_image =1
                AND post.time > ".$time."
                GROUP BY post.id
                ORDER BY type DESC, last_post_time ASC
                LIMIT 0 , 2
              )) AS a
            ORDER BY time ASC"
    );    
    
    $html = '<div id="navigation_thumbnails" style="text-align: center;"><span>';
    for($i = count($prevnext) -1; $i > -1 ; $i--){
        $html .=  '<div class="thumb_container_small"><a href="./?p='.$prevnext[$i]['id'].'"><img src="./images/small/'.$prevnext[$i]['actual_name'].'"></a></div>';
    }
    return $html."</div></span>";
}


function display_topic($posts,$tags,$topic,$no_permissions = false){ 
    global $forum_id_const, $current_user, $forum_info, $site_settings, $BACK_TO_GALLERY, $root_dir, $image_width, $image_height;
    $ret = "";
    topic_inc_views($topic[0]['topic_id']);
    $thumbs = get_navigation_images($posts[0]['forum_id'],$posts[0]['time_timestamp']);
    $image_container = load_template_file($root_dir."/theme/".$site_settings['template']."/view_image.html");
    $image_container = template_replace($image_container,array());
    for($i = 0; $i < count($posts); $i++){
        if($posts[$i]['is_approved']==1 || has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$forum_id_const]) ,"m_approve_posts")){
            if($no_permissions){
                $actions = "";
            }else{
                if($_GET['a'] == "search"){
                    $actions = "";
                }else{
                    $actions = topic_get_post_actions($posts[$i],true, false, $topic);
                }
            }
            if($i == 0){
                hashtag_inc_hit_count($posts[$i]['hashtags'],$posts[$i]['forum_id']);
            }
            
            $posts[$i]['data'] = decode_input($posts[$i]['data'] );
            if($site_settings['allow_bbcode'] == "1" && $posts[$i]['bbcode'] == "1"){
                $posts[$i]['data'] = parse_bbcode($posts[$i]['data'],bbcode_to_regex($tags,'bbcode','bbcode_html'),array(),true,true);
            }
            
            $attachments_tmp = "";
            $image = "";
            $exif_tmp = "";
            if(has_permission($current_user['permissions'][$forum_id_const],'f_can_download')){ //<-- HERE
                $attachments = post_get_attachments($posts[$i]['id']);
                $attach = render_attachment($attachments,$posts[$i],true, $i == 0);
                $attachments_tmp = $attach['attachments'];
                $exif_tmp = $attach['exif'];
                $image = $attach['image'];
            }
            $image = str_ireplace("{ALT}", $posts[$i]['hashtags'], $image);
            $posts[$i]['data'].=$attachments_tmp ;
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
            $back_link = ($i == 0 && $_GET['a'] != "search") ? $BACK_TO_GALLERY : '<div style="font-size: 20px;"><br></div>';
            $replacements = array(
                '{class}' => $class,
                '{post_id}' => $posts[$i]['id'],
                '{post_title}' => $posts[$i]['post_title'],
                '{actions}' => $actions,
                '{back_link}' => $back_link,
                '{username}' => '<a href="./profile.php?uid='.$posts[$i]['user_id'].'">'.$posts[$i]['username'].'</a>',
                '{post_time}' => $posts[$i]['time'],
                '{image}' => $image,
                '{thumbs}' => $thumbs,
                '{exif}' => $exif_tmp,
                '{content}' => $posts[$i]['data'],
                '{hashtags}' => $posts[$i]['hashtags'],
                '{hashtags_rendered}' => render_hashtags($posts[$i]['hashtags']),
                '{share}' => $share,
                '{like}' => $like  
            );
            $image_container_tmp = strtr($image_container, $replacements);
            
            
            $ret .= $image_container_tmp;
            $thumbs = "";
        }
    }
    return $ret;
}