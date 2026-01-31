<?php

function forum_list_all($fields = 'ALL', $none = false)
{
    $ret = array();
    if ($none) {
        $ret[0] = array(
            'forum_id' => '0',
            'forum_name' => 'none'
        );
    }
    $ret = array_merge_nulls_as_empty_array($ret, get_table_contents("forum", $fields));
    return $ret;
}

function forum_get_last_post()
{
}

function forum_get_last_poster()
{
}

function forum_get_post_count($fid)
{
    $result = _mysql_prepared_query(array(
        "query" => "SELECT COUNT(id) FROM post WHERE forum_id=:fid",
        "params" => array(
            ":fid" => $fid
        )
    ));
    return _mysql_result($result, 0);
}

function forum_get_topic_count($fid)
{
    $result = _mysql_prepared_query(array(
        "query" => "SELECT COUNT(topic_id) FROM topic WHERE forum_id=:fid",
        "params" => array(
            ":fid" => $fid
        )
    ));
    return _mysql_result($result, 0);
}

function forum_get_topics($forum_id, $start = -1, $end = -1)
{
    return get_table_contents("topic", 'ALL', "WHERE forum_id='" . $forum_id . "'");
}

function forum_get_allowed_topics($forum_id, $start = -1, $end = -1, $show_hidden = true)
{
    global $current_user, $forum_id_const;
    $sql_add = "";
    if (has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id]), "m_approve_posts")) {
        $sql_add .= ' AND is_approved=1';
    }
    if (!$show_hidden) {
        $sql_add .= ' AND hidden=0';
    }
    $topics = get_table_contents("", "", "", false, "SELECT * FROM topic WHERE forum_id=" . $forum_id . $sql_add . " ORDER BY type DESC, display_order DESC, last_post_time DESC", array('time', 'last_post_time'));
    return $topics;
}


function forum_get_comments($id)
{
    $data = get_table_contents('', array(), '', false,
        "SELECT post.*, topic.topic_id AS tid, topic.forum_id AS fid, topic.title AS tt, forum.forum_name, users.username FROM post, topic, forum,  users WHERE post.forum_id IN (SELECT forum_id FROM Forum WHERE forum_id IN (SELECT comments FROM forum WHERE comments > 0)) AND post_title*0 != post_title AND post_title=topic.topic_id AND topic.forum_id = forum.forum_id AND topic.Poster = users.user_id AND topic.forum_id = '" . $id . "'",
        array('time')
    );
    return $data;
}

function forum_get_comments_list($id)
{
    $comments = forum_get_comments($id);
    return array_copy_dimension($comments, 'id');
}

function forum_list_all_comment_forums()
{
    $result = _mysql_query("SELECT comments FROM forum WHERE comments > 0");
    $arr = array();
    if ($result) {
        while ($row = _mysql_fetch_array($result)) {
            $arr[] = $row[0];
        }
    }
    return $arr;
}


function forum_move_forums($source, $destination)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE forum SET parent_id=:destination WHERE forum_id=:source",
        "params" => array(
            ":destination" => $destination,
            ":source" => $source
        )
    ));
}

function forum_move_topics($source, $destination)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE topic SET forum_id=:destination WHERE forum_id=:source",
        "params" => array(
            ":destination" => $destination,
            ":source" => $source
        )
    ));
}

function forum_get_children($id)
{
    return get_table_contents("forum", "ALL", "WHERE parent_id=" . $id);
}

function forum_get_children_by_type($id, $type)
{
    return get_table_contents("forum", "ALL", "WHERE parent_id=" . $id . " AND forum_type='" . $type . "'");
}

function forum_get_child_list($id)
{
    $childs = forum_get_children($id);
    return array_copy_dimension($childs, 'forum_id');
}

function forum_get_child_list_by_type($id, $type)
{
    $childs = forum_get_children_by_type($id, $type);
    return array_copy_dimension($childs, 'forum_id');
}


function forum_get_type($id)
{
    $forum_info = forum_get_info($id);
    return $forum_info[0]['forum_type'];
}

function forum_get_attachments($id)
{
    return get_table_contents("attachments", "ALL", "WHERE forum_id=" . $id);
}

function forum_delete_attachments($id)
{
    if ($id <= 0) {
        return false;
    }
    global $root_dir;
    $attachments = forum_get_attachments($id);
    for ($i = 0; $i < count($attachments); $i++) {
        unlink($root_dir . '/uploads/' . $attachments[$i]['actual_name']);
        if ($attachments[$i]['is_image'] == 1) {
            unlink($root_dir . '/images/large/' . $attachments[$i]['actual_name']);
            unlink($root_dir . '/images/small/' . $attachments[$i]['actual_name']);
        }
    }
    _mysql_prepared_query(array(
        "query" => "DELETE FROM attachments WHERE forum_id=:fid",
        "params" => array(
            ":fid" => $id
        )
    ));
}

