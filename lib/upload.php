<?php
error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR | E_PARSE | E_USER_WARNING | E_USER_ERROR);
$connection = -1;
define('CHUNK_SIZE', 1024 * 1024); // size (in bytes) of tiles chunk

//System wide variables
$DB_CONNECTED = false;
$width = 0;
$height = 0;

$OPT_NOLIST = true;
$tab_id = stripslashes(strip_tags($_GET["id"]));
$user_name = stripslashes(strip_tags($_GET["u"]));
$action = $_GET["a"];
$group = stripslashes(strip_tags($_GET["g"]));

if (!function_exists("ConnectdataBase")) {
    include_once("../settings.php");
    include_once("./funcs.php");
    $DB_CONNECTION = ConnectdataBase();
    include_once "../globals.php";
    include_once("./groups.php");
    include_once("./users.php");
    include_once("./forms.php");
    include_once("./login.php");
    include_once("./tobase.php");
    include_once("./view_forum.php");
    include_once("./view_topic.php");
    include_once("./modules.php");
    include_once("./permissions.php");
}
$p = "";
if (isset($_GET['p'])) {
    $p = $_GET['p'];
    if ($p != "") {
        $p = "&p=" . $p;
    }
}
sanitarize_input();
if (!isset($_GET['a'])) {
    $data = file_get_contents("../theme/" . $site_settings['template'] . "/upload.html");
    $data = str_ireplace("{form}", $_GET['form'], $data);
    $data = str_ireplace("{frame}", $_GET['frame'], $data);
    die($data);
}

define_user();
$forum_links = get_allowed_forums();

$forum_id_const = $forum_links[0]['forum_id'];
if (isset($_GET['file']) && !isset($_GET['form'])) {
    $result = _mysql_prepared_query(array(
        "query" => "SELECT post_id FROM attachments WHERE id = :file",
        "params" => array(
            ":file" => $_GET['file']
        )
    ));
    $pid = _mysql_result($result, 0);
    $forum_id_const = post_get_forum($pid);
}

$current_user['permissions'][$forum_id_const] = permissions_to_string(user_get_permissions($current_user['uid'], $forum_id_const));
$current_user['permissions']['global'] = permissions_to_string(user_get_permissions($current_user['uid']));

