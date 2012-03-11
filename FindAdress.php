<?php
connect_to_database('127.0.0.1', 'root', '', 'mydb');

//Get all addresses from your database
$result = mysql_query('SELECT Adress ,idHotels FROM Hotels');
if (!$result) {
    die('Invalid query: ' . mysql_error());
} else {
    //Iterate over each address
    for ($i = 0, $il = mysql_num_rows($result); $i < $il; $i++) {
        $row = mysql_fetch_row($result);
        // Create url for Google Api
        // http://maps.googleapis.com/maps/api/geocode  -  Google geocode url
        // xml - Response format 
        // ?address= - !Do not remove! Adress parameter ( address string )
        // &sensor=false - !Do not remove! Parameter for other devices, leave false
        $url = file_get_contents('http://maps.googleapis.com/maps/api/geocode/xml?address=' . urlencode($row[0] . ', Latvia') . '&sensor=false');
        
        //XML response , <status> OK </status> == Found adress  
        preg_match('#\<status\>(.+?)\<\/status\>#s', $url, $matches);
        if ((string) $matches[1] == "OK") {
            //Read Location data
            preg_match('#\<location\>(.+?)\<\/location\>#s', $url, $matches);
            //Latitude
            preg_match('#\<lat\>(.+?)\<\/lat\>#s', $matches[0], $lat);
            //Longtitude
            preg_match('#\<lng\>(.+?)\<\/lng\>#s', $matches[0], $lng);
            //Check if it is out of certain region, if so google found wrong adress 
            if ($lat[1] > 59.008098 || $lat[1] < 55.627996) {
                continue;
            } elseif ($lng[1] > 28.520508 || $lng[1] < 20) {
                continue;
            } else {
                //Insert recived cord.
                $query_result = mysql_query("UPDATE Hotels Set Lat=$lat[1],Lng=$lng[1] WHERE idHotels=$row[1]");
            }
        }
    }
}

function connect_to_database($ip, $user, $pw, $table)
{
    mb_internal_encoding("UTF-8");
    $connection = mysql_connect($ip, $user, $pw);
    if (!$connection) {
        die('Could not connect: ' . mysql_error());
    } else {
        mysql_set_charset('utf8', $connection);
        $db_selected = mysql_select_db($table, $connection);
        if (!$db_selected) {
            die('Could not select db : ' . mysql_error());
        } else {
            return $connection;
        }
    }
}
;
?>