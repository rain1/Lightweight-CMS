<?php

/**
 *
 */


/**
 * Increases topic view count by one
 * @param integer $tid topic id
 */
function topic_inc_views($tid)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET Views=Views+1 WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $tid
        )
    ));
}


/**
 * Increases hashtag view count boy one
 * @param string $tags tags for which to increase view count
 * @param integer $forum forum id
 */
function hashtag_inc_hit_count($tags, $forum)
{
    $tags_list = explode(" ", $tags);
    $tags = "";
    for ($i = 0; $i < count($tags_list); $i++) {
        $tags .= "'" . $tags_list[$i] . "',";
    }
    $tags = StringTrimRight($tags, 1);
    _mysql_prepared_query(array(
        "query" => "UPDATE hashtags SET hit_count=hit_count+1 WHERE tag IN (:tags) AND forum_id=:fid",
        "params" => array(
            ":tags" => $tags,
            ":fid" => $forum
        )
    ));
}


/**
 * Updates hastags use count
 * @param string $tags_new Tags after create, edit, delete or move post.
 * @param string $tags_old Tags that were before create, edit, delete or move post.
 * @param integer $forum id
 */
function hastag_update_use_count($tags_new, $tags_old, $forum)
{
    $new_tag_list = explode(" ", $tags_new);
    $old_tag_list = explode(" ", $tags_old);

    $merged = array_merge_nulls_as_empty_array($new_tag_list, $old_tag_list);

    $tags_relative = array();
    foreach ($merged as $tag) {
        if ($tag != "") {
            if (in_array($tag, $new_tag_list) && in_array($tag, $old_tag_list)) {
                //$tags_relative[$tag] = 0;
            } else if (in_array($tag, $new_tag_list)) {
                $tags_relative[$tag] = "+1";
            } else if (in_array($tag, $old_tag_list)) {
                $tags_relative[$tag] = "-1";
            }
        }
    }

    $sql = "";

    foreach ($tags_relative as $tag => $count) {
        $sql .= "UPDATE hashtags SET use_count=use_count" . $count . " WHERE tag='" . $tag . "' AND forum_id='" . $forum . "';\n";
    }

    _mysql_multi_query($sql);
    _mysql_flush_multi_queries();
}


/**
 * Updates hashtag count for entire topic
 * @param string $tags hashtags
 * @param integer $current_forum current forum
 * @param integer $new_forum forum where topic is moved. -1 when topic is deleted.
 */
function hastag_update_use_count_topic($tags, $current_forum, $new_forum = -1)
{
    $tag_list = explode(" ", $tags);

    $tags_relative = array();
    foreach ($tag_list as $tag) {
        if ($tag != "") {
            if (!array_key_exists($tag, $tags_relative)) {
                $tags_relative[$tag] = 1;
            } else {
                $tags_relative[$tag] += 1;
            }
        }
    }

    $sql = "";

    foreach ($tags_relative as $tag => $count) {
        $sql .= "UPDATE hashtags SET use_count=use_count-" . $count . " WHERE tag='" . $tag . "' AND forum_id='" . $current_forum . "';\n";
        if ($new_forum > -1) {
            $sql .= "UPDATE hashtags SET use_count=use_count+" . $count . " WHERE tag='" . $tag . "' AND forum_id='" . $new_forum . "';\n";
        }
    }

    _mysql_multi_query($sql);
    _mysql_flush_multi_queries();

}


/**
 * Gets hastags from all posts in given topic
 * @param $topic_id topic id
 * @return string topic tags (incl repititions)
 */
function topic_get_hashtags($topic_id)
{
    $posts = get_table_contents("post", array("hashtags"), "WHERE topic_id = " . $topic_id);
    $total = "";
    foreach ($posts as $post) {
        $total .= $post["hashtags"] . " ";
    }
    $total = strtolower($total);
    return $total;
}


/**
 * Gets hastags from all posts in given forum
 * @param $forum_id forum id
 * @return string topic tags (incl repititions)
 */
function forum_get_hashtags($forum_id)
{
    $posts = get_table_contents("post", array("id", "hashtags"), "WHERE forum_id = " . $forum_id);
    $total = "";
    foreach ($posts as $post) {
        $total .= $post["hashtags"] . " ";
    }
    $total = strtolower($total);
    return $total;
}


/**
 * Updates hashtag count for entire forum
 * @param string $tags hashtags
 * @param integer $forum forum
 */
function hastag_update_use_count_forum($tags, $forum)
{
    $tag_list = explode(" ", $tags);

    $tags_relative = array();
    foreach ($tag_list as $tag) {
        if ($tag != "") {
            if (!array_key_exists($tag, $tags_relative)) {
                $tags_relative[$tag] = 1;
            } else {
                $tags_relative[$tag] += 1;
            }
        }
    }

    $sql = "";

    foreach ($tags_relative as $tag => $count) {
        $sql .= "UPDATE hashtags SET use_count=" . $count . " WHERE tag='" . $tag . "' AND forum_id='" . $forum . "';\n";
    }

    _mysql_multi_query($sql);
    _mysql_flush_multi_queries();
}


