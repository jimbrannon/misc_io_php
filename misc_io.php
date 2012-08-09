<?php
function output_array($array){
	foreach($array as $key => $val){
		echo "    $key = ".$val."<br>";
	}
}
function getargs ($key,$def) {
	if(isset($_GET[$key])){
		if(empty($_GET[$key])) {
			$output = $def;
		} else {
			$output = $_GET[$key];
		}
	} else {
		$output = $def;
	}
	return $output;
}
function do_offset($level){
	$offset = "";             // offset for subarry
	for ($i=1; $i<$level;$i++){
		$offset = $offset . "<td></td>";
	}
	return $offset;
}
function show_array($array, $level, $sub){
	if (is_array($array) == 1){          // check if input is an array
		foreach($array as $key_val => $value) {
			$offset = "";
			if (is_array($value) == 1){   // array is multidimensional
				echo "<tr>";
				$offset = do_offset($level);
				echo $offset . "<td>" . $key_val . "</td>";
				show_array($value, $level+1, 1);
			} else {                        // (sub)array is not multidim
				if ($sub != 1){          // first entry for subarray
					echo "<tr nosub>";
					$offset = do_offset($level);
				}
				$sub = 0;
				echo $offset . "<td main ".$sub." width=\"120\">" . $key_val .
					"</td><td width=\"120\">" . $value . "</td>";
				echo "</tr>\n";
			}
		} //foreach $array
	} else { // argument $array is not an array
		return;
	}
}
function html_show_array($array){
	echo "<table cellspacing=\"0\" border=\"2\">\n";
	show_array($array, 1, 0);
	echo "</table>\n";
}
function strtobool($str) {
	switch (strtolower($str)) {
		case "false":
		case "no":
		case "off":
		case "0":
			return false;
			break;
		default:
			return true;
	}
}
/** Checks a variable to see if it should be considered a boolean true or false.
 *     Also takes into account some text-based representations of true of false,
 *     such as 'false','N','yes','on','off', etc.
 * @author Samuel Levy <sam+nospam@samuellevy.com>
 * @param mixed $in The variable to check
 * @param bool $strict If set to false, consider everything that is not false to
 *                     be true.
 * @return bool The boolean equivalent or null (if strict, and no exact equivalent)
 */
function boolval($in, $strict=false) {
    $out = null;
    $in = (is_string($in)?strtolower($in):$in);
    // if not strict, we only have to check if something is false
    if (in_array($in,array('false','no', 'n','0','off',false,0), true) || !$in) {
        $out = false;
    } else if ($strict) {
        // if strict, check the equivalent true values
        if (in_array($in,array('true','yes','y','1','on',true,1), true)) {
            $out = true;
        }
    } else {
        // not strict? let the regular php bool check figure it out (will
        //     largely default to true)
        $out = ($in?true:false);
    }
    return $out;
}
/**
* funpack
* format: array of key, length pairs
* data: string to unpack
*/
function funpack($format, $data){
	$pos=0;
    foreach ($format as $key => $len) {
    	if(substr($key,0,4)!='skip')$result[$key] = trim(substr($data, $pos, $len));
        $pos+= $len;
    }
    return $result;
}
/**
* phunpack
* format: same format string as unpack()
* data: string to unpack
*/
function phunpack($format, $data){
	$tmp = unpack($format,$data);
	$result=array();
    foreach ($tmp as $key => $val) {
    	if(substr($key,0,4)!='skip')$result[$key] = $val;
    }
    return $result;
}
/**
* makeformat
* format: array of key, length pairs
* data: string to unpack
*/
function makeformatstring($names){
	$formatstring = '';
    foreach ($names as  $name=>$type) {
    	if($type=='@'){
    		$formatstring .= $type.substr($name,2)."/";
    	} else {
    		$formatstring .= $type.$name."/";
    	}
    	
    }
    return $formatstring;
}
/*
 * a map of pg field types returned from the pg_field_type() function
 * http://www.php.net/manual/en/function.pg-field-type.php
 * and
 * http://www.postgresql.org/docs/9.1/static/datatype-datetime.html
 * 
 * to google visualization column types
 * https://developers.google.com/chart/interactive/docs/reference
 * 
 * it's a moving gun and a moving target - might need to keep this mapping updated
 */
