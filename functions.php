<?php

function generate_uuid() {
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
		mt_rand( 0, 0xffff ),
		mt_rand( 0, 0x0fff ) | 0x4000,
		mt_rand( 0, 0x3fff ) | 0x8000,
		mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
	);
}

/**
 * Equivalent to MySQLs COALESCE()
 * Returns the first non-null or nonmissing value from a list of numeric arguments. 
 * 
 * @return null|int
 */
function coalesce(){
	$args = func_get_args();
	foreach ($args as $arg) {
		if (is_numeric ($arg)){
			return $arg;
		}
	}
	return null;
}

/**
 * Equivalent to MySQLs NULLIF()
 * @param mixed expr1 expression to compare
 * @param mixed expr2 expression to compare
 * 
 * @return null|mixed
 */
function nullif($expr1, $expr2){
	return $expr1 == $expr2 ? null : $expr1;
}

/**
 * Dummy translate function to mark text to be extracted by the "yii message" command.
 * It does nothing but returning the message.
 * @see https://www.yiiframework.com/doc/api/2.0/yii-baseyii#t()-detail
 * 
 * @return string
 */
function yiit($category, $message, $params = [], $language = null) {
	return $message;
}

/**
 * This function act similar to Yii::t() except that it does not translate any
 * string. It will just replace the placeholders {key} in the string with the
 * values given in the params array.
 * @param string string the string containing placeholders
 * @param array params the array with key values pairs
 * 
 * @return string
 */
function substitute($string, $params) {
	$search = preg_filter('/^.*$/', '{$0}', array_keys($params));
	return str_replace($search, array_values($params), $string);;
}

?>