/**
 * Gets all posts for given topic
 * @param integer $id topic id
 * @param bool $encode wether line endings should be replaced with html line endings
 *                     true -  line endings are replaced
 *                     false (defailt) - line endings are kept as they are
 * @return mixed array of posts
 */
function topic_display($id, $encode = false)
{
    $ret = get_table_contents("post", "ALL", "WHERE topic_id = " . $id);
    if ($encode) {
        for ($i = 0; $i < count($ret); $i++) {
            $ret[$i]['data'] = str_replace(array("\n", "\r", "\r\n", '"'), array('<br>\n', '<br>\r', '<br>\r\n', '\"'), $ret[$i]['data']);
        }
    }
    return $ret;
}


/**
 * Gets owner id of post
 * @param integer $id post id
 * @return mixed owner id
 */
function post_get_owner($id)
{
    $info = post_get_info($id);
    return $info[0]['user_id'];
}


/**
 * Gets attachment list for given post
 * @param integer $id post id
 * @return mixed array of attachments
 */
function post_get_attachments($id)
{
    return get_table_contents("attachments", "ALL", "WHERE post_id=" . $id);
}


/**
 * Gets attachment list for given topic
 * @param integer $id topic id
 * @return mixed array of attachments
 */
function topic_get_attachments($id)
{
    return get_table_contents("attachments", "ALL", "WHERE topic_id=" . $id);
}


/**
 * Setsdisplay order for post
 * @param integer $pid post id
 * @param integer $order new display order
 */
function post_set_order($pid, $order)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE post SET display_order=:display_order WHERE id=:pid",
        "params" => array(
            ":display_order" => $order,
            ":pid" => $pid
        )
    ));
}

/**
 * Sets post to approved
 * @param integer $id post id
 * @param int $state aproved state
 *                   0 - not approved
 *                   1 (default) - approved
 * @return resource
 */
function post_approve($id, $state = 1)
{
    $post_info = post_get_info($id);
    $topic_info = topic_get_info($post_info[0]['topic_id']);
    if ($topic_info[0]['first_post_id'] == $id) {
        _mysql_prepared_query(array(
            "query" => "UPDATE topic SET is_approved=1 WHERE topic_id=:tid",
            "params" => array(
                ":tid" => $topic_info[0]['topic_id']
            )
        ));
    }
    return _mysql_prepared_query(array(
        "query" => "UPDATE post SET is_approved=:state WHERE id=:pid",
        "params" => array(
            ":pid" => $id,
            ":state" => $state
        )
    ));
}


/**
 * Finds out what post should be first for given topic and updates topic info accordingly
 * Must be called when the first post of topic is deleted
 * @param int $tid topic id
 */
function topic_update_first_post($tid)
{
    $posts = topic_get_data_ex($tid);
    _mysql_prepared_query(
        array(
            "query" => "UPDATE topic SET time=:time, first_post_id=:first_post_id, title=:title, Poster=:Poster, poster_name=:poster_name, poster_color=:poster_color  WHERE topic_id=:topic_id",
            "params" => array(
                ":time" => $posts[0]['time_timestamp'],
                ":first_post_id" => $posts[0]['id'],
                ":title" => $posts[0]['post_title'],
                ":Poster" => $posts[0]['user_id'],
                ":poster_name" => $posts[0]['username'],
                ":poster_color" => $posts[0]['user_color'],
                ":topic_id" => $tid
            )
        )
    );
}


/**
 * Finds out what post should be last for given topic and updates topic info accordingly
 * Must be called when the last post of topic is deleted
 * @param int $tid topic id
 */
function topic_update_last_post($tid)
{
    $posts = topic_get_data_ex($tid);
    $len = count($posts) - 1;
    $sql = "UPDATE topic SET last_post_time=:last_post_time, last_post_id=:last_post_id, last_poster=:last_poster, last_poster_name=:last_poster_name, last_poster_color=:last_poster_color WHERE topic_id=:tid";
    _mysql_prepared_query(array(
        "query" => $sql,
        "params" => array(
            ":last_post_time" => $posts[$len]['time_timestamp'],
            ":last_post_id" => $posts[$len]['id'],
            ":last_poster" => $posts[$len]['user_id'],
            ":last_poster_name" => $posts[$len]['username'],
            ":last_poster_color" => $posts[$len]['user_color'],
            ":tid" => $tid
        )
    ));
}


/**
 * Sets owner of post
 * @param int $post id
 * @param array $owner owner data
 */
function post_set_owner($post, $owner)
{
    $post_info = post_get_info($post);
    user_dec_post_count($post_info[0]['user_id']);
    user_inc_post_count($owner['user_id']);
    $topic_info = topic_get_info($post_info[0]['topic_id']);
    _mysql_prepared_query(array(
        "query" => "UPDATE post SET user_id=:user_id, username=:username WHERE id=:pid",
        "params" => array(
            ":user_id" => $owner['user_id'],
            ":username" => $owner['username'],
            ":pid" => $post
        )
    ));
    if ($topic_info[0]['first_post_id'] == $post) {
        topic_update_first_post($topic_info[0]['topic_id']);
    }
    if ($topic_info[0]['last_post_id'] == $post) {
        topic_update_last_post($topic_info[0]['topic_id']);
    }
    forum_update_statistics_relative($post_info[0]['forum_id'], 0, 0);
}


