<?php

$forum_id = $forum_id_const;
if(!isset($_GET['f'])){
    $forum_id = "0";
}

$result = _mysql_query("SELECT SUM(posts) AS post_count, SUM(topics) AS topic_count FROM forum");
$arr1 = _mysql_fetch_assoc($result);


$result = _mysql_query("SELECT count(user_id) AS user_count FROM users WHERE user_password != ''");
$arr2 = _mysql_fetch_assoc($result);

$statistics = array_merge_nulls_as_empty_array($arr1, $arr2);


if(isset($_GET['a'])){
    do_action();
}else{
    if(!isset($_GET['id']) && !isset($_GET['p'])){
        $acp_action = "./theme/".$site_settings['template']."/view_forum.html";
        $forums = get_allowed_forums(-1);

        $forum_actions = forum_get_actions($forum_id_const);

        $forums_table = "";
        for ($i = 0; $i < count($forums); $i++) {
            if($forums[$i]['parent_id'] == $forum_id){
                $subforums = "";
                for ($j = 0; $j < count($forums); $j++) {
                    if($forums[$j]['parent_id'] == $forums[$i]['forum_id']){
                        $subforums .= '<a href="./?f='.$forums[$j]['forum_id'].'" >'.$forums[$j]['forum_name'].'</a> ';
                    }
                }
                $author_info = '<a href="./?p='.$forums[$i]['last_post_id'].'" class="placeholder">'.$forums[$i]['last_post_title'].'</a>By <a style="color: '.$forums[$i]['last_post_poster_color'].'" href="./?p='.$forums[$i]['last_post_poster_id'].'" >'.$forums[$i]['last_post_poster_name'].'</a><br><span class="post_time">On '.$forums[$i]['last_post_time'].'</span>';
                if($forums[$i]['last_post_time_timestamp'] == "0"){
                    $author_info = '<span class="placeholder">No posts</span>';
                }
                $pass_img = "";
                if($forums[$i]['forum_password'] != ""){
                    $pass_img = '<img src="'.$template_directory.'/icons/locked.png" class="icon16">';
                }
                
                $forums_table .='<li class="forum_item">
            <div class="forum"><h4>'.$pass_img.'<a href="./?f='.$forums[$i]['forum_id'].'" >'.$forums[$i]['forum_name'].'</a></h4>'.$forums[$i]['description'].'<br>'.$subforums.'</div>
            <div class="count">'.$forums[$i]['posts'].'</div>
            <div class="count">'.$forums[$i]['topics'].'</div>
            <div class="post">'.$author_info.'</div>
          </li>';
            }
        }
        if($forums_table != ""){
            $forums_table = '<ul class="forums">
      <li class="list_head">
        <div class="forum">Forum</div>
        <div class="count">Posts</div>
        <div class="count">Topics</div>
        <div class="post"><span class="placeholder">Last post</span></div>
      </li>
     '.$forums_table.'
    </ul>';
        }

        $topics_table = "";
        if($forum_id > 0){
            for ($i = 0; $i < count($topics); $i++) {
                $poster_color = "";
                if($topics[$i]['poster_color'] != ""){
                    $poster_color = ' style="color: '.$topics[$i]['poster_color'].';"';
                }
                $last_poster_color = "";
                if($topics[$i]['last_poster_color'] != ""){
                    $last_poster_color = ' style="color: '.$topics[$i]['last_poster_color'].';"';
                }
                $topics_table .='<li class="forum_item">
                <div class="forum"><a href="./?id='.$topics[$i]['topic_id'].'" a>'.$topics[$i]['title'].'</a><br><span class="post_time">By <a href="./profile.php?uid='.$topics[$i]['Poster'].'" '.$poster_color.'>'.$topics[$i]['poster_name'].'</a>, '.$topics[$i]['time'].'</span></div>
                <div class="count">'.$topics[$i]['Replies'].'</div>
                <div class="count">'.$topics[$i]['Views'].'</div>
                <div class="post"><a class="placeholder" href="./profile.php?uid='.$topics[$i]['last_poster'].'" '.$last_poster_color.'>'.$topics[$i]['last_poster_name'].'</a><span class="post_time">'.$topics[$i]['last_post_time'].'</span></div>
                </li>';   
            }
            if($topics_table == ""){
                $topics_table = '<div>There are no topics or posts in this forum.</div>';
            }
           $topics_table = '<ul class="forums">
            <li class="list_head">
              <div class="forum">Topic</div>
              <div class="count">Replies</div>
              <div class="count">Views</div>
              <div class="post"><span class="placeholder">Last post</span></div>
            </li>
            '.$topics_table.'
            </ul>';
        }else{
            $forum_actions = "";
        }


    }else{
        $acp_action = "./theme/".$site_settings['template']."/view_forum_topic.html";
        $forum_actions = forum_get_actions($forum_id_const, $_GET['id']);
        $CURRENT_TOPIC = topic_get_info($_GET['id']);
        $topic_content = topic_get_data_ex($_GET['id']);
        if(!$topics){
            $topic_data  = "There are no topics or posts in this forum.";
        }else{
            $topic_data = display_topic($topic_content,$tags, $CURRENT_TOPIC, false);
            $mod_tools = get_mod_tools();
        }
    }
}


