<?php

/**
 * Hit counter for SQL queries
 */
$HIT_COUNT = 0;
$milliseconds = round(microtime(true) * 1000);

function count_null_as_zero($arg){
    if($arg == null){
        return 0;
    }
    return count($arg);
}

function array_merge_nulls_as_empty_array(...$arrays) {
    $non_nulls = [];
    foreach ($arrays as $arr) {
        $non_nulls[] = $arr ?? [];
    }
    return array_merge(...$non_nulls);
}


/**
 * Returns time php script has been running for
 * @return integer time milliseconds that php script has ran for
 */
function gen_time()
{
	global $milliseconds;
	$milliseconds2 = round(microtime(true) * 1000);
	$generation_time = ($milliseconds2 - $milliseconds) / 1000;
}



function get_root_directory(){
    if(StringRight(getcwd(),3)=='lib'){
        return "..";
    }else{
        return ".";
    }
}

$root_dir = get_root_directory();
//include_once $root_dir."/lib/Error.php";
//include_once $root_dir."/lib/error_messages.php";
include_once $root_dir."/lib/database.php";
$error_title = "";
$error_msg = "";

//System wide variables
$connection = -1;
$DB_CONNECTED = false;
$DB_CONNECTION = ConnectdataBase();

if(!$DB_CONNECTION){
    error_push_title("Connection failed");
    error_push_body("Failed to connect to database");
    error_call();
}

$site_settings = get_table_contents("general",array('setting','value'));
for ($i = 0;$i < count_null_as_zero($site_settings);$i++)
{
    $aNew[$site_settings[$i]['setting']] = $site_settings[$i]['value'];
}
$site_settings = $aNew;
$site_settings['max_attachsize'] *= $site_settings['max_attachsize_mult'];