/** Gets topic owner
 * @param int $id topic id
 * @return int topic owner id
 */
function topic_get_owner($id)
{
    $info = topic_get_info($id);
    return $info[0]['Poster'];
}


/**
 * Locks or unlocks topic for editing
 * @param int $id topic id
 * @param int $lock lock or unlock
 *                  0 - unlock
 *                  1 - lock
 */
function topic_lock($id, $lock)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET locked=:locked WHERE topic_id=:tid",
        "params" => array(
            ":locked" => $lock,
            ":tid" => $id
        )
    ));
}


/**
 * Hides topic from normal users but does not make it inaccessible like unapproved post is
 * @param int $id topic id
 * @param int $hide hide or unhide
 *                  0 - unhide
 *                  1 - hide
 */
function topic_hide($id, $hide)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET hidden=:hidden WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $id,
            ":hidden" => $hide
        )
    ));
}


/**
 * Sets topic type
 * @param int $id topic id
 * @param $type - topic type
 *                0 - normal
 *                1 - sticky
 *                2 - announce
 */
function topic_set_type($id, $type)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET type=:type WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $id,
            ":type" => $type
        )
    ));
}


/**
 * Sets topic order
 * @param int $id topic id
 * @param int $order new display order
 */
function topic_set_order($id, $order)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET display_order=:display_order WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $id,
            ":display_order" => $order
        )
    ));
}


/**
 * @param $id
 * @param $fid
 */
function topic_set_forum($id, $fid)
{
    $current_forum = topic_get_forum($id);
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET forum_id=:fid WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $id,
            ":fid" => $fid
        )
    ));
    _mysql_prepared_query(array(
        "query" => "UPDATE post SET forum_id=:fid WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $id,
            ":fid" => $fid)
    ));
    $hashtags = topic_get_hashtags($id);
    hastag_update_use_count_topic($hashtags, $current_forum, $fid);
}

function post_get_forum($id)
{
    $info = post_get_info($id);
    return $info[0]['forum_id'];
}

function topic_get_forum($id)
{
    $info = topic_get_info($id);
    return $info[0]['forum_id'];
}


function topic_get_post_count($tid)
{

    $res = _mysql_prepared_query(array(
        "query" => "SELECT COUNT(id) FROM post WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $tid
        )
    ));
    return _mysql_result($res, 0);
}

function topic_delete_comments($id)
{
    $data = get_table_contents('', array(), '', false,
        "SELECT post.id FROM post, topic, forum,  users WHERE post.forum_id IN (SELECT forum_id FROM Forum WHERE forum_id IN (SELECT comments FROM forum WHERE comments > 0)) AND post_title*0 != post_title AND post_title=topic.topic_id AND topic.forum_id = forum.forum_id AND topic.Poster = users.user_id AND  topic.topic_id='" . $id . "' AND topic.forum_id='" . topic_get_forum($id) . "'",
        array('time')
    );
    $ids = array_copy_dimension($data, 'id');
    _mysql_prepared_query(array(
        "query" => "DELETE FROM post WHERE id IN (:id_list)",
        "params" => array(
            ":id_list" => implode_string(', ', $ids)
        )
    ));
}

function topic_delete($id)
{
    //Update users post count
    $sql = "UPDATE users"
        . " INNER JOIN (SELECT user_id, COUNT(user_id) AS c FROM post WHERE topic_id = :tid GROUP BY user_id) AS B"
        . "   ON B.user_id = users.user_id"
        . " SET users.user_post_count = users.user_post_count - B.c";
    _mysql_prepared_query(array(
        "query" => $sql,
        "params" => array(
            ":tid" => $id
        )
    ));

    topic_delete_attachments($id);
    topic_delete_comments($id);
    $fid = topic_get_forum($id);
    $posts = topic_get_post_count($id);
    $hashtags = topic_get_hashtags($id);
    hastag_update_use_count_topic($hashtags, $fid);
    _mysql_prepared_query(array(
        "query" => "DELETE FROM post WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $id
        )
    ));
    _mysql_prepared_query(array(
        "query" => "DELETE FROM topic WHERE topic_id = :tid",
        "params" => array(
            ":tid" => $id
        )
    ));

    forum_update_statistics_relative($fid, -1, $posts * -1);
}

function forum_update_statistics_relative($fid, $topics, $posts)
{
    $result = _mysql_prepared_query(array(
        "query" => "SELECT post.*, user_color FROM post LEFT JOIN users ON users.user_id = post.user_id  WHERE forum_id=:fid AND is_approved=1 ORDER BY time DESC LIMIT 1",
        "params" => array(
            ":fid" => $fid
        )
    ));
    $arr = _mysql_fetch_assoc($result);

    if (!is_array($arr)) {
        $arr = array(
            'id' => '0',
            'time' => '0',
            'post_title' => '',
            'username' => '',
            'user_id' => '0',
            'user_color' => ''
        );
    }
    _mysql_prepared_query(array(
        "query" => "UPDATE forum SET posts=posts+:posts, topics=topics+:topics, last_post_id=:last_post_id, last_post_time=:last_post_time, last_post_title=:last_post_title, last_post_poster_id=:last_post_poster_id, last_post_poster_name=:last_post_poster_name, last_post_poster_color=:last_post_poster_color WHERE forum_id=:fid",
        "params" => array(
            ":posts" => $posts,
            ":topics" => $topics,
            ":last_post_id" => $arr['id'],
            ":last_post_time" => $arr['time'],
            ":last_post_title" => $arr['post_title'],
            ":last_post_poster_id" => $arr['user_id'],
            ":last_post_poster_name" => $arr['username'],
            ":last_post_poster_color" => $arr['user_color'],
            ":fid" => $fid
        )
    ));
}

