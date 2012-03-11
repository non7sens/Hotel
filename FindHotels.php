<?php

//ini_set( "display_errors", 0);
$connection = connect_to_database('127.0.0.1', 'root', '');

//Read all search results
//Hardcoded page size!
for ($i = 0; $i < 27; $i++) {
    extract_data('http://meklesanas-rezultats.zl.lv/?p=' . $i . '&QProdukts=%22Viesn%C4%ABcas%22');
}
function extract_data($page)
{
    $html = new DOMDocument('1.0', 'UTF-8');
    //Turn off validation for html
    $html->validateOnParse = false;
    libxml_use_internal_errors(true);
    
    //Load html data , skip error handling
    $html->loadHTML('<?xml encoding="UTF-8">' . file_get_contents($page));
    libxml_clear_errors();
    $html->preserveWhiteSpace = true;
    
    
    //Get list element
    $list = $html->getElementById("List");
    
    //return if result is empty
    if ($list == NULL) {
        return;
    }
    
    //Dig deeper, to get all list <li> elements 
    $list = $list->childNodes;
    
    //Process each li element
    for ($i = 0, $il = $list->length; $i < $il; $i++) {
        $result = array(
            'title' => '',
            'adress' => '',
            'telephone' => '',
            'map' => '',
            'mail' => '',
            'web' => ''
        );
        //Check if this is really a <li> element
        if ($list->item($i)->localName == 'li') {
            //Now get its child nodes 
            $dataList = $list->item($i)->childNodes;
            
            //Process each child
            for ($a = 0, $al = $dataList->length; $a < $al; $a++) {
                //Read Header data -  it's the title
                if ($dataList->item($a)->localName == 'h1') {
                    //if this element has more then one childe ( text node ) remove them!
                    $result['title'] = $dataList->item($a)->childNodes->item(1);
                    if (isset($result['title']->childNodes) && $result['title']->childNodes->length > 1) {
                        $result['title']->removeChild($result['title']->childNodes->item(0));
                    }
                    $result['title'] = mysql_real_escape_string($result['title']->nodeValue);
                }
                //Get div that contains adress, telephone , www ...
                else if ($dataList->item($a)->localName == 'div' && $dataList->item($a)->attributes->getNamedItem("class")->nodeValue == 'Con') {
                    // <h2> element contains address
                    if ($dataList->item($a)->childNodes->item(0)->localName == 'h2') {
                        echo $result['adress'] = mysql_real_escape_string($dataList->item($a)->childNodes->item(0)->nodeValue);
                    }
                    // Search for <p> , it contains more stuff
                    if ($dataList->item($a)->childNodes->item(1)->localName == 'p') {
                        //Get <p> child nodes
                        $child = $dataList->item($a)->childNodes->item(1)->childNodes;
                        for ($b = 0, $bl = $child->length; $b < $bl; $b++) {
                            if ($child->item($b)->localName == 'span') {
                                echo $result['telephone'] = $child->item($b)->nodeValue;
                            } else if ($child->item($b)->localName == 'a' && $child->item($b)->attributes->getNamedItem("class")->nodeValue == 'Ico Map') {
                                echo $result['map'] = mysql_real_escape_string($child->item($b)->attributes->getNamedItem("href")->nodeValue);
                                
                            } else if ($child->item($b)->localName == 'a' && $child->item($b)->attributes->getNamedItem("class")->nodeValue == 'Ico Mail') {
                                echo $result['mail'] = mysql_real_escape_string(substr($child->item($b)->attributes->getNamedItem("href")->nodeValue, 7));
                                
                            } else if ($child->item($b)->localName == 'a' && $child->item($b)->attributes->getNamedItem("class")->nodeValue == 'Ico WWW') {
                                echo $result['web'] = mysql_real_escape_string($child->item($b)->attributes->getNamedItem("href")->nodeValue);
                                
                            }
                        }
                    }
                }
            }
        }
        //If element was not empty , insert to db
        if (empty($result['title'])) {
            continue;
        }
        $query_result = mysql_query('INSERT INTO Hotels ( Title , Adress , Email , Web , MapLink , Telephone ) VALUES ("' . $result['title'] . '","' . $result['adress'] . '","' . $result['mail'] . '","' . $result['web'] . '","' . $result['map'] . '","' . $result['telephone'] . '");');
        if (!$query_result) {
            die('Invalid query: ' . mysql_error());
        }
    }
}

function connect_to_database($ip, $user, $pw)
{
    mb_internal_encoding("UTF-8");
    $connection = mysql_connect($ip, $user, $pw);
    if (!$connection) {
        die('Could not connect: ' . mysql_error());
    } else {
        mysql_set_charset('utf8', $connection);
        $db_selected = mysql_select_db('mydb', $connection);
        if (!$db_selected) {
            die('Could not select db : ' . mysql_error());
        } else {
            return $connection;
        }
    }
}
;
?>