function rnd_string($len)
{
    $chr = array('q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'a', 's', 'd', 'f', 'g', 'h', 'j',
        'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'Q', 'W', 'E', 'R',
        'T', 'Y', 'U', 'I', 'O', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'A', 'S', 'D', 'F', 'G', 'H', 'J',
        'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
    $str = "";
    for ($i = 0; $i < $len; $i++) {
        $str .= $chr[rand(0, count($chr) - 1)];
    }
    return $str;
}

function resize_image_imagick($src, $dst, $size, $quality, $exif)
{

    $im = new Imagick();

    $im->readImageFile (fopen($src, 'rb'));

    if (isset($exif['Orientation']) && $exif['Orientation'] != null) {
        switch ($exif['Orientation']) {
            case 3:
                $im->rotateImage('#000000', 180);
                break;

            case 6:
                $im->rotateImage('#000000', 90);
                break;

            case 8:
                $im->rotateImage('#000000', -90);
                break;
        }
    }

    $height = $im->getImageHeight();
    $width = $im->getImageWidth();

    $max_orig = max($width, $height);
    $thumb_max = $size;
    $percent = $thumb_max / $max_orig;


    $thumb_width = $width * $percent;
    $thumb_height = $height * $percent;

    $original_aspect = $width / $height;
    $thumb_aspect = $thumb_width / $thumb_height;

    if ($original_aspect >= $thumb_aspect) {
        // If image is wider than thumbnail (in aspect ratio sense)
        $new_height = $thumb_height;
        $new_width = $width / ($height / $thumb_height);
    } else {
        // If the thumbnail is wider than the image
        $new_width = $thumb_width;
        $new_height = $height / ($width / $thumb_width);
    }

    $im->resizeImage($new_width, $new_height, Imagick::FILTER_LANCZOS, 1);
    $im->setCompressionQuality($quality);
    $im->writeImageFile(fopen ($dst, "wb"));
    $im->destroy();
}

function is_image($ext)
{
    $ext = strtolower($ext);
    $images = array("jpg", "png", "gif", "svg", "bmp");
    if (!(array_search($ext, $images) === false)) {
        return 1;
    }
    return 0;
}

function generate_name($ext)
{
    if (is_image($ext)) {
        return rnd_string(16) . "." . strtolower($ext);
    }
    return rnd_string(20);

}

$target = "../uploads/";

$count = true;
if ($_GET['a'] == "download_nocount") {
    $_GET['a'] = "download";
    $count = false;
}


function resize_image_gd($src, $dst, $size, $quality, $exif)
{
    global $width, $height;

    $image = @imagecreatefromjpeg($src);
    if (!$image) {
        $image = @imagecreatefromstring(file_get_contents($src));
    }
    $file_name = $dst;

    if (isset($exif['Orientation']) && $exif['Orientation'] != null) {
        switch ($exif['Orientation']) {
            case 3:
                $image = imagerotate ($image, 180, 0);
                break;

            case 6:
                $image = imagerotate ($image, -90, 0);
                break;

            case 8:
                $image = imagerotate ($image, 90, 0);
                break;
        }
    }

    $width = imagesx($image);
    $height = imagesy($image);

    $max_orig = max($width, $height);
    $thumb_max = $size;
    $percent = $thumb_max / $max_orig;


    $thumb_width = $width * $percent;
    $thumb_height = $height * $percent;

    $original_aspect = $width / $height;
    $thumb_aspect = $thumb_width / $thumb_height;

    if ($original_aspect >= $thumb_aspect) {
        // If image is wider than thumbnail (in aspect ratio sense)
        $new_height = $thumb_height;
        $new_width = $width / ($height / $thumb_height);
    } else {
        // If the thumbnail is wider than the image
        $new_width = $thumb_width;
        $new_height = $height / ($width / $thumb_width);
    }

    $thumb = imagecreatetruecolor($thumb_width, $thumb_height);

    // Resize and crop
    imagecopyresampled($thumb,
        $image,
        0 - ($new_width - $thumb_width) / 2, // Center the image horizontally
        0 - ($new_height - $thumb_height) / 2, // Center the image vertically
        0, 0,
        $new_width, $new_height,
        $width, $height);
    imagejpeg($thumb, $file_name, $quality);
}

function resize_image($src, $dst, $size, $quality, $exif)
{
    global $site_settings;
    if ($site_settings['resize_method'] == 'gd') {
        resize_image_gd($src, $dst, $size, $quality, $exif);
    } else if ($site_settings['resize_method'] == 'im') {
        try {
            if (extension_loaded('imagick')) {
                resize_image_imagick($src, $dst, $size, $quality, $exif);
            } else {
                resize_image_gd($src, $dst, $size, $quality, $exif);
            }
        } catch (Exception $e) {
            //dbg("FAILED", $e);
        }
    }
}

function compute_focal_length($focal_length)
{
    $parts = explode("/", $focal_length);
    if (count($parts) == 2) {
        return $parts[0] / $parts[1];
    }
    return $focal_length;
}


function compute_exposure($exposure)
{
    $parts = explode("/", $exposure);
    if (count($parts) == 2) {
        if ($parts[0] == 1) {
            return "1/" . $parts[1];
        }
        return "1/" . ($parts[1] / $parts[0]);
    }
    return $exposure;
}

switch ($_GET['a']) {
    case 'upload' :
        if ($site_settings['allow_upload'] == "0") {
            die("ERROR: this board does not allow uploading attachments.");
        }

        if ($site_settings['max_attachsize'] < $_FILES['uploaded']['size']) {
            die("ERROR: File too large. Maximum allowed filesize " . $site_settings['max_attachsize'] . " is bytes.");
        }

        $parts = explode(".", $_FILES['uploaded']['name']);

        if (!has_permission($current_user['permissions'][$forum_id_const], 'f_can_attach') || !has_permission($current_user['permissions']['global'], 'u_attach')) {
            die("You do not have permission to attach files.");
        }
        if (!form_is_valid($_GET['form'], 'postmessage')) {
            die("Invalid or missing form");
        }

        $random_name = generate_name(end($parts));
        while (file_exists($target . $random_name)) {
            $random_name = generate_name(end($parts));
        }

        $target = $target . $random_name;
        if (move_uploaded_file($_FILES['uploaded']['tmp_name'], $target)) {
            ini_set('memory_limit', '256M');
            $post_info = post_get_info($_GET['p']);
            $is_image = is_image(end($parts));
            $exif = "";
            if ($is_image == 1) {
                $arr = @exif_read_data($target);
                resize_image($target, "../images/large/" . $random_name, $site_settings['max_image_size'], 95, $arr);
                resize_image($target, "../images/small/" . $random_name, $site_settings['max_thumb_size'], 95, $arr);
                $new = array(
                    'FileDateTime' => strtotime($arr['DateTimeOriginal']),
                    'Model' => $arr['Model'],
                    'ISOSpeedRatings' => $arr['ISOSpeedRatings'],
                    'ApertureFNumber' => $arr['COMPUTED']['ApertureFNumber'],
                    'ExposureTime' => compute_exposure($arr['ExposureTime']),
                    'FocalLengthIn35mmFilm' => $arr['FocalLengthIn35mmFilm'],
                    'FocalLength' => compute_focal_length($arr['FocalLength'])
                );
                $exif = json_encode($new);
            }
            _mysql_prepared_query(array(
                "query" => "INSERT INTO attachments VALUES (NULL, :file_name, :actual_name, :extension, '' , :uid, :time, :pid, :size, 0, :is_image, :form, '0', :fid, :tid, :exif, :width, :height)",
                "params" => array(
                    ":file_name" => basename($_FILES['uploaded']['name']),
                    ":actual_name" => $random_name,
                    ":extension" => strtolower(end($parts)),
                    ":uid" => $current_user['uid'],
                    ":time" => time(),
                    ":pid" => $_GET['p'],
                    ":size" => $_FILES['uploaded']['size'],
                    ":is_image" => $is_image,
                    ":form" => $_GET['form'],
                    ":fid" => '',
                    ":tid" => '',
                    ":exif" => $exif,
                    ":width" => $width,
                    ":height" => $height
                )
            ));
            $id = _mysql_insert_id();
            if ($is_image == 1) {
                $replacements = array(
                    '{file_name}' => basename($_FILES['uploaded']['name']),
                    '{file_id}' => $id,
                    '{form_id}' => $_GET['form'],
                    '{actual_name}' => $random_name
                );
                echo renderResponse('preview_upload_image.html', $replacements);
                die();
            } else {
                $replacements = array(
                    '{file_name}' => basename($_FILES['uploaded']['name']),
                    '{file_id}' => $id,
                    '{form_id}' => $_GET['form']
                );
                echo renderResponse('preview_upload.html', $replacements);
            }
        } else {
            echo "Sorry, there was a problem uploading your file.";
        }
        break;
    case 'preview':
        if ($site_settings['allow_download'] == "0") {
            die("ERROR: this board does not allow downloading attachments.");
        }
        if (isset($_GET['p']) && isset($_GET['form']) && isset($_GET['file'])) {
            if (has_permission($current_user['permissions'][$forum_id_const], 'f_edit_own')
                && post_get_owner($_GET['p']) == $current_user['uid']
                || has_permission($current_user['permissions']['global'], 'm_edit_posts')
                || has_permission($current_user['permissions'][$forum_id_const], 'm_edit_posts')) {
                $result = _mysql_prepared_query(array(
                    "query" => "SELECT * FROM attachments WHERE id = :file",
                    "params" => array(
                        ":file" => $_GET['file']
                    )
                ));

                $replacements = array(
                    '{file_name}' => _mysql_result($result, 0, 'file_name'),
                    '{file_id}' => _mysql_result($result, 0, 'id'),
                    '{form_id}' => $_GET['form']
                );
                echo renderResponse('preview_upload.html', $replacements);
            } else {
                die("You are not authorized to alter attachments for this post");
            }
        } else {
            die("Post wasn't specified.");
        }
        break;
    case 'delete' :
        if (!has_permission($current_user['permissions'][$forum_id_const], 'f_can_attach')) {
            die("You do not have permission to attach files.");
        }
        $result = _mysql_prepared_query(array(
            "query" => "SELECT * FROM attachments WHERE id = :file",
            "params" => array(
                ":file" => $_GET['file']
            )
        ));
        if (@unlink($target . _mysql_result($result, 0, 'actual_name'))) {
            _mysql_prepared_query(array(
                "query" => "DELETE FROM attachments WHERE id = :file",
                "params" => array(
                    ":file" => $_GET['file']
                )
            ));
            if (_mysql_result($result, 0, 'is_image') == "1") {
                @unlink($target . "../images/small/" . _mysql_result($result, 0, 'actual_name'));
                @unlink($target . "../images/large/" . _mysql_result($result, 0, 'actual_name'));
            }
            die("File have been successfully deleted.");
        } else {
            _mysql_prepared_query(array(
                "query" => "DELETE FROM attachments WHERE id = :file",
                "params" => array(
                    ":file" => $_GET['file']
                )
            ));
            die("Failed to delete file. Please contact the board administrator.");
        }

        break;
    case 'download' :
        if ($site_settings['allow_download'] == "0") {
            die("ERROR: this board does not allow downloading attachments.");
        }
        $result = _mysql_prepared_query(array(
            "query" => "SELECT * FROM attachments WHERE id = :file",
            "params" => array(
                ":file" => $_GET['file']
            )
        ));
        if ($_GET['form'] == _mysql_result($result, 0, 'form') || has_permission($current_user['permissions'][post_get_forum(_mysql_result($result, 0, 'post_id'))], 'f_can_download') && has_permission($current_user['permissions']['global'], 'u_download_files') || has_permission($current_user['permissions']['global'], 'a_manage_attachments')) {
            header("Pragma: public");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Cache-Control: public");
            header("Content-description: File Transfer");
            header("Content-type: application");
            header("Content-Disposition: attachment; filename=" . _mysql_result($result, 0, 'file_name'));
            header("Content-Transfer-Encoding: binary");
            header("Content-Length: " . _mysql_result($result, 0, 'size'));
            if ($count) {
                _mysql_prepared_query(array(
                    "query" => "UPDATE attachments SET downloads=downloads+1 WHERE id = :file",
                    "params" => array(
                        ":file" => $_GET['file']
                    )
                ));
            }
            file_read_chunked($target . _mysql_result($result, 0, 'actual_name'));
            exit;
        } else {
            die("You do not have permission to view this file");
        }
        break;
}

function renderResponse($template_name, $replacements)
{
    global $template_directory;
    $content = file_get_contents($template_directory . '/' . $template_name);
    $content = strtr($content, $replacements);
    return $content;
}