function forum_update_statistics_absolute($fid)
{
    $result = _mysql_prepared_query(array(
        "query" => "SELECT post.*, user_color FROM post LEFT JOIN users ON users.user_id = post.user_id  WHERE forum_id=:fid AND is_approved=1 ORDER BY time DESC LIMIT 1",
        "params" => array(
            ":fid" => $fid
        )
    ));
    $arr = _mysql_fetch_assoc($result);

    if (!is_array($arr)) {
        $arr = array(
            'id' => '0',
            'time' => '0',
            'post_title' => '',
            'username' => '',
            'user_id' => '0',
            'user_color' => ''
        );
    }
    _mysql_prepared_query(array(
        "query" => "UPDATE forum SET posts=:posts, topics=:topics, last_post_id=:last_post_id, last_post_time=:last_post_time, last_post_title=:last_post_title, last_post_poster_id=:last_post_poster_id, last_post_poster_name=:last_post_poster_name, last_post_poster_color=:last_post_poster_color WHERE forum_id=:fid",
        "params" => array(
            ":posts" => forum_get_post_count($fid),
            ":topics" => forum_get_topic_count($fid),
            ":last_post_id" => $arr['id'],
            ":last_post_time" => $arr['time'],
            ":last_post_title" => $arr['post_title'],
            ":last_post_poster_id" => $arr['user_id'],
            ":last_post_poster_name" => $arr['username'],
            ":last_post_poster_color" => $arr['user_color'],
            ":fid" => $fid
        )
    ));
}

function post_get_topic($id)
{
    $info = post_get_info($id);
    return $info[0]['topic_id'];
}

function topic_get_data($topic_id)
{
    return get_table_contents("post", "ALL", "WHERE topic_id = " . $topic_id . " ORDER BY display_order DESC, time ASC");
}

function topic_get_data_ex($topic_id)
{
    return get_table_contents("", "", "", false, "SELECT post.*, users.*, rank, color, description, groups.name AS group_name, ranks.image AS group_rank_image, ranks.name AS group_rank_name, user_rank.image AS user_rank_image, user_rank.name AS user_rank_name FROM post
        LEFT JOIN users ON users.user_id = post.user_id
        LEFT JOIN groups ON groups.id = users.user_default_group
        LEFT JOIN ranks ON ranks.id = groups.rank
        LEFT JOIN ranks AS user_rank ON user_rank.id = users.user_rank
        WHERE topic_id = " . $topic_id .
        " ORDER BY display_order DESC, time ASC",
        array('time', 'user_join_date', 'edit_time'));
}


function topic_get_info($id)
{
    if($id == null){return null;}
    return get_table_contents("topic", "ALL", "WHERE topic_id = " . $id);
}

function post_get_info($id)
{
    if($id == null){return null;}
    return get_table_contents("post", "ALL", "WHERE id = " . $id);
}

function post_get_topic_id($id)
{
    $ret = get_table_contents("post", "ALL", "WHERE id = " . $id);
    return $ret[0]['topic_id'];
}

function topic_exists($tid)
{
    $res = _mysql_prepared_query(array(
        "query" => "SELECT topic_id FROM topic WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $tid
        )
    ));
    return _mysql_num_rows($res) > 0;
}

function post_action($title, $text, $topic_id, $poster, $poster_name, $forum_id = 0, $post_id = 0, $hash_tags = "", $solved = '0', $check_form = true, $check_approved = true, $check_permissions = true, $check_bbcode = true, $not_locked = false, $not_edit_locked = false, $check_title_length = true, $check_content_length = true, $lock = null)
{
    if ($post_id == 0) {//create or reply if post id is not present
        $id = post_edit_reply($title, $text, $topic_id, $poster, $poster_name, $forum_id, $hash_tags, $solved, $check_form, $check_approved, $check_permissions, $check_bbcode, $not_locked, $not_edit_locked, $check_title_length, $check_content_length, $lock);
        return $id;//return created topic id
    } else {
        $ret = post_edit($post_id, $title, $text, $forum_id, $poster, $hash_tags, $check_form, $check_approved, $check_permissions, $check_bbcode, $not_locked, $not_edit_locked, $check_title_length, $check_content_length, $lock);
        return $ret;
    }
}

function topic_is_locked($id)
{
    $is_locked = _mysql_prepared_query(array(
        "query" => "SELECT locked FROM topic WHERE topic_id = :tid",
        "params" => array(
            ":tid" => $id
        )
    ));

    return _mysql_result($is_locked, 0);
}