function forum_move_attachments($id, $target_forum)
{
    _mysql_prepared_query(array(
        "query" => "UPDATE attachments SET forum_id=:fid WHERE topic_id=:tid",
        "params" => array(
            ":fid" => $target_forum,
            ":tid" => $id
        )
    ));
}

function forum_remove($id, $subforums = false)
{
    _mysql_prepared_query(array(
        "query" => "DELETE FROM topic WHERE forum_id=:fid",
        "params" => array(
            ":fid" => $id
        )
    ));
    $childs = ForumListChilds($id);
    for ($i = 0; $i < count($childs); $i++) {
        forum_remove($childs[$i], true);
    }
}

function forum_exists($id)
{
    $result = _mysql_prepared_query(array(
        "query" => "SELECT 1 FROM forum WHERE forum_id=:fid",
        "params" => array(
            ":fid" => $id
        )
    ));
    return _mysql_num_rows($result) == 1;
}

function forum_copy_permissions($source_id, $target_id)
{
    _mysql_prepared_query(array(
        "query" => "DELETE FROM group_permissions WHERE forum_id=:target",
        "params" => array(
            ":target" => $target_id
        )
    ));
    _mysql_prepared_query(array(
        "query" => "INSERT INTO group_permissions SELECT group_id, :target AS forum_id, permission_id FROM group_permissions WHERE forum_id=:source",
        "params" => array(
            ":target" => $target_id,
            ":source" => $source_id
        )
    ));
}

function forum_add($type, $parent, $name, $description, $comment, $display, $display_order, $password, $google_fragment)
{
    _mysql_prepared_query(array(
        "query" => "INSERT INTO forum VALUES (NULL, :parent, :name, :description, :type, :comment, :display, :display_order, :password, '0', '0', '0', '0', '', '0', '', '', :google_fragment)",
        "params" => array(
            ":parent" => $parent,
            ":name" => $name,
            ":description" => $description,
            ":type" => $type,
            ":comment" => $comment,
            ":display" => $display,
            ":display_order" => $display_order,
            ":password" => $password,
            ":google_fragment" => $google_fragment
        )
    ));
    $insert_id = _mysql_insert_id();
    if ($_POST['permissions_id'] > 0) {
        forum_copy_permissions($_POST['permissions_id'], $insert_id);
    }
    return $insert_id;
}

function forum_edit($id, $type, $parent, $name, $description, $comment, $display, $password, $google_fragment)
{
    $old_password = forum_get_info($id);
    $old_password = $old_password[0]['forum_password'];
    _mysql_prepared_query(array(
        "query" => "UPDATE forum SET parent_id=:parent, forum_name=:name, description=:description, forum_type=:type, comments=:comment, display=:display, forum_password=:password, google_fragment=:google_fragment WHERE forum_id=:fid",
        "params" => array(
            ":parent" => $parent,
            ":name" => $name,
            ":description" => $description,
            ":type" => $type,
            ":comment" => $comment,
            ":display" => $display,
            ":password" => $password,
            ":google_fragment" => $google_fragment,
            ":fid" => $id
        )
    ));
    if ($_POST['permissions_id'] > 0) {
        forum_copy_permissions($_POST['permissions_id'], $id);
    }
    if ($password != $old_password) {
        _mysql_prepared_query(array(
            "query" => "DELETE FROM forum_session WHERE forum_id=:fid",
            "params" => array(
                ":fid" => $id
            )
        ));
    }
}

function forum_list_permissions($id)
{
    $sql_custom = "SELECT group_id, permissions.permission_id, permission_class, name FROM group_permissions
LEFT JOIN  permissions
ON permissions.permission_id = group_permissions.permission_id 
WHERE forum_id='$id' AND founder = 0 
ORDER BY group_id ASC";
    return get_table_contents(NULL, NULL, NULL, false, $sql_custom);
}

/*
 * Where users can post
 * f_read_forum 38
 * f_start_new 35
 */
function get_allowed_forums_combo()
{
    global $current_user;
    $sql_custom = "SELECT forum_id, forum_name FROM forum WHERE forum_id IN(SELECT DISTINCT forum_id FROM group_permissions WHERE permission_id IN (35,38) AND group_id IN(" . implode(",", user_get_groups($current_user['uid'])) . "))";
    $tbl = get_table_contents(NULL, NULL, NULL, false, $sql_custom);
    return forum_list_to_combo($tbl);
}