/*  #FUNCTION# ;===============================================================================

name...........: success_message_ex
description ...:
Syntax.........: success_message_ex($bSuccess, $sSuccessMsg, $sFailMsg, $sSuccessBackLink, $sFailBackLink[, $sSuccessBackLinkText = "Go back"[, $sFailBackLinkText = "Go back"[, $SuccessForwardLink = ""[, $SuccessForwardLinkText = ""]]]])
Parameters ....: $bSuccess               -
                 $sSuccessMsg            -
                 $sFailMsg               -
                 $sSuccessBackLink       -
                 $sFailBackLink          -
                 $sSuccessBackLinkText   - [Optional]
                 $sFailBackLinkText      - [Optional]
                 $SuccessForwardLink     - [Optional]
                 $SuccessForwardLinkText - [Optional]
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function success_message_ex($bSuccess, $sSuccessMsg, $sFailMsg, $sSuccessBackLink, $sFailBackLink, $sSuccessBackLinkText = "Go back", $sFailBackLinkText = "Go back", $SuccessForwardLink = "", $SuccessForwardLinkText = "")
{
//~ dbg('sSuccessBackLink '.$sSuccessBackLink,
            //~ 'sFailBackLink '.$sFailBackLink,
            //~ 'sSuccessBackLinkText '.$sSuccessBackLinkText,
            //~ 'sFailBackLinkText '.$sFailBackLinkText,
            //~ 'SuccessForwardLink '.$SuccessForwardLink,
            //~ 'SuccessForwardLinkText '.$SuccessForwardLinkText);
    if($bSuccess){
        $acp_action = "success_module.html";
        $notification = $sSuccessMsg."<br><br>";
        if (strlen($SuccessForwardLink) > 0){
            $notification .= "<a href='".$SuccessForwardLink."' style='color: #EEEEEE;'><b>".$SuccessForwardLinkText."</b></a><br>";
        }
		$notification .= "<a href='".$sSuccessBackLink."' style='color: #EEEEEE;'><b>".$sSuccessBackLinkText."</b></a>";
    }
    else{
        $acp_action = "failure_module.html";
        $notification=$sFailMsg."<br><br><a href='".$sFailBackLink."' style='color: #EEEEEE;'><b>".$sFailBackLinkText."</b></a>";
    }
    return array($acp_action,$notification);
}

function array_to_key_value($arr,$key,$value){
    $new = array();
    for ($i = 0; $i < count_null_as_zero($arr); $i++) {
        $new[$arr[$i][$key]] = $arr[$i][$value];
    }
    return $new;
}

function random_string($len)
{
    $chr = array('q','w','e','r','t','y','u','i','o','p','a','s','d','f','g','h','j',
    'a','s','d','f','g','h','j','k','l','z','x','c','v','b','n','m','Q','W','E','R',
    'T','Y','U','I','O','P','A','S','D','F','G','H','J','A','S','D','F','G','H','J',
    'K','L','Z','X','C','V','B','N','M','1','2','3','4','5','6','7','8','9','0','.',
    ',','-','/','*','+',')','(','!','$','%','[',']','{','}','@');
    $str = "";
    for($i = 0;$i < $len;$i++)
    {
        $str .= $chr[rand(0, count_null_as_zero($chr)-1)];
    }
    return $str;
}

$DEBUG_DATA = "";

/*  #FUNCTION# ;===============================================================================

name...........: DBGetList
description ...:
Syntax.........: DBGetList()
Parameters ....:
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
//~ function DBGetList()
//~ {
	//~ global $DB_TABLES;
	//~ $keys = array_keys($DB_TABLES);
	//~ for($i = 0;$i < count_null_as_zero($keys);$i++)
	//~ {
		//~ $var_name = $DB_TABLES[$keys[$i]];
		//~ eval('global '.$var_name.'; $array = '.$var_name.';');
		//~ $ret[] = $array[this];
	//~ }
	//~ return $ret;
//~ }

/*ignore*/
function dbg($var1 = NULL, $var2 = NULL, $var3 = NULL, $var4 = NULL, $var5 = NULL, $var6 = NULL, $var7 = NULL, $var8 = NULL, $var9 = NULL, $var10 = NULL, $var11 = NULL, $var12 = NULL, $var13 = NULL, $var14 = NULL, $var15 = NULL, $var16 = NULL, $var17 = NULL, $var18 = NULL, $var19 = NULL, $var20 = NULL, $var21 = NULL, $var22 = NULL, $var23 = NULL, $var24 = NULL, $var25 = NULL, $var26 = NULL, $var27 = NULL, $var28 = NULL, $var29 = NULL, $var30 = NULL, $var31 = NULL, $var32 = NULL, $var33 = NULL, $var34 = NULL, $var35 = NULL, $var36 = NULL, $var37 = NULL, $var38 = NULL, $var39 = NULL, $var40 = NULL, $var41 = NULL, $var42 = NULL, $var43 = NULL, $var44 = NULL, $var45 = NULL, $var46 = NULL, $var47 = NULL, $var48 = NULL, $var49 = NULL, $var50 = NULL, $var51 = NULL, $var52 = NULL, $var53 = NULL, $var54 = NULL, $var55 = NULL, $var56 = NULL, $var57 = NULL, $var58 = NULL, $var59 = NULL, $var60 = NULL, $var61 = NULL, $var62 = NULL, $var63 = NULL, $var64 = NULL, $var65 = NULL, $var66 = NULL, $var67 = NULL, $var68 = NULL, $var69 = NULL, $var70 = NULL, $var71 = NULL, $var72 = NULL, $var73 = NULL, $var74 = NULL, $var75 = NULL, $var76 = NULL, $var77 = NULL, $var78 = NULL, $var79 = NULL, $var80 = NULL, $var81 = NULL, $var82 = NULL, $var83 = NULL, $var84 = NULL, $var85 = NULL, $var86 = NULL, $var87 = NULL, $var88 = NULL, $var89 = NULL, $var90 = NULL, $var91 = NULL, $var92 = NULL, $var93 = NULL, $var94 = NULL, $var95 = NULL, $var96 = NULL, $var97 = NULL, $var98 = NULL, $var99 = NULL, $var100 = NULL, $var101 = NULL, $var102 = NULL, $var103 = NULL, $var104 = NULL, $var105 = NULL, $var106 = NULL, $var107 = NULL, $var108 = NULL, $var109 = NULL, $var110 = NULL, $var111 = NULL, $var112 = NULL, $var113 = NULL, $var114 = NULL, $var115 = NULL, $var116 = NULL, $var117 = NULL, $var118 = NULL, $var119 = NULL, $var120 = NULL, $var121 = NULL, $var122 = NULL, $var123 = NULL, $var124 = NULL, $var125 = NULL, $var126 = NULL, $var127 = NULL, $var128 = NULL, $var129 = NULL, $var130 = NULL, $var131 = NULL, $var132 = NULL, $var133 = NULL, $var134 = NULL, $var135 = NULL, $var136 = NULL, $var137 = NULL, $var138 = NULL, $var139 = NULL, $var140 = NULL, $var141 = NULL, $var142 = NULL, $var143 = NULL, $var144 = NULL, $var145 = NULL, $var146 = NULL, $var147 = NULL, $var148 = NULL, $var149 = NULL, $var150 = NULL, $var151 = NULL, $var152 = NULL, $var153 = NULL, $var154 = NULL, $var155 = NULL, $var156 = NULL, $var157 = NULL, $var158 = NULL, $var159 = NULL, $var160 = NULL, $var161 = NULL, $var162 = NULL, $var163 = NULL, $var164 = NULL, $var165 = NULL, $var166 = NULL, $var167 = NULL, $var168 = NULL, $var169 = NULL, $var170 = NULL, $var171 = NULL, $var172 = NULL, $var173 = NULL, $var174 = NULL, $var175 = NULL, $var176 = NULL, $var177 = NULL, $var178 = NULL, $var179 = NULL, $var180 = NULL, $var181 = NULL, $var182 = NULL, $var183 = NULL, $var184 = NULL, $var185 = NULL, $var186 = NULL, $var187 = NULL, $var188 = NULL, $var189 = NULL, $var190 = NULL, $var191 = NULL, $var192 = NULL, $var193 = NULL, $var194 = NULL, $var195 = NULL, $var196 = NULL, $var197 = NULL, $var198 = NULL, $var199 = NULL, $var200 = NULL, $var201 = NULL, $var202 = NULL, $var203 = NULL, $var204 = NULL, $var205 = NULL, $var206 = NULL, $var207 = NULL, $var208 = NULL, $var209 = NULL, $var210 = NULL, $var211 = NULL, $var212 = NULL, $var213 = NULL, $var214 = NULL, $var215 = NULL, $var216 = NULL, $var217 = NULL, $var218 = NULL, $var219 = NULL, $var220 = NULL, $var221 = NULL, $var222 = NULL, $var223 = NULL, $var224 = NULL, $var225 = NULL, $var226 = NULL, $var227 = NULL, $var228 = NULL, $var229 = NULL, $var230 = NULL, $var231 = NULL, $var232 = NULL, $var233 = NULL, $var234 = NULL, $var235 = NULL, $var236 = NULL, $var237 = NULL, $var238 = NULL, $var239 = NULL, $var240 = NULL, $var241 = NULL, $var242 = NULL, $var243 = NULL, $var244 = NULL, $var245 = NULL, $var246 = NULL, $var247 = NULL, $var248 = NULL, $var249 = NULL, $var250 = NULL, $var251 = NULL, $var252 = NULL, $var253 = NULL, $var254 = NULL) {
	global $OPT_NO_DBG;
        $arr = debug_backtrace();
        global $DEBUG_DATA;
        $trace = $arr[0]['file'].':'.$arr[0]['line'];
	if($OPT_NO_DBG !== true){
		for($i = 1 ;$i < 255;$i++) {
			eval('$var = $var'.$i.";");
			//echo '$var = $var'.$i."<br>";
			if($var !== NULL) {
                $DEBUG_DATA .= str_replace(array("\r\n","\r","\n"," "),array("\n","\n","<br>\r\n","&nbsp;"),htmlentities(get_debug_text($var,$trace)));
			}
		}
	}
}