function topic_is_hidden($id)
{
    $is_hidden = _mysql_prepared_query(array(
        "query" => "SELECT hidden FROM topic WHERE topic_id = :tid",
        "params" => array(
            ":tid" => $id
        )
    ));

    return _mysql_result($is_hidden, 0);
}

function topic_is_solved($id)
{
    $is_solved = _mysql_prepared_query(array(
        "query" => "SELECT COUNT(*) FROM post WHERE topic_id = :tid AND solved='1'",
        "params" => array(
            ":tid" => $id
        )
    ));

    return _mysql_result($is_solved, 0);
}

function post_set_solved($id, $solved)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE post SET solved=:solved WHERE id=:pid",
        "params" => array(
            ":solved" => $solved,
            ":pid" => $id
        )
    ));

    $tid = post_get_topic($id);
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET solved=:solved WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $tid,
            ":solved" => topic_is_solved($tid) > 0 ? 1 : 0
        )
    ));

}

function post_is_locked($id)
{
    $is_locked = _mysql_prepared_query(array(
        "query" => "SELECT edit_locked FROM post WHERE id = :pid",
        "params" => array(
            ":pid" => $id
        )
    ));

    return _mysql_result($is_locked, 0);
}


function topic_check_permissions($check, $not_locked, $not_edit_locked, $forum_id)
{
    global $notification, $language, $current_user, $msg;
    $ACTION_ALLOWED = false;
    if ($check == "edit_post") { //User is trying to edit own post
        $ACTION_ALLOWED = (has_permission($current_user['permissions'][$forum_id], 'f_edit_own')
            && post_get_owner($_GET['p']) == $current_user['uid']
            && $not_locked
            && $not_edit_locked
            || has_permission($current_user['permissions']['global'], 'm_edit_posts')
            || has_permission($current_user['permissions'][$forum_id], 'm_edit_posts'));
        $notification = $language['notifications']['edit'];
        $msg = $language['notifications']['edit_success'];
    } elseif ($check == "reply_post") {// user is trying to reply
        $ACTION_ALLOWED = (has_permission($current_user['permissions'][$forum_id], 'f_can_reply'));
        $notification = $language['notifications']['reply'];
        $msg = $language['notifications']['post_success'];
    } elseif ($check == "new_post") {// new post
        $ACTION_ALLOWED = (has_permission($current_user['permissions'][$forum_id], 'f_start_new'));
        $notification = $language['notifications']['new'];
        $msg = $language['notifications']['topic_success'];
    } else {
        $notification = "Unknown action";
    }
    return $ACTION_ALLOWED;
}

/**
 * Detects user intention for post
 * @return string
 */
function get_check_name()
{
    if ($_GET['p'] != 0) {
        return "edit_post";
    } elseif ($_GET['id'] != 0 && $_GET['p'] == 0) {
        return "reply_post";
    } elseif ($_GET['id'] == 0 && $_GET['p'] == 0) {
        return "new_post";
    }
}

function validate_post($title, $content, $hash_tags, $forum_id, $check_form = true, $check_approved = true, $check_permissions = true, $check_bbcode = true, $not_locked = false, $not_edit_locked = false, $check_title_length = true, $check_content_length = true, $check_hastags_length = true)
{
    Global $current_user, $forum_id_const, $language, $notification, $site_settings;
    $check_result = array();

    //Check form
    if ($check_form && !form_is_valid($_GET['form'], $_GET['a'])) {
        $notification = $language['notifications']['form'];
        return 0;
    }
    //Check permissions
    if ($check_permissions) {
        if (!topic_check_permissions(get_check_name(), $not_locked, $not_edit_locked, $forum_id)) {
            return 0;
        }
    }

    //Check approved
    $apporoved = '1';
    if ($check_approved) {
        $apporoved = '0';
        if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id]), "m_approve_posts||f_no_approval")) {
            $apporoved = '1';
        }
    }
    $check_result['approved'] = $apporoved;

    //Check bbcode
    $allow_bbcode = '1';
    if ($check_bbcode) {
        $allow_bbcode = '0';
        if ($_POST['allowbb'] == '1' && has_permission($current_user['permissions'][$forum_id], "f_post_bb")) {
            $allow_bbcode = '1';
        }
    }
    $check_result['allowbb'] = $allow_bbcode;

    //Check lengths
    if ($check_title_length) {
        if ($site_settings['min_title_length'] > strlen($title) || strlen($title) > $site_settings['max_title_length']) {
            $notification = $language['notifications']['title_len'];
            return 0;
        }
    }

    if ($check_content_length) {
        if ($site_settings['min_content_length'] > strlen($content) || strlen($content) > $site_settings['max_content_length']) {
            $notification = $language['notifications']['content_len'];
            return 0;
        }
    }

    if ($check_hastags_length) {
        if (strlen($hash_tags) > $site_settings['max_hashtag_length']) {
            $notification = $language['notifications']['hashtag_len'];
            return 0;
        }
    }
    return $check_result;

}

