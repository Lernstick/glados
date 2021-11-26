<?php

/**
 * Generate a random UUID.
 * @return string the uuid
 */
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
 * Generate a UUID based on the MD5 hash of a namespace identifier (which is a UUID) and a name (which is a string).
 * @return string the uuid
 */
function generate_uuid3($name) {
    $ns = "6ba7b810-9dad-11d1-80b4-00c04fd430c8"; # NameSpace_DNS, see https://tools.ietf.org/html/rfc4122#appendix-C

    // Get hexadecimal components of namespace
    $nhex = str_replace(array('-','{','}'), '', $ns);

    // Binary Value
    $nstr = '';

    // Convert Namespace UUID to bits
    for($i = 0; $i < strlen($nhex); $i+=2) {
        $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
    }

    // Calculate hash value
    $hash = md5($nstr . $name);

    return sprintf('%08s-%04s-%04x-%04x-%12s',
        substr($hash, 0, 8), // 32 bits for "time_low"
        substr($hash, 8, 4), // 16 bits for "time_mid"
        (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000, // 16 bits for "time_hi_and_version", four most significant bits holds version number 3
        (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000, // 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low", two most significant bits holds zero and one for variant DCE1.1
        substr($hash, 20, 12) // 48 bits for "node"
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
 * @see JS equivalent [[substitute()]] in web/js/site.js
 */
function substitute($string, $params) {
    $search = preg_filter('/^.*$/', '{$0}', array_keys($params));
    return str_replace($search, array_values($params), $string);;
}

/* Introduced in PHP8 */
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return (strpos($haystack, $needle) !== false);
    }
}

?>