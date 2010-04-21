<?php
/*
 * tweetspider
 * Created on Apr 20, 2010
 * Author : nb
 * Email  : bharadhwaj.n@gmail.com
 * Twitter: @bharadhwaj
 */
set_time_limit  (0); //overriding max execution time to prevent script from stopping after 60 seconds
# Include libraries

include("lib/LIB_parse.php");
include("lib/LIB_http.php");
include("config/variables.php");
include("config/URLList.php");
include("config/databaseConfig.php"); //configure this file first


$dbHandle = mysql_connect($host, $usr, $pwd)or die(mysql_error());
mysql_set_charset('utf8_unicode_ci',$dbHandle);
echo "Connected to MySQL<br />";
mysql_select_db($db) or die(mysql_error());
echo "Connected to $db<br/>";

function utf8_to_unicode( $str ) 
{
        
        $unicode = array();        
        $values = array();
        $lookingFor = 1;
        
        for ($i = 0; $i < strlen( $str ); $i++ ) 
        {

            $thisValue = ord( $str[ $i ] );
            
            if ( $thisValue < 128 ) $unicode[] = $thisValue;
            else {
            
                if ( count( $values ) == 0 ) $lookingFor = ( $thisValue < 224 ) ? 2 : 3;
                
                $values[] = $thisValue;
                
                if ( count( $values ) == $lookingFor ) 
                {
            
                    $number = ( $lookingFor == 3 ) ?
                        ( ( $values[0] % 16 ) * 4096 ) + ( ( $values[1] % 64 ) * 64 ) + ( $values[2] % 64 ):
                    	( ( $values[0] % 32 ) * 64 ) + ( $values[1] % 64 );
                        
                    $unicode[] = $number;
                    $values = array();
                    $lookingFor = 1;
            
                } // if
            
            } // if
            
        } // for

        return $unicode;
    
    } // utf8_to_unicode

 function unicode_to_entities_preserving_ascii( $unicode ) 
 {
    
        $entities = '';
        foreach( $unicode as $value ) 
        {
        
            $entities .= ( $value > 127 ) ? '&#' . $value . ';' : chr( $value );
            
        } //foreach
        return $entities;
        
    } // unicode_to_entities_preserving_ascii




function dateFormat($dateTime)
{
	$str = $dateTime;
	$yr = substr($str, -4);
	$hr = substr($str, 11, 2);
	$min = substr($str, 14, 2);
	$sec = substr($str, 17, 2);
	$mmm = substr($str, 4, 3);
	$dd = substr($str, 8,2);
	switch ($mmm) 
	{
		case "Jan":
			$mm = 01;
			break;
		case "Feb":
			$mm = 02;
			break;
		case "Mar":
			$mm = 03;
			break;
		case "Apr":
			$mm = 04;
			break;
		case "May":
			$mm = 05;
			break;
		case "Jun":
			$mm = 06;
			break;
		case "Jul":
			$mm = 07;
			break;
		case "Aug":
			$mm = 08;
			break;
		case "Sep":
			$mm = 09;
			break;
		case "Oct":
			$mm = 10;
			break;
		case "Nov":
			$mm = 11;
			break;
		case "Dec":
			$mm = 12; 
			break;
		default:
			break;
	}
	$dt = "$yr-$mm-$dd $hr:$min:$sec";
	return $dt;
}
 function processRTS($rts)
 {
 	
		
 		$replyXML   = http_get($target="http://twitter.com/statuses/show/$rts.xml", $referer="$tw");
 		$replyArray = parse_array($replyXML['FILE'], "<status>", "</status>");
 		$flag = 1;
	for($i =0; $i<count($replyArray);$i++)
	{
		crawlStatus($replyArray[$i]);
	}
}
 
 
function isUserInDB($u_id)
{
	$idcheck_sql=
"SELECT COUNT(`userID`)
FROM $db.`userprofile`
WHERE `userID` ='".$u_id."';";
$resCheck = mysql_query($idcheck_sql) or die(mysql_error());
$row = mysql_fetch_array($resCheck);
if($row[0]=='0')
	return 0;
else
	return 1;
}

function insertUserDB($userprofile)
{
	$u_id = return_between($userprofile,"<id>","</id>", EXCL);
	$screenName = return_between($userprofile, "<screen_name>", "</screen_name>", EXCL);
	$userDesc = return_between($userprofile, "<description>", "</description>", EXCL);
	$userDesc = utf8_to_unicode( $userDesc );
	$userDesc = unicode_to_entities_preserving_ascii($userDesc);
	$userLocn = return_between($userprofile, "<location>", "</location>", EXCL);
	$userLocn = utf8_to_unicode( $userLocn );
	$userLocn = unicode_to_entities_preserving_ascii($userLocn);
	$userWebpage = return_between($userprofile, "<url>", "</url>", EXCL);
	$userWebpage = utf8_to_unicode( $userWebpage );
	$userWebpage = unicode_to_entities_preserving_ascii($userWebpage);
	$userFollowers = return_between($userprofile, "<followers_count>", "</followers_count>", EXCL);
	$insertUserSQL = sprintf("INSERT INTO $db.`userprofile` (
`userID` ,
`username` ,
`description` ,
`location` ,
`webpage` ,
`followers`
)
VALUES ( '%s','%s','%s','%s','%s','%s')", $u_id, mysql_real_escape_string($screenName), mysql_real_escape_string($userDesc), mysql_real_escape_string($userLocn), mysql_real_escape_string($userWebpage), $userFollowers);
mysql_query($insertUserSQL) or die (mysql_error());
}