function post_edit_reply($title, $text, $topic_id, $poster, $poster_name, $forum_id = 0, $hash_tags = "", $solved = '0',
                         $check_form = true, $check_approved = true, $check_permissions = true, $check_bbcode = true,
                         $not_locked = false, $not_edit_locked = false, $check_title_length = true,
                         $check_content_length = true, $check_hastags_length = true, $lock = null)
{

    $post_edited = false;
    Global $current_user, $notification, $site_settings;

    $validation_result = validate_post($title, $text, $hash_tags, $forum_id, $check_form, $check_approved,
        $check_permissions, $check_bbcode, $not_locked, $not_edit_locked, $check_title_length, $check_content_length,
        $check_hastags_length);

    if ($validation_result === 0) {
        return 0;
    }

    form_delete_by_id($_GET['form']);

    if (!topic_exists($topic_id)) {
        $topic_id = topic_add($forum_id, $poster, $title, $validation_result['approved'], $solved);
        $forum_id = topic_get_forum($topic_id);
        $post_edited = true;
        if ($topic_id == 0) {
            log_event("USER", $poster_name, $_SERVER['REMOTE_ADDR'], "POST", "Insert statement failed");
            return 0;
        }
    }

    //Check lock
    $lock_post = '0';
    if ($lock != null) {
        $lock_post = $lock;
    }

    hastag_update_use_count($hash_tags, "", $forum_id);

    $sql = "INSERT INTO post VALUES ("
        . "NULL, :tid, :fid, :ip, :time, '0', :allow_bbcode, :author_name, :author_id,"
        . " '-1', '-1', '', '0', :locked, :content, :title, :approved, :hash_tags, :solved, '0')";

    _mysql_prepared_query(array(
        "query" => $sql,
        "params" => array(
            ":tid" => $topic_id,
            ":fid" => $forum_id,
            ":ip" => $_SERVER['SERVER_ADDR'],
            ":time" => time(),
            ":allow_bbcode" => $validation_result['allowbb'],
            ":author_name" => $poster_name,
            ":author_id" => $poster,
            ":locked" => $lock_post,
            ":content" => $text,
            ":title" => $title,
            ":approved" => $validation_result['approved'],
            ":hash_tags" => $hash_tags,
            ":solved" => $solved
        )
    ));

    $insert = _mysql_insert_id();
    forum_update_statistics_relative($forum_id, 0, 1);
    if ($insert == 0) {
        log_event("USER", $poster_name, $_SERVER['REMOTE_ADDR'], "POST", "Insert statement failed");
    }

    if ($post_edited) {
        topic_update_first_post($topic_id);
    } else {
        _mysql_prepared_query(array(
            "query" => "UPDATE topic SET Replies=Replies+1 WHERE topic_id=:tid",
            "params" => array(
                ":tid" => $topic_id
            )
        ));

    }
    topic_update_last_post($topic_id);
    user_inc_post_count($poster);

    return $insert;
}


function post_edit($post_id, $title, $content, $forum_id, $user_id, $hash_tags = "", $check_form = true, $check_approved = true,
                   $check_permissions = true, $check_bbcode = true, $not_locked = false, $not_edit_locked = false,
                   $check_title_length = true, $check_content_length = true, $lock = null)
{

    global $current_user, $notification, $site_settings, $forum_info;
    $topic_id = post_get_topic($post_id);
    $result = _mysql_prepared_query(array(
        "query" => "SELECT first_post_id, last_post_id FROM topic WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $topic_id
        )
    ));

    $first_last_post = _mysql_fetch_assoc($result);
    $validation_result = validate_post($title, $content, $hash_tags, $forum_id, $check_form, $check_approved,
        $check_permissions, $check_bbcode, $not_locked, $not_edit_locked, $check_title_length,
        $check_content_length, $post_id);

    if ($validation_result === 0) {
        return 0;
    }
    form_delete_by_id($_GET['form']);

    //Check lengths
    if ($check_title_length) {
        if ($site_settings['min_title_length'] > strlen($title) || strlen($title) > $site_settings['max_title_length']) {
            return 0;
        }
    }

    if ($check_content_length) {
        if ($site_settings['min_content_length'] > strlen($content) || strlen($content) > $site_settings['max_content_length']) {
            return 0;
        }
    }

    //Check bbcode
    $allow_bbcode = '1';
    if ($check_bbcode) {
        $allow_bbcode = '0';
        if ($_POST['allowbb'] == '1' && has_permission($current_user['permissions'][$forum_id], "f_post_bb")) {
            $allow_bbcode = '1';
        }
    }

    //Check lock
    $lock_post = '';
    if ($lock != null) {
        $lock_post = ", edit_locked='" . $lock . "'";
    }

    $original = post_get_info($post_id);
    hastag_update_use_count($hash_tags, $original[0]["hashtags"], $forum_id);

    _mysql_prepared_query(array(
        "query" => "UPDATE post SET post_title=:title, data=:content, edit_count=edit_count+1, edit_user_id=:user_id, edit_time=:time,"
            . " hashtags=:hash_tags, bbcode=:allow_bbcode, edit_locked=:edit_locked WHERE id=:pid",
        "params" => array(
            ":title" => $title,
            ":content" => $content,
            ":user_id" => $user_id,
            ":time" => time(),
            ":hash_tags" => $hash_tags,
            ":allow_bbcode" => $allow_bbcode,
            ":edit_locked" => $lock_post,
            ":pid" => $post_id
        )
    ));

    if ($forum_info[0]['last_post_id'] == $post_id && $topic_id > 0) {
        forum_update_statistics_relative($forum_id, 0, 0);
    }
    if ($post_id == $first_last_post['first_post_id'] && $topic_id > 0) {
        topic_update_first_post($topic_id);
    }
    return $post_id;
}