function get_allowed_forums($parent_id = '-1', $hidden = false, $type = 'ALL', $ignore_cache = false)
{
    global $current_user;
    if ($current_user['allowed_forums'] == null || $ignore_cache) {
        $parent = '';
        if ($parent_id != '-1') {
            $parent = " parent_id='" . $parent_id . "' AND ";
        }
        $display = "";
        if (!$hidden) {
            $display = " AND display=1 ";
        }
        $f_type = "";
        if ($type != "ALL") {
            $f_type = "forum_type='" . $type . "' AND ";
        }
        $SQL_custom = "SELECT * FROM forum WHERE " . $f_type . " forum_id IN(SELECT DISTINCT forum_id FROM group_permissions WHERE $parent permission_id IN (35,38) " . $display . " AND  group_id IN(" . implode(",", $current_user['groups']) . ")) ORDER BY display_order DESC, forum_id ASC";
        $tbl = get_table_contents(NULL, NULL, NULL, false, $SQL_custom, array('last_post_time'));
        return $tbl;
    } else {
        $ret = array();
        for ($i = 0; $i < count($current_user['allowed_forums']); $i++) {
            $cond = true;
            if ($parent_id != '-1') {
                $cond = $cond && $current_user['allowed_forums'][$i]['parent_id'] == $parent_id;
            }
            if (!$hidden) {
                $cond = $cond && $current_user['allowed_forums'][$i]['display'] == "1";
            }
            if ($type != "ALL") {
                $cond = $cond && $current_user['allowed_forums'][$i]['forum_type'] == $type;
            }
            if ($cond) {
                $ret[] = $current_user['allowed_forums'][$i];
            }
        }
        return $ret;
    }
}

function forum_get_path_linked($id)
{
    $parents = forum_get_path($id);
    $links = "";
    for ($i = 0; $i < count($parents); $i++) {
        $links .= '<a class="path" href="./?f=' . $parents[$i]['forum_id'] . '" >' . $parents[$i]['forum_name'] . '</a> > ';
    }
    return $links;
}

function forum_get_path($id, $path = "")
{
    $parent = forum_get_parent($id);
    $parents = array();
    while (true) {
        if (!$parent) {
            break;
        }
        $parents[] = array(
            'forum_id' => $parent[0]['forum_id'],
            'forum_name' => $parent[0]['forum_name']
        );
        $parent = forum_get_parent($parent[0]['forum_id']);
    }

    $parents = array_reverse($parents);

    return $parents;
}


function forum_get_parent($id)
{
    $forum_info = forum_get_info($id);
    $parent = forum_get_info($forum_info[0]['parent_id']);
    return $parent;
}

function forum_get_info($forum_id, $debug = false)
{
    if (is_array($forum_id)) {
        $forum_id = implode(",", $forum_id);
    }
    if($forum_id == null){
        $forum_id = "NULL";
    }
    return get_table_contents("forum", "ALL", "WHERE forum_id IN (" . $forum_id . ") ", $debug);
}

function forum_get_name_by_id($forum_id)
{
    $ret = get_table_contents("forum", array('forum_name'), "WHERE forum_id = " . $forum_id);
    return $ret[0]['forum_name'];
}

