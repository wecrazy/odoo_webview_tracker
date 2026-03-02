<?php 

/*----------------------------------------------------------------------
|:                                                                    :|
|:  Sexy comments in your code!                                       :|
|:  ===========================                                       :|
|:  Hi, my name is Ivan I care about programming, music and women.    :|
|:  Follow me on twitter: @ivanca                                     :|
|:                                                                    :|
----------------------------------------------------------------------*/

use function Cake\Core\toString;

function sexy_comment($message, $title = '', $width = 25){

	function mb_str_pad($input, $pad_length, $pad_string=' ', $pad_type=STR_PAD_RIGHT) {
		$diff = strlen($input) - mb_strlen($input);
		return str_pad($input, $pad_length+$diff, $pad_string, $pad_type);
	}

	function replace_accents($str) {
		$str = htmlentities($str, ENT_COMPAT, "UTF-8");
		$str = preg_replace(
	'/&([a-zA-Z])(uml|acute|grave|circ|tilde);/',
	'$1',$str);
		return html_entity_decode($str);
	}

	$message = iconv('UTF-8', 'ISO-8859-1', $message); 
	$n = "\r\n";
	$tab_ini = '|:  ';
	$tab_end = strrev($tab_ini);
	$result = '';
	$border = '/*';
	$title_underline = '=';
	$square_by = '-';
 
	while(mb_strlen($border) < $width){
		$border .= $square_by;
	}
 
	$result .= $n . $border . $n;
	$title_deco = mb_str_pad('', mb_strlen($title), $title_underline);
	$padding = mb_strlen($tab_ini);
	$maxlength = mb_strlen($border);
	$linelength = $maxlength - ($padding * 2);
	$words_arr = explode(" ", $message);
	$comment_arr = array();
	$temp = '';
	$newline = FALSE;
 
	for ($j=0; $j < count($words_arr); $j++) { 
		$word = $words_arr[$j];
		if(is_null($word)){
			array_push($comment_arr, $temp);
			$temp = '';
			$newline = FALSE;
			continue;
		}
		$word = str_replace("\t", '  ', $word);
		$clean_word = preg_replace('/\r?\n[\s\S]*/', '', $word);
		if(mb_strlen($temp) + mb_strlen($clean_word) >= $linelength || $newline !== FALSE){
			array_push($comment_arr, $temp);
			$temp = '';
		}
		$newline = strpos($word, "\n");
		if($newline !== FALSE){
			$split = preg_split('!\r?\n!', $word);
			$split = array_map(function($a){
				if($a === ''){
					return NULL;
				}
				return $a;
			}, $split);
			array_splice($words_arr, $j, 1, $split);
			$word = $clean_word;
		} else {
			$word .= ' ';
		}
		$temp .= $word;
	}
 
	if(!empty($temp)){
		array_push($comment_arr, $temp);
	}
 
	if($title){
		array_unshift($comment_arr, $title, $title_deco);
	}
	array_unshift($comment_arr, '');
	array_push($comment_arr, '');
	for ($i=0; $i < count($comment_arr); $i++) { 
		$line = $comment_arr[$i];
		$line = $tab_ini . $line;
		$line = mb_str_pad($line, $maxlength - $padding, ' ');
		$result .= $line . $tab_end . $n;
	}
	$result .= strrev($border) . $n . $n;
	$result = iconv('ISO-8859-1', 'UTF-8', $result);
	return $result;
}

if ($argc > 1) {
    // Concatenate all command line arguments into a single string
    $string = implode(' ', array_slice($argv, 1)); // Start from index 1 to ignore the script name
    
    echo sexy_comment($string);
} else {
    echo "Please provide a message as command line arguments.\n";
}