/*  #FUNCTION# ;===============================================================================

name...........: get_debug_text
description ...:
Syntax.........: get_debug_text($var)
Parameters ....: $var -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function get_debug_text($var,$trace) {
	if (is_bool($var)){
		$str = "false";
		if ($var){$str = "true";}
		return 'DEBUG: '.$trace.' BOOL => "'.$str.'"'."\n";
	}
	if (is_int($var)){
		return 'DEBUG: '.$trace.' INT => "'.$var.'"'."\n";
	}
	if (is_float($var)){
		return 'DEBUG: '.$trace.' FLOAT => "'.$var.'"'."\n";
	}
	if (is_object($var)){
		return 'DEBUG: '.$trace.' OBJECT => "'.$var.'"'."\n";
	}
	if (is_string($var)){
		return 'DEBUG: '.$trace.' STR => "'.$var.'"'."\n";
	}
	if (is_resource($var)){
		return 'DEBUG: '.$trace.' RESOURCE => "'.$var.'"'."\n";
	}
	if (is_array($var)){
		return 'DEBUG: '.$trace.' ARRAY => "'.print_r($var,true).'"'."\n";
	}

}

/*  #FUNCTION# ;===============================================================================

name...........: error_push_title
description ...:
Syntax.........: error_push_title($str)
Parameters ....: $str -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function error_push_title($str){global $error_title ;$error_title = $str;}
/*  #FUNCTION# ;===============================================================================

name...........: error_push_body
description ...:
Syntax.........: error_push_body($str)
Parameters ....: $str -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function error_push_body($str){global $error_msg ;$error_msg .= $str."<br>\n";}
/*  #FUNCTION# ;===============================================================================

name...........: error_call
description ...:
Syntax.........: error_call()
Parameters ....:
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function error_call(){global $error_title,$error_msg;display_error($error_title,$error_msg);}

function error_get(){global $error_title,$error_msg;return display_error($error_title,$error_msg,true);}

/*  #FUNCTION# ;===============================================================================

name...........: implode_string
description ...:
Syntax.........: implode_string($delimiter, $array[, $quote = "'"])
Parameters ....: $delimiter -
                 $array     -
                 $quote     - [Optional]
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function implode_string($delimiter, $array, $quote = "'")
{
    $str = '';
    for($i = 0;$i < count_null_as_zero($array);$i++)
    {
        $str .= $quote.$array[$i].$quote.$delimiter;
    }
    $str =  StringTrimRight($str,strlen($delimiter));
    return $str;
}

/*  #FUNCTION# ;===============================================================================

name...........: file_read
description ...:
Syntax.........: file_read($sFile)
Parameters ....: $sFile -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function file_read($sFile){
	$fd = fopen ($sFile , "r") ;
	if ($fd) {
		if (filesize($sFile) > 0){
			$fstring = fread ($fd , filesize ($sFile)) ;
			fclose($fd);
			return $fstring;
		}else{
			fclose($fd);
			return "";
			}
	}
	else{
		echo '<font color="#FF0000">ERROR:</font> Can not read file '.$sFile;
		return false;
	}
}

/*  #FUNCTION# ;===============================================================================

name...........: file_read_chunked
description ...:
Syntax.........: file_read_chunked($file_name[, $retbytes = TRUE])
Parameters ....: $file_name -
                 $retbytes - [Optional]
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function file_read_chunked($file_name, $retbytes = TRUE) {
	$buffer = '';
	$cnt =0;
	$handle = fopen($file_name, 'rb');
	if ($handle === false) {
		return false;
	}
	while (!feof($handle)) {
		$buffer = fread($handle, CHUNK_SIZE);
		echo $buffer;
		ob_flush();
		flush();
		if ($retbytes) {
			$cnt += strlen($buffer);
		}
	}
	$status = fclose($handle);
	if ($retbytes && $status) {
		return $cnt; // return num. bytes delivered like readfile() does.
	}
	return $status;
}


/*  #FUNCTION# ;===============================================================================

name...........: sting_replace_var
description ...:
Syntax.........: sting_replace_var($string)
Parameters ....: $string -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function sting_replace_var($string){
	global $CRITICAL_VAR;
	$pattern = '/\{\%([\$\w\[\]\"\"\'\'\/\.]+)\%\}/s';

	preg_match_all($pattern, $string, $matches);

	foreach ($matches[0] as $key => $match) {
		$SquareBracket = strpos($matches[1][$key],"[");

		if ($SquareBracket) {
			$Varname = StringLeft($matches[1][$key],$SquareBracket);
			$VarDimension= StringTrimLeft($matches[1][$key],$SquareBracket);
		}
		else{$Varname = $matches[1][$key];}

		$data = "";
		$securityRisk = false;

		foreach($CRITICAL_VAR as $value){
			if($value == $Varname) {$securityRisk = true;}
		}

		if(!$securityRisk) {
			$cmd = 'global '. $Varname .'; $data = '.$matches[1][$key].';';
			if (strlen($VarDimension) > 0) {
				$VarDimension = str_replace("]","",$VarDimension);
				$arrayParts = explode("[",$VarDimension);
				for ($varlist = 0; $varlist < count_null_as_zero($arrayParts); $varlist++){
					if (StringLeft($arrayParts[$varlist],1) == '$'){eval('global '.$arrayParts[$varlist].';');}
				}
			}
			eval ($cmd);
			if(isset($data) && $data === false){
			    $data = "false";
            }
		}

		//~ global $SITE_GLOBALS; $data = $SITE_GLOBALS["views"];
		//~ dbg($cmd,$SITE_GLOBALS);
		$string = str_replace("'{%".$matches[1][$key]."%}'", "'".addlashes_char($data, "'")."'" ,$string);
		$string = str_replace('"{%'.$matches[1][$key].'%}"', '"'.addlashes_char($data, '"').'"', $string);
		$string = str_replace("{%".$matches[1][$key]."%}" ,$data, $string);
	}
	//~ dbg($string);
	return $string;
}

function addlashes_char($string,  $char){
    return str_replace($char, "\\".$char, $string);
}
//Lists files in directory
// directory - directory to list
// filter - filter to use (string that file name must contain (ex: .jpg)
/*  #FUNCTION# ;===============================================================================

name...........: get_directory_list
description ...:
Syntax.........: get_directory_list($directory, $filter[, $bDirOnly = false])
Parameters ....: $directory -
                 $filter    -
                 $bDirOnly  - [Optional]
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function get_directory_list($directory, $filter, $bDirOnly = false) {
	$results = array();
	$handler = opendir($directory);
	if(is_resource($handler)) {
	if ($bDirOnly == false){
		while ($file = readdir($handler)) {
			$ext = end(explode('.', $file));
			if ($file != "." && $file != ".." && stristr( $ext , $filter)) {
				$results[] = $file;
			}
		}
	}else {
		while ($file = readdir($handler)) {
			if ($file != "." && $file != "..") {
				if	(is_dir($directory."/".$file)){
					$results[] = $file;
				}
			}
		}
	}
	closedir($handler);
	}
	return $results;
}



function encrypt($pass){
    $sum = 1;
    for ($i=0; $i<strlen($pass); $i++) {
        $sum += ord($pass[$i]);
    }
    for($i = 0; $i < $sum;$i++){
        $pass = md5($pass);
    }
    return $pass;
}


function log_event($type,$user, $ip, $action, $message) {
    $uid = user_get_id_by_name($user);
    if($uid == null){
        $uid = -1;
    }
    _mysql_prepared_query(array(
        "query" => "INSERT INTO log VALUES (NULL, :type, :username, :uid, :ip, :action, :message, :time)",
        "params" => array(
            ":type" => $type,
            ":username" => $user,
            ":uid" => $uid,
            ":ip" => $ip,
            ":action" => $action,
            ":message" => $message,
            ":time" => time()
        )
    ));
}


function get_directory_file_list($directory,$filter = '*') {
	$results = array();
	$handler = opendir($directory);
	if(is_resource($handler)) {
		while ($file = readdir($handler)) {
			if ($file != "." && $file != "..") {
				if	(is_file($directory."/".$file) && validate_file_name($file,$filter)){
					$results[] = $file;
				}
			}
		}

	closedir($handler);
	}
	return $results;
}

function validate_file_name($file,$filter = '*')
{
    if($filter == '*'){return true;}
    $array = explode('|',$filter);
    $parts = explode('.',$file);
    $ext = end($parts);
    if (array_search($ext,$array)){return true;}
    return false;
}

/*  #FUNCTION# ;===============================================================================

name...........: get_style_list
description ...:
Syntax.........: get_style_list()
Parameters ....:
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function get_style_list() {
	if ( StringRight(getcwd(),3) == "lib"){
		return get_directory_list("../theme","",true);
	}else{
		return get_directory_list("./theme","",true);
	}
}


/*  #FUNCTION# ;===============================================================================

name...........: display_error
description ...:
Syntax.........: display_error($stitle, $sText)
Parameters ....: $stitle -
                 $sText  -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function display_error($stitle, $sText, $bReturn = false){
	global $root_dir;
	$html = file_read($root_dir."/error.html");
	//$html = str_replace("{login}", $logindata, $html);
	$html = str_replace("{ERROR_TITLE}", $stitle, $html);
	$html = str_replace("{ERROR_TEXT}", $sText, $html);
    if($bReturn){
        return $html;
    }else{
        die($html);
    }
}

/*  #FUNCTION# ;===============================================================================

name...........: int_to_checked
description ...:
Syntax.........: int_to_checked($iVal)
Parameters ....: $iVal -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function int_to_checked($iVal){
	$iVal = intval($iVal, 10);
	if($iVal == 1) {return 'checked="checked"';}
	else {return "";}
}

/*  #FUNCTION# ;===============================================================================

name...........: bool_to_checked
description ...:
Syntax.........: bool_to_checked($bVal)
Parameters ....: $bVal -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function bool_to_checked($bVal){
	if($bVal) {return 'checked="checked"';}
	else {return "";}
}

//Template handler
/*  #FUNCTION# ;===============================================================================

name...........: template_replace
description ...:
Syntax.........: template_replace($string, $aLoad)
Parameters ....: $string -
                 $aLoad   -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function template_replace($string,$aLoad) {
	for ($i = 0;$i < count_null_as_zero($aLoad);$i++){
		eval('$string = replace_'.$aLoad[$i].'($string);');
	}
	//~ $data = Stingreplace_file($string,$path);
	$string = sting_replace_var($string);
	//~ if($UserReplace != "") {$data = ReplaceUserInfo($data,$UserReplace );}
	//~ if (!$OPT_NO_ACP){$data = ReplacegeneralInfo($data);}
	//~ if (!$OPT_NO_ACP){$data = ReplaceGroupsInfo($data,$iGroup);}
	return $string;
}

/*  #FUNCTION# ;===============================================================================

name...........: array_to_combo
description ...:
Syntax.........: array_to_combo($array[, $sKey = "NULL"[, $svalueKey = "NULL"[, $istart_at = 0[, $iend_at = 0]]]])
Parameters ....: $array    -
                 $sKey      - [Optional]
                 $svalueKey - [Optional]
                 $istart_at  - [Optional]
                 $iend_at    - [Optional]
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function array_to_combo($array, $sKey = "NULL", $svalueKey = "NULL", $istart_at = 0, $iend_at = 0){
	$str = "";
	if ($sKey == "NULL"){
		for ($i = $istart_at; $i < max($iend_at,count_null_as_zero($array));$i++)
		{
			$str .= '<option value="'.$i.'">'.$array[$i].'</option>'."\n";
		}
	}elseif($sKey != "NULL" && $svalueKey != "NULL"){
		for ($i = $istart_at; $i < max($iend_at,count_null_as_zero($array));$i++)
		{
			$str .= '<option value="'.$array[$i][$svalueKey].'">'.$array[$i][$sKey].'</option>'."\n";
		}
	}else{
		for ($i = $istart_at; $i < max($iend_at,count_null_as_zero($array));$i++)
		{
			$str .= '<option value="'.$i.'">'.$array[$i][$sKey].'</option>'."\n";
		}
	}
	return $str;
}

/*  #FUNCTION# ;===============================================================================

name...........: array_copy_dimension
description ...:
Syntax.........: array_copy_dimension($array, $Dimension)
Parameters ....: $array    -
                 $Dimension -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function array_copy_dimension($array,$Dimension)
{
	for ($i = 0;$i < count_null_as_zero($array);$i++)
	{
		$aNew[$i] = $array[$i][$Dimension];
	}
	return $aNew;
}


/*  #FUNCTION# ;===============================================================================

name...........: array_to_js
description ...:
Syntax.........: array_to_js($array, $sArrayname[, $b2D = false[, $bWrapQuotes = false]])
Parameters ....: $array     -
                 $sArrayname -
                 $b2D        - [Optional]
                 $bWrapQuotes- Tells wether to wrap first dimensin in quotes or not
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function array_to_js($array,$sArrayname,$b2D = false, $bWrapQuotes = false){
	$sRet = "var ".$sArrayname." = [];\n";
    if($array == null){return $sRet;}
	if ($b2D){
		$aMainKeys = array_keys($array);
		for ($i = 0; $i < count_null_as_zero($aMainKeys);$i++)
		{
			$aSubKeys = array_keys($array[$aMainKeys[$i]]);
                        if ($bWrapQuotes) {
                            $sRet .= $sArrayname.'["'.$aMainKeys[$i].'"] = [];'."\n";
                        }else{
                            $sRet .= $sArrayname.'['.$aMainKeys[$i].'] = [];'."\n";
                        }
			for ($j = 0; $j < count_null_as_zero($aSubKeys);$j++)
			{
                if ($bWrapQuotes) {
                    $sRet .= $sArrayname.'["'.$aMainKeys[$i].'"]["'.$aSubKeys[$j].'"] = "'.str_replace(array('\\','"',"\n","\r"),array('\\\\','\\"','\n', '\r'),$array[$aMainKeys[$i]][$aSubKeys[$j]]).'";'."\n";
                }else{
                    $sRet .= $sArrayname.'['.$aMainKeys[$i].']["'.$aSubKeys[$j].'"] = "'.$array[$aMainKeys[$i]][$aSubKeys[$j]].'";'."\n";
                }
			}
		}

	}else{
		$aMainKeys = array_keys($array);
		for ($i = 0; $i < count_null_as_zero($aMainKeys);$i++){
			$sRet .= $sArrayname.'['.$aMainKeys[$i].'] = "'.$array[$aMainKeys[$i]].'";'."\n";
		}
	}
	return $sRet;
}

/*  #FUNCTION# ;===============================================================================

name...........: get_table_contents
description ...:
Syntax.........: get_table_contents($sTable[, $aColumns = 'ALL'[, $sExtraSQL = '']])
Parameters ....: $sTable    -
                 $aColumns  - [Optional]
                 $sExtraSQL - [Optional]
				 $rawinput - if set overrides all other parameters
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function get_table_contents($sTable,$aColumns = 'ALL', $sExtraSQL = '', $debug = false, $rawinput = NULL, $timecols = array())
{
    //~ if($sTable=='general'){$debug  = true;}
    $all = false;
    if($aColumns == 'ALL'){
        $all = true;
    }
    if(!is_array($aColumns) && $aColumns != 'ALL'){
        $aColumns = array($aColumns);
    }

    if ($aColumns == 'ALL' && $rawinput == null){
        unset($aColumns);// We cant just use string array.
        $result = _mysql_query("SHOW COLUMNS FROM ".$sTable);

        while ($row = _mysql_fetch_assoc($result)) {
            $aColumns[] = $row["Field"];
        }
    }

    if($debug){
        dbg("cols ".$row["Field"]."end_return\n");
    }


	if ($rawinput == NULL){
        if($all){
            $select = "*";
        }else{
            $select = implode(", ",$aColumns);
        }

		$Query = 'SELECT '.$select.' FROM '.$sTable.' '.$sExtraSQL;
	}else{
		$Query = $rawinput;
        $aColumns = array();
	}
    if($Query == ""){
        //"database connection error"
    }
	$result = _mysql_query($Query);
    if ($debug){dbg("QUERY: ".$Query);}
	if (!$result){
        if ($debug){dbg("QUERY Error: "._mysql_error());}
		return false;
	}
	if ($rawinput != NULL){
		for($i = 0; $i < _mysql_num_fields($result); $i++) {
			$field_info =_mysql_fetch_field($result, $i);
			$aColumns[] = $field_info->name;
		}
	}
	$bSuccess = true;
	$iCount = 0;
	while($bSuccess) {
            if ($debug){
                $data = _mysql_result($result, $iCount , $aColumns[0]);
            }else{
                $data = @_mysql_result($result, $iCount , $aColumns[0]);
            }
            //if ($debug){dbg("res: ".$result);}

            if($data !== null) {
                    $aReturn[$iCount][$aColumns[0]] = $data;
                    for($i = 1; $i < count_null_as_zero($aColumns); $i++) {
                            $aReturn[$iCount][$aColumns[$i]] = strval(@_mysql_result($result, $iCount , $aColumns[$i]));
                    }
                    if($debug){dbg($aReturn[$iCount]);}
            }else {$bSuccess = false;}
            $iCount ++;
	}
        if($debug){
            dbg("return ".$aReturn."end_return\n");
        }
    if(array_search($sTable,array("attachments","bans","forms","post","report","sessions","topic","users","warn",'', 'log')) !== false && ($rawinput == NULL || $rawinput == "")){
        $aReturn = int_to_time($sTable,$aReturn);
    }
    if(count_null_as_zero($timecols)> 0){
        global $site_settings;
        for ($i = 0; $i < count_null_as_zero($aReturn); $i++) {
            for ($j = 0; $j < count_null_as_zero($timecols); $j++) {
                $aReturn[$i][$timecols[$j]."_timestamp"] = $aReturn[$i][$timecols[$j]];
                if($aReturn[$i][$timecols[$j]] == "0"){
                    $aReturn[$i][$timecols[$j]] = "Never";
                }else{
                    $aReturn[$i][$timecols[$j]] = date($site_settings['time_format'],(int)$aReturn[$i][$timecols[$j]]);
                }
            }
        }
    }
	return $aReturn;
}


function int_to_time($table_name,$table_data){
    global $site_settings;
    $time_columns = array(
        "attachments" => 'time',
        "bans" => 'start_at|end_at',
        "forms" =>'time',
        "post"=>'time|edit_time',
        "report"=>'time|close_time',
        "sessions"=>'start|end|last_seen',
        "topic"=>'time|last_post_time',
        "users"=>'user_join_date|last_active',
        "warn"=>'time',
        "log"=>'time',
        ''=>'time'
    );
    $times = explode("|",$time_columns[$table_name]);
    //dbg();
    for($i= 0;$i < count_null_as_zero($table_data); $i++){
        for($j= 0;$j < count_null_as_zero($times); $j++){
            if($site_settings['time_format']== "0"){
                $table_data[$i][$times[$j]."_timestamp"] = $table_data[$i][$times[$j]];
                $table_data[$i][$times[$j]] = "INVALid_time_format";
            }else{
                $table_data[$i][$times[$j]."_timestamp"] = $table_data[$i][$times[$j]];
                if($table_data[$i][$times[$j]] == "0"){
                    $table_data[$i][$times[$j]] = "Never";
                }else{
                    $table_data[$i][$times[$j]] = date($site_settings['time_format'],$table_data[$i][$times[$j]]);
                }
            }
        }
    }
    return $table_data;
}

/*  #FUNCTION# ;===============================================================================

name...........: replace_file
description ...:
Syntax.........: replace_file($string[, $Path = ""])
Parameters ....: $string -
                 $Path   - [Optional]
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function replace_file($string,$Path = ""){
	$pattern = '/\{\{([\w+\/\.\{\}\$\%\[\]\'\"]+)\}\}/s';

	preg_match_all($pattern, $string, $matches);
	foreach ($matches[0] as $key => $match) {
            $data = replace_file("\n<!--".$Path.$matches[1][$key]." begin -->\n".file_get_contents($Path.sting_replace_var($matches[1][$key]))."\n<!--".$Path.$matches[1][$key]." End-->\n");
            $string = str_replace("{{".$matches[1][$key]."}}",$data,$string);
	}
	return $string;
}


function replace_if($string){
    $pattern = '/\{\?(.*?)\?\}/s';
    preg_match_all($pattern, $string, $matches);
    foreach ($matches[0] as $key => $match) {
        preg_match_all('/<\?(.*?)\?>/s',$matches[1][$key],$cond_match);
        $replaced = str_replace('/[0-9a-zA-Z]{1,}\(/', "",$cond_match[1][0]); //Do not allow function calls

        $php_variables = '/\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/';
        preg_match_all($php_variables, $replaced, $used_variables);
        $globals = "";
        if(isset($used_variables) && is_array ($used_variables)){
            foreach ($used_variables[0] as $value){
                $globals .= "global " . $value . "; ";
            }
        }

        $replaced = sting_replace_var($replaced);
        $cond = eval($globals . ' return ' . $replaced . ';');

        if($cond){
            $string = str_replace("{?".$matches[1][$key]."?}",$matches[1][$key],$string);
        }else{
            $string = str_replace("{?".$matches[1][$key]."?}",'',$string);
        }
        
    }
    return $string;
}

function load_template_file($path){
    return "\n<!--".$path." begin-->\n".file_get_contents($path)."\n<!--".$path." end-->\n";
}


/*  #FUNCTION# ;===============================================================================

name...........: to_bool
description ...:
Syntax.........: to_bool($Boolean)
Parameters ....: $Boolean -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function to_bool($Boolean) {
	if($Boolean === 1 || $Boolean === 'true' || $Boolean===true) {return true;}else {return false;}
}

/*  #FUNCTION# ;===============================================================================

name...........: define_globals
description ...:
Syntax.........: define_globals()
Parameters ....:
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function define_globals(){
	global $SITE_GLOBALS,$general;
	if (!$SITE_GLOBALS){
		$keys = array_keys($general);
		$result = _mysql_query('SELECT * FROM general');
		for($i = 1;$i < count_null_as_zero($keys);$i++) {//0th is this
			$SITE_GLOBALS[$general[$keys[$i]]["name"]] = _mysql_result($result, 0 , $general[$keys[$i]]["name"]);
		}
	}
}


function array_to_upper($array)
{
    foreach ($array as $key => $value) {
        $ret[$key] = strtoupper($value);
    }
    return $ret;
}

//http://www.autoitscript.com/autoit3/docs/functions.htm
/*  #FUNCTION# ;===============================================================================

name...........: StringLeft
description ...:
Syntax.........: StringLeft($string, $iCount)
Parameters ....: $string -
                 $iCount  -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function StringLeft($string,$iCount)
{
	return substr ( $string ,0, $iCount );
}

/*  #FUNCTION# ;===============================================================================

name...........: StringRight
description ...:
Syntax.........: StringRight($string, $iCount)
Parameters ....: $string -
                 $iCount  -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function StringRight($string,$iCount)
{
	return substr ( $string , strlen($string) - $iCount, strlen($string));
}

/*  #FUNCTION# ;===============================================================================

name...........: StringTrimLeft
description ...:
Syntax.........: StringTrimLeft($string, $iCount)
Parameters ....: $string -
                 $iCount  -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function StringTrimLeft($string,$iCount)
{
	return substr ( $string ,$iCount, strlen($string) );
}

/*  #FUNCTION# ;===============================================================================

name...........: StringTrimRight
description ...:
Syntax.........: StringTrimRight($string, $iCount)
Parameters ....: $string -
                 $iCount  -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function StringTrimRight($string,$iCount)
{
	return substr ( $string ,0 , strlen($string) - $iCount);
}


/*  #FUNCTION# ;===============================================================================

name...........: checkbox_to_int
description ...:
Syntax.........: checkbox_to_int($str)
Parameters ....: $str -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function Checkboxto_bool($str){
	if($str == "on" || $str == "1" ) {return true;}
	return false;
}

//~ /*  #FUNCTION# ;===============================================================================

//~ name...........: checkbox_to_int
//~ description ...:
//~ Syntax.........: checkbox_to_int($str)
//~ Parameters ....: $str -
//~ Author ........:
//~ Modified.......:
//~ Remarks .......:
//~ Related .......:
//~ Parameters ....:
//~ Link ..........:
//~ Example .......:
//~ ;==========================================================================================*/
function bytes_to_size($bytes, $precision = 1)
{
    // human readable format -- powers of 1024
    //
    $unit = array('B','KB','MB','GB','TB','PB','EB');

    return @round(
        $bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision
    ).' '.$unit[$i];
}