function topic_add($forum_id, $Poster, $title, $approved, $solved = '0')
{
    $user = user_get_info_by_id($Poster);
    forum_update_statistics_relative($forum_id, 1, 0);
    _mysql_prepared_query(array(
        "query" => "INSERT INTO topic VALUES (NULL, :fid, :time, :user_id, 0, 0, 0, :title, :time, 0, 0, :approved, 0, :solved ,0, 0, :user_name, :user_color, :user_id, :user_name, :user_color)",
        "params" => array(
            ":fid" => $forum_id,
            ":time" => time(),
            ":user_id" => $user[0]['user_id'],
            ":title" => $title,
            ":approved" => $approved,
            ":solved" => $solved,
            ":user_name" => $user[0]['username'],
            ":user_color" => $user[0]['user_color']

        )
    ));

    return _mysql_insert_id();
}

function post_delete_attachments($id)
{
    if ($id <= 0) {
        return false;
    }
    global $root_dir;
    $attachments = post_get_attachments($id);
    for ($i = 0; $i < count($attachments); $i++) {
        unlink($root_dir . '/uploads/' . $attachments[$i]['actual_name']);
        if ($attachments[$i]['is_image'] == 1) {
            unlink($root_dir . '/images/large/' . $attachments[$i]['actual_name']);
            unlink($root_dir . '/images/small/' . $attachments[$i]['actual_name']);
        }
    }
    _mysql_prepared_query(array(
        "query" => "DELETE FROM attachments WHERE post_id=:pid",
        "params" => array(
            ":pid" => $id
        )
    ));

}


function topic_delete_attachments($id)
{
    if ($id <= 0) {
        return false;
    }
    global $root_dir;
    $attachments = topic_get_attachments($id);
    for ($i = 0; $i < count($attachments); $i++) {
        @unlink($root_dir . '/uploads/' . $attachments[$i]['actual_name']);
        if ($attachments[$i]['is_image'] == 1) {
            unlink($root_dir . '/images/large/' . $attachments[$i]['actual_name']);
            unlink($root_dir . '/images/small/' . $attachments[$i]['actual_name']);
        }
    }
    _mysql_prepared_query(array(
        "query" => $sql = "DELETE FROM attachments WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $id
        )
    ));

}

function topic_move_attachments($id, $target_forum)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE attachments SET forum_id=:target_forum WHERE topic_id=:tid",
        "params" => array(
            ":target_forum" => $target_forum,
            ":tid" => $id
        )
    ));

}

function post_delete($id)
{
    $result = _mysql_prepared_query(array(
        "query" => "SELECT topic.topic_id,last_post_id,first_post_id, user_id, hashtags FROM post, topic WHERE post.topic_id = topic.topic_id AND post.id=:pid",
        "params" => array(
            ":pid" => $id
        )
    ));

    $row = _mysql_fetch_assoc($result);

    $fid = post_get_forum($id);
    hastag_update_use_count("", $row["hashtags"], $fid);
    user_dec_post_count($row['user_id']);
    _mysql_prepared_query(array(
        "query" => "DELETE FROM post WHERE id=:pid",
        "params" => array(
            ":pid" => $id
        )
    ));

    forum_update_statistics_relative($fid, 0, -1);
    if ($row['last_post_id'] == $id && $row['first_post_id'] == $id) {
        topic_delete($row['topic_id']);
        return 1;
    } elseif ($row['first_post_id'] == $id) { //First post is deleted;
        topic_update_first_post($row['topic_id']);
    } elseif ($row['last_post_id'] == $id) {
        topic_update_last_post($row['topic_id']);
    }
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET Replies=Replies-1 WHERE topic_id=:tid",
        "params" => array(
            ":tid" => $row['topic_id']
        )
    ));

    post_delete_attachments($id);

    return 0;
}


function post_open_report($id, $msg)
{
    global $current_user;
    $a = _mysql_prepared_query(array(
        "query" => "UPDATE post SET reported=1 WHERE id=:pid",
        "params" => array(
            ":pid" => $id
        )
    ));


    $b = _mysql_prepared_query(array(
        "query" => "INSERT INTO report VALUES (NULL, :pid, :time, :user_id, '','', :msg)",
        "params" => array(
            ":pid" => $id,
            ":time" => time(),
            ":user_id" => $current_user['uid'],
            ":msg" => $msg
        )
    ));

    return $a && $b;
}

function post_report_close($id)
{
    global $current_user;
    $a = _mysql_prepared_query(array(
        "query" => "UPDATE post SET reported=0 WHERE id=:pid",
        "params" => array(
            ":pid" => $id
        )
    ));

    $b = _mysql_prepared_query(array(
        "query" => "UPDATE report SET closer=:user_id, close_time=:time WHERE post_id=:pid AND close_time=0",
        "params" => array(
            ":pid" => $id,
            ":time" => time(),
            ":user_id" => $current_user['uid'],
        )
    ));

    return $a && $b;
}

