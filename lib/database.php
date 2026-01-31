<?php
$DB = NULL;
if (!defined('MYSQL_BOTH')) {
    define('MYSQL_BOTH', 1); // Same value as MYSQLI_BOTH
}

/*  #FUNCTION# ;===============================================================================

name...........: ConnectdataBase
description ...:
Syntax.........: ConnectdataBase()
Parameters ....:
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function ConnectdataBase()
{
	global $DB_CONNECTED;
	//Make sure that only 1 connection is created
	if($DB_CONNECTED != true) {
            global $DATABASE_SERVER;
            global $DATABASE_PORT;
            global $DATABASE_USER;
            global $DATABASE_NAME;
            global $DATABASE_PASSWORD;
            global $DB;
            global $MODE;
            //IF port set, use it
            if($MODE=='mysql'){
                if($DATABASE_PORT != "") {$DATABASE_SERVER = $DATABASE_SERVER.":".$DATABASE_PORT;}
                $DB = @mysql_connect($DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASSWORD);
                if (!$DB) {
                    return false;
                }
                $sel = @mysql_select_db($DATABASE_NAME);
                if (!$sel) {
                    return false;
                }
                $DB_CONNECTED = true;
            }elseif($MODE=='mysqli'){
                if($DATABASE_PORT != "") {$DATABASE_SERVER= $DATABASE_SERVER.":".$DATABASE_PORT;}
                $DB = new mysqli($DATABASE_SERVER, $DATABASE_USER, $DATABASE_PASSWORD, $DATABASE_NAME) or false;
                if (mysqli_connect_errno()) {
                    return false;
                }
            }
            return $DB;
	}else {
            return false;
        }
}

function _mysql_errno(){
    global $MODE,$DB;
    if($MODE=='mysql'){
        return mysql_errno();
    }elseif($MODE=='mysqli'){
        return mysqli_errno( $DB );
    }
}

/*  #FUNCTION# ;===============================================================================

name...........: DBGetColumnsList
description ...:
Syntax.........: DBGetColumnsList($name)
Parameters ....: $name -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function DBGetColumnsList($name)
{
    global $MODE,$DB;
    $columns = array();
    if($MODE=='mysql'){
        $result = _mysql_query("SHOW COLUMNS FROM ".$name."");
        while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
            $columns[] = $row[0] ;
        }
    }elseif($MODE=='mysqli'){
        $result = _mysql_query("SHOW COLUMNS FROM ".$name."");
        while ($row =  _mysql_fetch_array ($result, MYSQL_NUM)) {
            $columns[] = $row[0] ;
        }
    }
    return $columns;
}

/*  #FUNCTION# ;===============================================================================

name...........: _mysql_query
description ...:
Syntax.........: _mysql_query($str)
Parameters ....: $str -
Author ........:
Modified.......:
Remarks .......:
Related .......:
Parameters ....:
Link ..........:
Example .......:
;==========================================================================================*/
function _mysql_query($query) {
    global $MODE,$DB,$HIT_COUNT ;
    if($MODE=='mysql'){
        $res = mysql_query($query);
    }elseif($MODE=='mysqli'){
        $res = $DB->query($query);
    }
    $HIT_COUNT ++;
    return $res;
}

function _mysql_flush_multi_queries(){
    global $DB;
    while ($DB->next_result()) {;}
}

function _mysql_multi_query($query) {
    global $MODE,$DB,$HIT_COUNT ;
    if($MODE=='mysql'){
        throw new Exception('Not implemented');
    }elseif($MODE=='mysqli'){
        if($query == null){
            return false;
        }
        $res = $DB->multi_query($query);
    }
    $HIT_COUNT ++;
    return $res;
}

function _mysql_result($result,$row,$field= 0) {
    global $MODE,$DB;
    if($MODE=='mysql'){
        return mysql_result($result,$row,$field);
    }elseif($MODE=='mysqli'){
        if(gettype($field) == "integer" ){
            $row = mysqli_fetch_array($result, MYSQLI_NUM);
            return $row[$field];
        }else{
            mysqli_data_seek($result,$row);
            $row = mysqli_fetch_assoc($result);
            return $row[$field];
        }
    }
}

function _mysql_insert_id($link_identifier = NULL) {
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_insert_id($link_identifier);
    }elseif($MODE=='mysqli'){
        $res = mysqli_insert_id($DB);
    }
    return $res;
}

function _mysql_num_rows($result) {
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_num_rows($result);
    }elseif($MODE=='mysqli'){
        $res = mysqli_num_rows($result);
    }
    return $res;
}

function _mysql_fetch_array($result, $result_type=MYSQL_BOTH){
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_fetch_array($result,$result_type);
    }elseif($MODE=='mysqli'){
        $res = mysqli_fetch_array($result,$result_type);
        if($res == NULL){
            return false;
        }
    }
    return $res;
}

function _mysql_free_result($result) {
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_free_result($result);
    }elseif($MODE=='mysqli'){
        $res = mysqli_free_result($result);
    }
    return $res;
}

function _mysql_close($link_indentifier = NULL) {
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_close($link_indentifier);
    }elseif($MODE=='mysqli'){
        $res = mysqli_close($DB);
    }
    return $res;
}

function _mysql_fetch_assoc($result) {
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_fetch_assoc($result);
    }elseif($MODE=='mysqli'){
        $res = mysqli_fetch_assoc($result);
        if($res == NULL){
            return false;
        }
    }
    return $res;
}

function _mysql_num_fields($result) {
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_num_fields($result);
    }elseif($MODE=='mysqli'){
        $res = mysqli_num_fields($result);
        if($res == NULL){
            return false;
        }
    }
    return $res;
}

function _mysql_fetch_field($result) {
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_fetch_field($result);
    }elseif($MODE=='mysqli'){
        $res = mysqli_fetch_field($result);
        if($res == NULL){
            return false;
        }
    }
    return $res;
}

function _mysql_real_escape_string($str){
    if($str == null || $str == ""){
        return $str;
    }
    global $MODE,$DB;
    if($MODE=='mysql'){
        $res = mysql_real_escape_string($str,$DB);
    }elseif($MODE=='mysqli'){
        $res = mysqli_real_escape_string($DB,$str);
        if($res == NULL){
            return false;
        }
    }
    return $res;
}

function replaceValues($value, $level = 0){
    if(is_string($value)){
        return "'" . _mysql_real_escape_string($value) . "'";
    } elseif (is_null($value)){
        return  "NULL";
    } elseif (is_bool($value)){
        return  $value ? "1" : "0";
    } elseif (is_array($value)){
        $ret = $level == 0 ? "" : "(";
        for($i = 0; $i < sizeof($value); $i++){
            $listEnd = sizeof($value) == $i+1 ? "" : ", ";
            $ret .= replaceValues($value[$i], $level+1) . $listEnd;
        }
        $ret .= $level == 0 ? "" : ")";
        return $ret;
    }
    return $value;
}

/**
 * Runs a prepared query
 * Prepared query should be passed in the following form:
 * array(
 * "query" => "query string with :param1, :param2, ...",
 * "params" => array(
 *   ":param1" => ":param1 value",
 *   ":param2" => ":param2 value"
 * )
 * );
 */
function _mysql_prepared_query($query, $debug = false){
    global $MODE;
    if($MODE=='mysql' || $MODE=='mysqli'){
        // For backwards compatibility
        $paramsCopy = array_map("replaceValues", $query["params"]);
        $queryString = strtr($query["query"], $paramsCopy);
        if($debug){
            dbg($query, $paramsCopy, $queryString);
        }
        return _mysql_query($queryString);
    }
}