function pgtype_to_gvtype ($field_type) {
	switch (strtolower(trim($field_type))) {
		case 'bool': // a boolean from a pg database
			$output = 'boolean';
			break;
		case 'int2': // a small integer from a pg database
		case 'int4': // an integer from a pg database
		case 'int8': // a long integer from a pg database
			$output = 'number';
			break;
		case 'numeric': // a "numeric" real number field from a pg database
		case 'float4': // a single precision real number field from a pg database
		case 'float8': // a double precision real number field from a pg database
			$output = 'number';
			break;
		case 'date': // a date field from a pg database
			$output = 'date';
			break;
		case 'time': // a time field from a pg database
		case 'timetz': // a time field with timezone from a pg database
			$output = 'timeofday';
			break;
		case 'timestamp': // a timestamp field from a pg database
		case 'timestamptz': // a timestamp field with timezone from a pg database
			$output = 'datetime';
			break;
		case 'text':  // a text field from a pg database
		case 'varchar': // a varchar field from a pg database
		case 'bpchar': // a bpchar field from a pg database
			$output = 'string';
			break;
		default: // an unanticipated field type, put a text box or single selects, but may not work
			$output = 'string';
	}
	return $output;
}
/*
 * converts a pg field type and value pair into the correct string for the GViz JSON
 * php field types:
 * http://www.php.net/manual/en/function.pg-field-type.php
 * and
 * http://www.postgresql.org/docs/9.1/static/datatype-datetime.html
 * 
 * google visualization column types:
 * https://developers.google.com/chart/interactive/docs/reference
 * 
 * some weird stuff here, such as date strings must be copnverted to "new Date()" javascript function calls
 *  
 * it's a moving gun and a moving target - might need to keep this mapping updated
 */
function pgtypeval_to_gvval ($field_type, $val) {
	switch (strtolower(trim($field_type))) {
		case 'bool': // a boolean from a pg database
			// convert to gviz column type of 'boolean'
			$output = $val;
			break;
		case 'int2': // a small integer from a pg database
		case 'int4': // an integer from a pg database
		case 'int8': // a long integer from a pg database
			// convert to gviz column type of 'number'
			$output = (integer) $val;
			break;
		case 'numeric': // a "numeric" real number field from a pg database
		case 'float4': // a single precision real number field from a pg database
		case 'float8': // a double precision real number field from a pg database
			// convert to gviz column type of 'number'
			$output = (float) $val;
			break;
		case 'date': // a date field from a pg database
			// convert to gviz column type of 'date'
			$time_stamp = strtotime($val);
			$time_array = getdate($time_stamp);
			$yy = $time_array['year'];
			$mm = $time_array['mon']-1; //why -1?  i have no idea...
			$dd = $time_array['mday'];
			$hr = $time_array['hours'];
			$min = $time_array['minutes'];
			$sec = $time_array['seconds'];
			$output = "new Date($yy,$mm,$dd,$hr,$min,$sec)";
			break;
		case 'time': // a time field from a pg database
		case 'timetz': // a time field with timezone from a pg database
			// convert to gviz column type of 'timeofday'
			$time_stamp = strtotime($val);
			$time_array = getdate($time_stamp);
			$yy = $time_array['year'];
			$mm = $time_array['mon']-1; //why -1?  i have no idea...
			$dd = $time_array['mday'];
			$hr = $time_array['hours'];
			$min = $time_array['minutes'];
			$sec = $time_array['seconds'];
			$output = "new Date($yy,$mm,$dd,$hr,$min,$sec)";
			break;
		case 'timestamp': // a timestamp field from a pg database
		case 'timestamptz': // a timestamp field with timezone from a pg database
			// convert to gviz column type of 'datetime'
			$time_stamp = strtotime($val);
			$time_array = getdate($time_stamp);
			$yy = $time_array['year'];
			$mm = $time_array['mon']-1; //why -1?  i have no idea...
			$dd = $time_array['mday'];
			$hr = $time_array['hours'];
			$min = $time_array['minutes'];
			$sec = $time_array['seconds'];
			$output = "new Date($yy,$mm,$dd,$hr,$min,$sec)";
			break;
		case 'text':  // a text field from a pg database
		case 'varchar': // a varchar field from a pg database
		case 'bpchar': // a bpchar field from a pg database
			// convert to gviz column type of 'string'
			$output = $val;
			break;
		default: // an unanticipated field type, put a text box or single selects, but may not work
			// convert to gviz column type of 'string'
			$output = $val;
	}
	return $output;
}
/*
 * converts a pg field type and value pair into a value that can be used as a PHP hash index value
 * php field types:
 * http://www.php.net/manual/en/function.pg-field-type.php and
 * http://www.postgresql.org/docs/9.1/static/datatype-datetime.html
 *
 * the key thing is that 
 */