function post_view_report($id)
{
    global $site_settings;
    $res = _mysql_prepared_query(array(
        "query" => "SELECT * FROM report WHERE post_id=:pid",
        "params" => array(
            ":pid" => $id,
        )
    ));

    $ret = _mysql_fetch_assoc($res);
    $ret['time'] = date($site_settings['time_format'], $ret['time']);
    return $ret;
}


function topic_get_post_actions($post, $has_topic = true, $js = false, $topic = null)
{

    $id = $post['id'];
    global $current_user;
    $not_locked = 'true'; // comments doesnt have topic
    if ($has_topic) {
        if ($topic == null) {
            $not_locked = topic_is_locked($post['topic_id']) == 0 ? "true" : "false";
        } else {
            $not_locked = $topic[0]['locked'] == 0 ? "true" : "false";
        }
    }
    $not_edit_locked = $post['edit_locked'] == '0' ? "true" : "false";
    $ret = Array();
    $questionmark = "";
    if (!strstr($_SERVER['REQUEST_URI'], '?')) {
        $questionmark = "?";
    }


    $topic_owner = 'false';
    if ($topic == null) {
        $poster_id = topic_get_owner(post_get_topic($_GET['p']));
    } else {
        $poster_id = $topic[0]['Poster'];
    }
    if ($has_topic && $poster_id == $current_user['uid']) {
        $topic_owner = 'true';
    }
    $is_owner = $current_user['uid'] == $post['user_id'];
    $is_owner = $is_owner ? 'true' : 'false';
    $link_start = "href=";
    $link_end = "";
    if ($js) {
        $link_start = 'href="#" onclick="parseLink(';
        $link_end = '); return false;"';
    }
    if (stristr($_SERVER['REQUEST_URI'], "a=")) {
        $_SERVER['REQUEST_URI'] = preg_replace("/\&a=[a-z]+/", "", $_SERVER['REQUEST_URI']);
    }
    if (stristr($_SERVER['REQUEST_URI'], "p=")) {
        $_SERVER['REQUEST_URI'] = preg_replace("/\&p=\d*/", "", $_SERVER['REQUEST_URI']);
    }
    $links = Array(
        'f_edit_own&&' . $is_owner . '&&' . $not_locked . '&&' . $not_edit_locked . '||m_edit_posts' => '<a class="lpadding" ' . $link_start . "'" . $_SERVER['REQUEST_URI'] . $questionmark . '&a=edit&p=' . $id . "'" . $link_end . '>edit</a>',
        'f_delete_own&&' . $is_owner . '&&' . $not_locked . '&&' . $not_edit_locked . '||m_delete_posts' => '<a class="lpadding" style="color: #cc0000" ' . $link_start . "'" . $_SERVER['REQUEST_URI'] . $questionmark . '&a=delete&p=' . $id . "'" . $link_end . '>delete</a>',
        'm_issue_warning' => '<a class="lpadding" href="./lib/mcp.php?a=warn_user&p=' . $id . '">warn</a>',
        'm_change_post_author||m_view_post_details' => '<a class="lpadding" href="./lib/mcp.php?a=post_info&p=' . $id . '">details</a>',
        'm_approve_posts&&' . $post['is_approved'] . '==0' => '<a class="lpadding" ' . $link_start . "'" . $_SERVER['REQUEST_URI'] . $questionmark . '&a=approve&p=' . $id . "'" . $link_end . '>approve</a>',
        'm_close_reports&&' . $post['reported'] . '==1' => '<a class="lpadding" ' . $link_start . "'" . $_SERVER['REQUEST_URI'] . $questionmark . '&a=viewreport&p=' . $id . "'" . $link_end . '>viewreport</a>',
        '(m_close_reports||f_can_report)&&' . $post['reported'] . '==0' => '<a class="lpadding" ' . $link_start . "'" . $_SERVER['REQUEST_URI'] . $questionmark . '&a=report&p=' . $id . "'" . $link_end . '>report</a>',
        '((f_mark_solved&&' . $topic_owner . ')||m_mark_solved)&&' . $post['solved'] . '==0' => '<a class="lpadding" ' . $link_start . '"' . $_SERVER['REQUEST_URI'] . $questionmark . '&a=solve&p=' . $id . '"' . $link_end . '>solve</a>',
        '((f_mark_solved&&' . $topic_owner . ')||m_mark_solved)&&' . $post['solved'] . '==1' => '<a class="lpadding" ' . $link_start . '"' . $_SERVER['REQUEST_URI'] . $questionmark . '&a=unsolve&p=' . $id . '"' . $link_end . '>unsolve</a>',
    );
    $merged = array_merge_nulls_as_empty_array($current_user['permissions'][$post['forum_id']], $current_user['permissions']['global']);
    foreach ($links as $key => $value) {
        if (has_permission($merged, $key)) {
            array_push($ret, $value);
        }
    }

    $ret = array_unique($ret);

    $str = "";
    foreach ($ret as $value) {
        $str .= $value . " ";
    }
    return $str;
}