function insertDB($s_id, $desc,$hlink, $dt, $rts, $rtu, $tsource, $userprofile)
{
	//extract uid from userprofile for insertion into db and further processing
	$u_id = return_between($userprofile,"<id>","</id>", EXCL);
	$query_status = sprintf("INSERT INTO $db.`statustimeline` (
	`statusID` ,
	`userID` ,
	`description` ,
	`hyperlink` ,
	`creationTime` ,
	`replytosid` ,
	`replytouid` ,
	`tweetsource`)
	VALUES (
	'%s','%s','%s','%s','%s','%s','%s','%s')" ,$s_id, $u_id, mysql_real_escape_string($desc), mysql_real_escape_string($hlink), mysql_real_escape_string($dt), $rts, $rtu, mysql_real_escape_string($tsource));

	//sql to check if a tweet is already present in the database
	$idcheck_sql=
	"SELECT COUNT(`statusID`)
	FROM $db.`statustimeline`
	WHERE `statusID` ='".$s_id."';";
	$resCheck = mysql_query($idcheck_sql) or die(mysql_error());
	$row = mysql_fetch_array($resCheck);

	//if tweet not present in database
	if($row[0]==0) 
	{
		mysql_query($query_status) or die (mysql_error());
		//if user not already present, insert user into database
		if(isUserInDB($u_id)==0) 
			insertUserDB($userprofile);
		//if the tweet is a reply to another tweet, the reply is processed
		if(strlen($rts)>0)
			processRTS($rts);
	}
}

//to handle shortened URLs common to tweets (due to size limitation)
function expandTinyURL($url)
{
	$ch = curl_init();    // initialize curl handle
	curl_setopt($ch, CURLOPT_URL,$url); // set url to post to
	curl_setopt($ch, CURLOPT_FAILONERROR, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable
	curl_setopt($ch, CURLOPT_TIMEOUT, 3); // times out after 4s
	curl_exec($ch);
	
	//effective URL returns the ultimate URL after redirections from sites such as tinyURL
	return curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
}


//if called, this function returns the URL of a single tweet using screen name and status id
function getStURL($sName, $sID)
{
	$tempURL = "http://twitter.com/".$sName."/status/".$sID."xml";
	return ereg_replace(' ', '', $tempURL); 
}

//Echo check to see if strings are handled as required..aids easy check
function disp($var)
{
	echo $var." length of $var : ".strlen($var)."<br />";
}


function crawlStatus($currElement)
{
	$src = $hyperlink = $locn = $userURL = $followers = $rts = $rtu = null;
	//$currElement = $statusArray[$i];
	$createdAt = return_between($currElement, "<created_at>", "</created_at>", EXCL);
	//format the date to Database datetime type (for date based comparisons)
	$dtFormat = dateFormat($createdAt);
	$tempsid = split_string($currElement,"</created_at>", AFTER, EXCL);
	$tempsid = split_string($tempsid, "</id>", BEFORE, EXCL);
	$sid = split_string($tempsid, "<id>", AFTER, EXCL);
	
	$text = return_between($currElement,"<text>", "</text>", EXCL);
	//this and next functions called to handle unicode characters or non english text
	$text = utf8_to_unicode( $text ); 
	$text = unicode_to_entities_preserving_ascii($text);
	
	//preg match to extract URL from tweets, if present (currently for http), match string can be modified for better handling
	$do = preg_match('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', $text, $matches);
	if ($do = true) //if url present
		$hyperlink = expandTinyURL(htmlentities($matches['0'])); //tweets usually contain tiny urls ->expansion needed
	
	$src = return_between($currElement,"<source>", "</source>", EXCL);
	$src = strip_tags($src);
	
	//gathering reply to information, if the tweet is a reply 
	$rts = return_between($currElement, "<in_reply_to_status_id>", "</in_reply_to_status_id>", EXCL);
	$rtu = return_between($currElement, "<in_reply_to_user_id>", "</in_reply_to_user_id>", EXCL);
	
	//extracting user information as an array
	$userprofile = return_between($currElement, "<user>", "</user>", EXCL);
	$flag = 0;
	
	insertDB($sid, $text,$hyperlink, $dtFormat, $rts, $rtu, $src, $userprofile);
}	

for($loop=0;$loop<$runs;$loop++) //runs - number of crawls - variable present in variables.php
{
	# Download public timeline xml
	
	$web_page   = http_get($target=$publictimeLineXML, $referer=$twitter); //refer config/URLList.php for URLs used
	$statusArray = parse_array($web_page['FILE'], "<status>", "</status>");
	
	for($i =0; $i<count($statusArray);$i++)
	{
		crawlStatus($statusArray[$i]);
	}
	sleep($crawlDelay);
}
?>
