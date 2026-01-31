<?php
//////////////////////////////////////////////////////////////////////////////
//name: Dynamic bbcode parser
//
//Author: Rain Oksvort
//
//This code generates html code from bbcode by using bbcode templates to
//auto generate regular expression.
//
//Functions:
//bbcode_to_regex - Converts bbcode templates to regex patterns
//parse_bbcode - Replaces bbcode with html code
//bbcode_code_tag - Disables bbcode parsing inside code tag
//
//Usage:
//$tags[n]['bbcode'] = 'bbcode template';
//$tags[n]['HTML'] = 'HTML Code template';
//parse_bbcode(String,bbcode_to_regex($tags));
//Note: int N must >= 0
//
//warning: This code doesn't escape UTF-7 encoded tags. To avoid UTF-7 attacks
//set your page encoding to something else than UTF-7. UTF-8 is good to go.
//Example: <meta http-equiv="Content-type" content="text/html; charset=utf-8">
//////////////////////////////////////////////////////////////////////////////

//Returns array of tags regex patterns
/*
	ARRAY[N]['name'] = name of tag extracted from tag itself (Ex: for '[url\="{url}"]{text}[\/url]' it would be 'url')
	ARRAY[N]['bbcode'] = Pattern that matches given tag
	ARRAY[N]['bbcode_html'] = Peplace pattern

	N Number of tag ranges from 0 to number of tags
*/

$attachments_cache = array();

function bbcode_to_regex($tags, $bbcode_key = 'bbcode', $html_key = 'HTML'){
	//Assign data types for bb code(s)
	$data_types= array(
		'text' => array(
			'pattern' => '(.*?)',
			'size' => 1
		),
		'url' => array(
			'pattern' => '(((http|https|news|ftp)://(.*?))|(./(.*?))|(./(.*?)))',
			'size' => 8
		),
		'color' => array(
			'pattern' => '(\#([?i:a-z0-9]){6})',
			'size' => 2
		),
                'int' => array(
			'pattern' => '([0-9]+)',
			'size' => 1
		),
                'hash' => array(
			'pattern' => '(\#([?i:a-zA-Z0-9]){0,})',
			'size' => 2
		),
                'simple_text' => array(
			'pattern' => '(([?i:a-zA-Z0-9]){0,})',
			'size' => 2
		),
                'urlend' => array(
			'pattern' => '(\?([a-zA-Z0-9-\=\#&]*))',
			'size' => 1
		)
	);

	//Escape special characters
	$escape_search = array('[',']');
	$escape_replace = array('\[','\]');
	$data_type_pattern = '';

	$keys = array_keys($data_types);
	for ($i = 0; $i < count($keys); $i++){
		$data_type_pattern .= $keys[$i].'(\d?|\d+)|'; //create pattern to match variable
	}

	$data_type_pattern = '(\{('.substr ( $data_type_pattern ,0 , strlen($data_type_pattern) - 1).')\})';

	for ($j = 0; $j < count($tags); $j++){//Loop trough all bbcodes
		$current_bbcode = $tags[$j][$bbcode_key ];

		//Create array of variables used in bb code
		preg_match_all($data_type_pattern , $current_bbcode , $matches, PREG_SET_ORDER);

		$c = 1;

		//Generate array of match info
		foreach ($matches as $val) {
			$raw = preg_replace("(\d|\d+)","",$val[1]);
			$match[$c]['original'] = $val[0];
			$match[$c]['pattern'] = $data_types[$raw]['pattern'];
			$match[$c]['size'] = $data_types[$raw]['size'];
			$c++;
		}

		///define variables
		$bbcode = $current_bbcode;
		$bbcode_html =  $tags[$j][$html_key];
		$offset = 1; //Some regular exprssions are larger than 1 match
		//Assemble pattern and replacement
		$bbcode = str_replace($escape_search,$escape_replace,$bbcode);
        if(count($matches) > 0){
            for ($n = 1; $n <= count($match); $n++){
                $bbcode = str_replace($match[$n]['original'],$match[$n]['pattern'],$bbcode);
                $bbcode_html = str_replace($match[$n]['original'],'\\'.$offset,$bbcode_html);
                $offset += $match[$n]['size'];
            }
        }
		$bbcode = '#'.$bbcode.'#si';
                
		//Get bb code name
		preg_match('(\[(.*?)\])', $current_bbcode, $matches);
		$bbcode_name = explode("=",$matches[0]);
		$bbcode_name = str_replace(array('[',']'),'',$bbcode_name[0]);
		//Make array of bbcode regex replacements
		$bbcode_regex[$j]['name'] = $bbcode_name;
		$bbcode_regex[$j]['bbcode'] = $bbcode;
		$bbcode_regex[$j]['bbcode_html'] = $bbcode_html;

		unset($match); //Cleanup
	}
	//~ print_r($bbcodeRegex);

	//return array of regex patterns and replacements
	return $bbcode_regex;
}

