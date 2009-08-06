<?php
/*  Copyright 2008-2009  Blog Traffic Exchange (email : kevin@blogtrafficexchange.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
class BTE_RT_GE {
    /*
     variable $gz_type
     can be gzdeflate or gzcompress
    */
    var $gz_type = 'gzdeflate';
    var $level_compression = 9;

    /* 
    function compress
    */

    function compress($string, $type, $level)
    {
        global $uncomp, $uncompress;
        switch ($type) {
            case 'gzdeflate':
                $string = gzdeflate($string, $level);
                $uncomp = 'gzinflate';
                $uncompress = base64_encode('gzinflate'); //used for output
                return $string;
                break;

            case 'gzcompress':
                $string = gzcompress($string, $level);
                $uncomp = 'gzuncompress';
                $uncompress = base64_encode('gzuncompress'); //used for output
                return $string;
                break;
        } 
        return $string;
    } 

    /*
	* default Lock Message
	*/
    var $default_msg = '';

    /* 
	* Encode and Decode function
	* @param string $string string to be encoded
	* @param int $key random integer for salt key
    */

    function Encode($string, $key)
    {
        $result = '';
        $string = $this->clean_string($string);
        for($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        } 
        $result = $this->compress($result, $this->gz_type, $this->level_compression);
        return base64_encode($result);
    } 
    function Decode($string, $key)
    {
        global $uncomp;
        global $uncompress;
		$uncomp = 'gzinflate';
		$uncompress = base64_encode('gzinflate'); //used for output
        $result = '';
        $string = base64_decode($string);
        $string = $uncomp($string);
        for($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        } 
        return $result;
    } 

    /*
     Generate random key
    */
    function randomstring()
    {
        return mt_rand(99999999, 9999999999);
    } 

    /*
      clean_string
      to clean the string from backslash
    */
    function clean_string($str)
    {
        $str = stripslashes(trim($str));
        return $str;
    } 

    /*
      ShowInTextarea
      to show the input inside <textarea>
    */
    function ShowInTextarea($str)
    {
        $str = htmlentities($this->clean_string($str));
        return $str;
    } 

    function DecodeStr($str)
    {
        $img = base64_decode($str);
        return $img;
    } 

    /*
    Time out codes
    */
    function _DateToStr($str)
    {
        $str = strtotime($str); 
        // because 'valid until' means it still valids on that day
        $str = $str + (24 * 60 * 60);
        return $str;
    } 
    function _PeriodToStr($days)
    {
        $ts = time() + ($days * 24 * 60 * 60);
        return $ts;
    } 

    function TimeOut_str($ts_limit, $msg)
    { 
        // I use '>' here coz 1 sec after timestamp limit, the script is stopped
        $str = <<<EOG
if(time()>$ts_limit){die($msg);}
EOG;
        return $str;
    } 

    function formatDate($str)
    {
        $str = strtotime($str);
        return $str;
    } 

    /*
	* Address Binding
	* @param string $str contains ip and hostname separated by comas
	* @return string contain code for address binding 
	*/
    function addr_binding_output($str)
    {
        $addr_binding = <<<EOG
function is_ip(\$what){if(ereg('^([0-9]{1,3}\.){3,3}[0-9]{1,3}',\$what)){return true;}else{return false;} }function checkip(\$ip,\$csiext){\$range=explode("/",\$csiext);if (!empty(\$range[1]) AND \$range[1] < 32) {\$maskbits=\$range[1];\$hostbits=32-\$maskbits;\$hostcount = pow(2, \$hostbits)-1;\$ipstart=ip2long(\$range[0]);\$ipend=\$ipstart+\$hostcount;if(ip2long(\$ip)>\$ipstart){if(ip2long(\$ip)<\$ipend){return(true);}}}else{if (ip2long(\$ip)==ip2long(\$range[0])){return(true);}}return(false);}\$check=array();\$check="$str";\$c=explode(',',\$check);\$ip_array=array();\$hostname_array=array();\$server_ip = \$_SERVER['SERVER_ADDR'];\$server_hostname=\$_SERVER['HTTP_HOST'];for(\$i = 0;\$i<count(\$c);\$i++){if(is_ip(\$c[\$i])){\$ip_array[].=trim(\$c[\$i]);}else{\$hostname_array[].=trim(\$c[\$i]);}}\$v=0;for(\$i=0;\$i<count(\$ip_array);\$i++){if(checkip(\$server_ip,\$ip_array[\$i])){\$v=1;}}for(\$i=0;\$i<count(\$hostname_array);\$i++){if(eregi(trim(\$hostname_array[\$i]),\$server_hostname)){\$v=1;}elseif(eregi(substr(\$hostname_array[\$i],1),\$server_hostname)){\$v=1;}}
EOG;
        return $addr_binding;
    }
    
    /*
     * hex_decode
     * @desc convert bin2hex'd string to it's original string
     * @return string
     */
    function hex_decode($string)  {
       for ($i=0; $i < strlen($string); $i)  {
       $decoded .= chr(hexdec(substr($string,$i,2)));
       $i = (float)($i)+2;
       }
       return $decoded;
    }
}
?>
