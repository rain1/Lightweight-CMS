<?php
class manage_attachments{
    var $module_info = array(
        'title' => "manage_attachments",
        'MODULES' => array(
            array('name' => 'view_attachments','permissions' => 'u_manage_attachments'),
            array('name' => 'view_orphaned','permissions' => 'u_manage_attachments')
        )
    );
    

    function main($module){
        global $current_user, $language;
        switch($module){
            case 'view_attachments':
                $this->page_title = $language['module_titles']['manage_attachments'];
                $this->template = "manage_orphaned";
                $orphaned = @get_table_contents("attachments", "ALL"," WHERE user_id='".$current_user['uid']."'");
                $table_start =  '<table id="oprhaned" class="sortable"><td><b>Download</b></td><td><b>size</b></td><td><b>time</b></td><td><b>Download count</b></td><td><b>View post</b></td><tr>';
                $table_end = '</table>';
                $table_middle = "";
                for($i = 0; $i < count($orphaned);$i++){
                    $table_middle .= '<tr><td><a href="./upload.php?a=download&file='.$orphaned[$i]['id'].'">'.$orphaned[$i]['file_name'].'</a></td>
                        <td>'.bytes_to_size($orphaned[$i]['size']).'</td><td>'.$orphaned[$i]['time'].'</td><td>'.$orphaned[$i]['downloads'].'</td><td><a href="../?p='.$orphaned[$i]['post_id'].'"">view</a></td></tr>';
                }
                $this->vars=array(
                    'ATTACHMENTS' => $table_start.$table_middle.$table_end,
                );        
            break;
            case 'view_orphaned':
                $this->page_title = $language['module_titles']['manage_orphaned_attachments'];
                $this->template = "manage_orphaned";
                $orphaned = @get_table_contents("", "ALL", "", False, "SELECT * FROM (SELECT attachments.*, post.id AS pid FROM attachments LEFT OUTER JOIN post ON attachments.post_id=post.id) AS t1 WHERE pid IS NULL AND t1.user_id='".$current_user['uid']."'");
                $table_start =  '<table id="oprhaned" class="sortable"><tr><td><b>Download</b></td><td><b>size</b></td><td><b>time</b></td><td><b>Download count</b></td><tr>';
                $table_end = '</table>';
                $table_middle = "";
                for($i = 0; $i < count($orphaned);$i++){
                    $table_middle .= '<tr><td><a href="./upload.php?a=download&file='.$orphaned[$i]['id'].'">'.$orphaned[$i]['file_name'].'</a></td>
                        <td>'.$orphaned[$i]['size'].'</td><td>'.$orphaned[$i]['time'].'</td><td>'.$orphaned[$i]['downloads'].'</td></tr>';
                }
                $this->vars=array(
                    'ATTACHMENTS' => $table_start.$table_middle.$table_end,
                );  
            break;
        }
    }
}