function display_topic($posts,$tags,$topic,$no_permissions = false){ 
    global $forum_id_const, $current_user, $forum_info, $site_settings, $pid_image, $BACK_TO_GALLERY, $prev_link,$next_link, $root_dir, $image_width, $image_height;
    $pid_image = array();
    $ret = "";
    topic_inc_views($topic[0]['topic_id']);
    $image_container = load_template_file($root_dir."/theme/".$site_settings['template']."/forum_post.html");
    $image_container = template_replace($image_container, array());
    for($i = 0; $i < count($posts); $i++){
        if($posts[$i]['is_approved']==1 || has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'],$current_user['permissions'][$forum_id_const]) ,"m_approve_posts")){
            $edit_info = "";
            if($posts[$i]['edit_count'] > 0){
                $user = user_get_info_by_id($posts[$i]['edit_user_id']);
                $edit_info = '<br>This post have been edited '.$posts[$i]['edit_count'].' times, last edit by '.render_user_link($user[0]).' on '.$posts[$i]['edit_time'].'.<br>';
                if($posts[$i]['edit_reason'] != ""){
                    $edit_info .= 'reason: '.$posts[$i]['edit_reason'] ;
                }
            }
            $ranks = render_user_rank($posts[$i]['user_rank'],$posts[$i]['user_rank_name'],$posts[$i]['user_rank_image'], $posts[$i]['rank'], $posts[$i]['group_rank_name'], $posts[$i]['group_rank_image']);
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
            
            $attachment_html = "";
            if(has_permission($current_user['permissions'][$forum_id_const],'f_can_download')){
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
            
            if($posts[$i]['color'] == ""){
                $posts[$i]['color'] = 'inherit';
            }
            
            if($posts[$i]['user_avatar'] != ""){
                $posts[$i]['user_avatar'] = '<div style="display: inline-block;"><div class="profile_image"><img id="avatar" src="'.$posts[$i]['user_avatar'].'"></div></div><br>';
            }
            
            if($posts[$i]['user_signature'] != ""){
                $posts[$i]['user_signature'] = '<hr class="signature_separator">'.parse_bbcode($posts[$i]['user_signature'],bbcode_to_regex($tags,'bbcode','bbcode_html'),array(),true,true);
            }
            
            $replacements = array(
                '{class}' => $class,
                '{post_id}' => $posts[$i]['id'],
                '{post_title}' => $posts[$i]['post_title'],
                '{actions}' => $actions,
                '{username}' => '<a style="color:'.$posts[$i]['color'].';" href="./profile.php?uid='.$posts[$i]['user_id'].'">'.$posts[$i]['username'].'</a>',
                '{POSTTIME}' => $posts[$i]['time'],
                '{content}' => $posts[$i]['data'],
                '{hashtags}' => $posts[$i]['hashtags'],
                '{hashtags_rendered}' => render_hashtags($posts[$i]['hashtags']),
                '{EDIT}' => $edit_info,
                '{share}' => $share,
                '{like}' => $like,
                '{AVATAR}' => $posts[$i]['user_avatar'],
                '{POSTS}' => $posts[$i]['user_post_count'],
                '{JOINED}' => $posts[$i]['user_join_date'],
                '{signature}' => $posts[$i]['user_signature'],
                '{RANK}' => $ranks
            );
            $image_container_tmp = strtr($image_container, $replacements);
            
            
            $ret .= $image_container_tmp;
        }
    }
    return $ret;
}