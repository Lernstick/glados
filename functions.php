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

?>
