<?php
/*
 * tweetspider
 * Created on Apr 20, 2010
 * Author : nb
 * Email  : bharadhwaj.n@gmail.com
 * Twitter: @bharadhwaj
 */
 $runs		 = 2;
 
 //delay between crawls in seconds 
 //twitter only allows 150 requests/hour
 //and a single crawl makes one request for a public timeline
 //and as many requests for the number of replies in the timeline 
 //recursively until all replies are resolved
 $crawlDelay = 900; 
?>