function parse_bbcode($string,$replace,$no_parse = array(),$encode_html = true, $utf8 = false)
{
    //We don't want html code as it's security risk
    if($utf8){
        if ($encode_html) {$string = htmlentities($string,ENT_QUOTES, "UTF-8");}
    }else{
        if ($encode_html) {$string = htmlentities($string,ENT_QUOTES);}
    }

    //Line ends
    $string = str_replace(array("\r\n","\r","\n"),array("\n","\n","<br>\r\n"),$string);

    //code tag
    $code_tag_pattern = '#\[code=[a-z0-9-]*\](.*?)\[/code\]#si';
    $string = preg_replace_callback($code_tag_pattern,"bbcode_code_tag",$string);

    //code tag
    $attach_tag_pattern = '#\[attach=[0-9]*\]#si';
    $string = preg_replace_callback($attach_tag_pattern,"bbcode_attach_tag",$string);
    
    
    for ($i = 0;$i < count($replace); $i++){
            if (array_search($replace[$i]['name'],$no_parse) === false){
                    if($replace[$i]['name']=="youtube"){
                        $replace[$i]['bbcode'] = '#\[youtube\]https://www.youtube.com/watch\?v=(.*?)\[/youtube\]#si';
                    }
                    $string = preg_replace($replace[$i]['bbcode'],$replace[$i]['bbcode_html'],$string);
            }
    }
    
    return $string;
}



function  bbcode_code_tag($matches)
{
    $lng = StringTrimLeft($matches[0], 6); 
    $pos = strpos($lng, ']');
    $lng = StringLeft($lng, $pos);
    $ent = array(
            //' ' => '&nbsp;',
            '[' => '&#91;',
            ']' => '&#93;',
            chr(9) => '&nbsp;&nbsp;&nbsp;&nbsp;',
    );
    
    $code = '<pre class="syntaxhighlighter brush: '.$lng.';">'.strtr(str_replace("<br>", "", $matches[1]),$ent).'</pre>';
    return $code ;
}

function get_attachment_by_id($attach_id){
    global $attachments_cache;
    
    if(isset($attachments_cache[$attach_id])){
        return $attachments_cache[$attach_id];
    }
    
    $result = _mysql_prepared_query(array(
        "query" => "SELECT * FROM attachments WHERE id = :id",
        "params" => array(
            ":id" => $attach_id
        )
    ));
    
    $attachment = array(
        'post_id' => '',
        'file_name' => '',
        'id' => '',
        'size' => ''
    );
    
    if($result){
        $attachment = array(
            'post_id' => _mysql_result($result,0, 'post_id' ),
            'file_name' => _mysql_result($result,0, 'file_name' ),
            'id' => _mysql_result($result,0, 'id' ),
            'size' => _mysql_result($result,0, 'size' )
        );
    }
    
    $attachments_cache[$attachment['id']] = $attachment;
    return $attachment;  
}

function bbcode_attach_tag($matches)
{
    global $site_settings, $current_user;
    $attach_id = StringTrimLeft($matches[0], 8); 
    $pos = strpos($attach_id, ']');
    $attach_id = StringLeft($attach_id, $pos);
    $attachment = get_attachment_by_id($attach_id);
    
    if($site_settings['allow_download'] == "1" &&
            has_permission($current_user['permissions'][post_get_forum($attachment['post_id' ])],'f_can_download') && has_permission($current_user['permissions']['global'],'u_download_files') || has_permission($current_user['permissions']['global'], 'a_manage_attachments')    ){
        $attach_name = $attachment['file_name' ];
        $attach_id = $attachment['id'];
        $attach_size = $attachment['size']; 
        return '<span class="attachment"><a href="./lib/upload.php?a=download&file='.$attach_id.'">'.$attach_name.'</a> ('.bytes_to_size($attach_size).')</span>';
    }else{
        return "";
    }
}