function forum_get_actions($id, $topic = 0)
{
    global $current_user, $not_locked, $forum_id_const;
    $not_locked = to_bool($not_locked);
    $ret = Array();
    $links = Array(
        'f_start_new' => '<a class="forum_action" href="./?a=new&f=' . $id . '">new</a>',
    );
    if ($topic > 0 && ($not_locked || has_permission(array_merge_nulls_as_empty_array($current_user['permissions']['global'], $current_user['permissions'][$forum_id_const]), "m_edit_posts"))) {
        $links['f_can_reply'] = '<a class="forum_action" href="./?a=reply&f=' . $id . '&id=' . $topic . '">reply</a>';
    }

    foreach ($links as $key => $value) {
        if (has_permission($current_user['permissions'][$id], $key)) {
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

function forum_list_to_combo($forum_list = 0)
{
    if ($forum_list == 0) {
        $forum_list = forum_list_all(array('forum_id', 'forum_name'));
    }
    return array_to_combo($forum_list, 'forum_name', 'forum_id');
}

function forum_list_custom()
{
    $sql_custom =
        "SELECT forum. * , COUNT( post.forum_id ) AS posts, ("
        . "    SELECT COUNT( topic.topic_id )"
        . "     FROM topic"
        . "     WHERE topic.forum_id = post.forum_id"
        . " ) AS topics, p.post_title, p.user_id, p.time, users.username, p.id"
        . " FROM forum"
        . " LEFT JOIN post ON forum.forum_id = post.forum_id"
        . " LEFT JOIN ("
        . "     SELECT post_title, user_id, forum_id, time, id"
        . "     FROM post"
        . "     WHERE id"
        . "     IN ("
        . "         SELECT MAX( id )"
        . "         FROM post"
        . "         GROUP BY forum_id"
        . "     )"
        . " ) AS p ON forum.forum_id = p.forum_id"
        . " LEFT JOIN topic ON post.topic_id = topic.topic_id"
        . " LEFT JOIN users ON p.user_id = users.user_id"
        . " LIMIT 0 , 30";

//$SQL_LastPost = "SELECT post_title, user_id FROM post WHERE id IN (SELECT MAX(id) FROM post GROUP BY forum_id)"

    return get_table_contents(NULL, NULL, NULL, false, $sql_custom);
}


function forum_delete($forum_id, $childs, $new_parent)
{
    global $current_user;
    $forum_name = forum_get_name_by_id($forum_id);
    log_event('ADMINISTRATOR', $current_user['name'], $_SERVER['REMOTE_ADDR'], "MANAGE forums", 'Deleted forum named ' . $forum_name);
    if ($childs == "move") {
        _mysql_prepared_query(array(
            "query" => "UPDATE topic SET forum_id=:new_parent WHERE forum_id=:forum_id",
            "params" => array(
                ":new_parent" => $new_parent,
                ":forum_id" => $forum_id
            )
        ));
        _mysql_prepared_query(array(
            "query" => "UPDATE post SET forum_id=:new_parent  WHERE forum_id=:forum_id",
            "params" => array(
                ":new_parent" => $new_parent,
                ":forum_id" => $forum_id
            )
        ));
        _mysql_prepared_query(array(
            "query" => "UPDATE attachments SET forum_id=:new_parent WHERE forum_id=:forum_id",
            "params" => array(
                ":new_parent" => $new_parent,
                ":forum_id" => $forum_id
            )
        ));
        _mysql_prepared_query(array(
            "query" => "UPDATE forum SET parent_id=:new_parent WHERE parent_id=:forum_id",
            "params" => array(
                ":new_parent" => $new_parent,
                ":forum_id" => $forum_id
            )
        ));
        forum_update_statistics_absolute($new_parent);
        _mysql_prepared_query(array(
            "query" => "DELETE FROM forum WHERE forum_id=:forum_id",
            "params" => array(
                ":forum_id" => $forum_id
            )
        ));
        if ($forum_id != "" && $forum_id != "0") {
            _mysql_prepared_query(array(
                "query" => "DELETE FROM group_permissions WHERE forum_id=:forum_id",
                "params" => array(
                    ":forum_id" => $forum_id
                )
            ));
        }
    } else {
        $comments = implode(", ", forum_get_comments_list($forum_id));
        $sql = "UPDATE users"
            . " INNER JOIN (SELECT user_id, COUNT(user_id) AS c FROM post WHERE forum_id = :forum_id GROUP BY user_id) AS B"
            . " ON B.user_id = users.user_id"
            . " SET users.user_post_count = users.user_post_count  -  B.c";

        _mysql_prepared_query(array(
            "query" => $sql,
            "params" => array(
                ":forum_id" => $forum_id
            )
        ));
        _mysql_prepared_query(array(
            "query" => "DELETE FROM topic WHERE forum_id=:forum_id",
            "params" => array(
                ":forum_id" => $forum_id
            )
        ));
        _mysql_prepared_query(array(
            "query" => "DELETE FROM post WHERE forum_id=:forum_id",
            "params" => array(
                ":forum_id" => $forum_id
            )
        ));
        _mysql_prepared_query(array(
            "query" => "DELETE FROM forum WHERE forum_id=:forum_id",
            "params" => array(
                ":forum_id" => $forum_id
            )
        ));
        if ($forum_id != "" && $forum_id != "0") {
            _mysql_prepared_query(array(
                "query" => "DELETE FROM group_permissions WHERE forum_id=:forum_id",
                "params" => array(
                    ":forum_id" => $forum_id
                )
            ));
        }
        $sql = "UPDATE users"
            . " INNER JOIN (SELECT user_id, COUNT(user_id) AS c FROM post WHERE id IN  (:comments) GROUP BY user_id) AS B"
            . " ON B.user_id = users.user_id"
            . " SET users.user_post_count = users.user_post_count - B.c";
        _mysql_prepared_query(array(
            "query" => $sql,
            "params" => array()
        ));
        _mysql_prepared_query(array(
            "query" => "DELETE FROM post WHERE id IN (:comments)",
            "params" => array(
                ":comments" => $comments
            )
        ));

        forum_delete_attachments($forum_id);
        $child_forums = forum_get_child_list($forum_id);
        if (count($childs) > 0) {
            for ($i = 0; $i < count($child_forums); $i++) {
                forum_delete($child_forums[$i], $childs, $new_parent);
            }
        }
    }
}