function pgtypeval_to_hashindex ($field_type, $val) {
	switch (strtolower(trim($field_type))) {
		case 'bool': // a boolean from a pg database
			// convert to gviz column type of 'boolean'
			$output = $val;
			break;
		case 'int2': // a small integer from a pg database
		case 'int4': // an integer from a pg database
		case 'int8': // a long integer from a pg database
			// convert to gviz column type of 'number'
			$output = (integer) $val;
			break;
		case 'numeric': // a "numeric" real number field from a pg database
		case 'float4': // a single precision real number field from a pg database
		case 'float8': // a double precision real number field from a pg database
			// convert to gviz column type of 'number'
			$output = (float) $val;
			break;
		case 'date': // a date field from a pg database
			// convert to gviz column type of 'date'
			$time_stamp = strtotime($val);
			$time_array = getdate($time_stamp);
			$yy = $time_array['year'];
			$mm = $time_array['mon']; //why -1?  i have no idea...
			$dd = $time_array['mday'];
			$hr = $time_array['hours'];
			$min = $time_array['minutes'];
			$sec = $time_array['seconds'];
			$output = "new Date($yy,$mm,$dd,$hr,$min,$sec)";
			break;
		case 'time': // a time field from a pg database
		case 'timetz': // a time field with timezone from a pg database
			// convert to gviz column type of 'timeofday'
			$time_stamp = strtotime($val);
			$time_array = getdate($time_stamp);
			$yy = $time_array['year'];
			$mm = $time_array['mon']; //why -1?  i have no idea...
			$dd = $time_array['mday'];
			$hr = $time_array['hours'];
			$min = $time_array['minutes'];
			$sec = $time_array['seconds'];
			$output = "new Date($yy,$mm,$dd,$hr,$min,$sec)";
			break;
		case 'timestamp': // a timestamp field from a pg database
		case 'timestamptz': // a timestamp field with timezone from a pg database
			// convert to gviz column type of 'datetime'
			$time_stamp = strtotime($val);
			$time_array = getdate($time_stamp);
			$yy = $time_array['year'];
			$mm = $time_array['mon']; //why -1?  i have no idea...
			$dd = $time_array['mday'];
			$hr = $time_array['hours'];
			$min = $time_array['minutes'];
			$sec = $time_array['seconds'];
			$output = "new Date($yy,$mm,$dd,$hr,$min,$sec)";
			break;
		case 'text':  // a text field from a pg database
		case 'varchar': // a varchar field from a pg database
		case 'bpchar': // a bpchar field from a pg database
			// convert to gviz column type of 'string'
			$output = $val;
			break;
		default: // an unanticipated field type, put a text box or single selects, but may not work
			// convert to gviz column type of 'string'
			$output = $val;
	}
	return $output;
}
?>