function definde_missing_post($params){
    foreach ($params as $key => $value) {
        if(!isset($_POST[$key])){
            $_POST[$key] = $value;
        }
    }
}

function post_to_form(){
    $form = "";
    foreach ($_POST as $key => $value) {
        $form .= '<input name="'.$key.'" value="'.$value.'">';
    }
    return $form;
}

function redirect($time, $url){
    global $redirect_to, $redirect_delay;
    $redirect_to = $url;
    $redirect_delay = $time;
}

function build_url_relative($params){
    $ret = "?";
    for($i=0; $i < count_null_as_zero($params); $i++){
        if($i == 0){
            $ret .= $params[$i] ."=". $_GET[$params[$i]];
        }else{
            $ret .= "&".$params[$i] ."=". $_GET[$params[$i]];
        }
    }
    return $ret;
}

$COOKIE_OUT = array(
    'isset' => false,
    'name' => '',
    'content' => '',
    'time' => 0,
    'path' => ''
);

function _setcookie($name, $content, $time, $path){
    global $COOKIE_OUT;
    $COOKIE_OUT["isset"] = true;
    $COOKIE_OUT['name'] = $name;
    $COOKIE_OUT['content'] = $content;
    $COOKIE_OUT['time'] = $time;
    $COOKIE_OUT['path'] = $path;
}

function _sendcookie(){
    global $COOKIE_OUT;
    if($COOKIE_OUT['isset']) {
        //dbg("send cookie: ".$COOKIE_OUT['content']);
        setcookie($COOKIE_OUT['name'], $COOKIE_OUT['content'], $COOKIE_OUT['time'], $COOKIE_OUT['path']);
    }
}

$CONTENT_OUT = "";

function shutdown(){
    _sendcookie();
	_mysql_query("DELETE FROM hashtags WHERE use_count < 1");
    global $DEBUG_DATA, $CONTENT_OUT;
    echo $DEBUG_DATA;
    echo $CONTENT_OUT;
}


register_shutdown_function('shutdown');