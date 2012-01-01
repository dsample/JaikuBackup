<?php

# Username you want to download the posts for
$username = '';

# Login information taken from browser cookies - NOT YOUR PLAINTEXT PASSWORD
$jaikuuser = ''; #eg. 'dsample@jaiku.com'
$jaikupassword = ''; #will look random like 'asdfasdfasdfasdfasdfasdfasdfasdf'

# Number of pages of post listings to scrape (0 for all)
$pages = 1;

/*

USAGE INSTRUCTIONS

This script aims to help with backing up public Jaiku data. If you would like to download private posts you will need to populate the cookie_username and cookie_password variables above with the data stored in your browser cookie for the Jaiku site (get this from Chrome's 'inspect' feature or similarly from Firefox).


1. Run this with 'php scrape.php';

*Make sure you are running this in a fresh directory, otherwise this will get cluttered*

Once it's run you will have several files, all prepended with the username you're downloading for:

- posts.list		A file listing all of the URLs for posts the user has been involved in
- jsonposts.list	A file listing all of the URLs for the JSON of each of the above posts. JSON is a structured data version of the web page and will be easier to import into other things at a later time.
- jsonposts.script	A script file for downloading the above JSON data in a nice way


2. Download the full posts

2a. PUBLIC

wget -E -H -k -K -p -i posts.list

The above command will download all of the web pages for the posts inside posts.list and will convert them all to work as downloaded pages (without needing jaiku.com to still be around)

Make sure to prepend the username onto the posts.list (eg. dsample_posts.list)

2b. INCLUDING PRIVATE POSTS

wget --no-cookies --header "Cookie: jaikuuser=; jaikupassword=" -E -H -k -K -p -i posts.list

Fill in the cookie information into this command as you found earlier

3. Download the data

bash jsonposts.script

This will download the JSON files. If you specified the cookie login information it will have stored the cookie information in the script already.

*/

/***** LEAVE EVERYTHING BELOW HERE ALONE *****/

$jaiku_start_url = 'http://' . $username . '.jaiku.com/';

if (!function_exists('curl_init') || !function_exists('curl_exec')) die('Need to install cURL');

#if (!function_exists('simplexml_load_string')) die('Need to install SimpleXML');

function download_file($url)
{
	global $jaikuuser, $jaikupassword;
	$ch = curl_init($url);
	if ($ch)
	{
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if ($jaikuuser && $jaikupassword) {
			curl_setopt($ch, CURLOPT_COOKIE, 'jaikuuser=' . $jaikuuser . '; jaikupassword=' . $jaikupassword);
		}
		$contents = curl_exec($ch);
		curl_close($ch);
		
		return $contents;
	}
	
	return false;
}

function to_xml($string)
{
#	return simplexml_load_string($string);
	$doc = new DOMDocument;
	$doc->preserveWhiteSpace = false;
	$doc->strictErrorChecking = false;
	$doc->recover = true;
	@$doc->loadHTML($string);
	return $doc;
}

function do_xpath($xml, $query)
{
	$xpath = new DOMXpath($xml);
	return $xpath->query($query);
}

/***** PROCESS *****/

$listPages = fopen($username . '_listpages.list','a+');
@fwrite($listPages, "\n\n# " . date('c') . "\n\n");

$listPosts = fopen($username . '_posts.list','a+');
@fwrite($listPosts, "\n\n# " . date('c') . "\n\n");
$listJSONs = fopen($username . '_jsonposts.list','a+');
@fwrite($listJSONs, "\n\n# " . date('c') . "\n\n");
$listJSONscript = fopen($username . '_jsonposts.script','a+');
@fwrite($listJSONscript, "\n\n# " . date('c') . "\n\n");


if (file_exists($username . '_nextpage.url')) {
	$nextPage = file_get_contents($username . '_nextpage.url');
}
else
{
	$nextPage = $jaiku_start_url;	
}

$pagesProcessed = 0;

while ($nextPage != false) {
	fwrite($listPages, $nextPage . "\n");
	$file = download_file($nextPage);
	$xml = to_xml($file);
	
	$postsToSave = processPosts($xml);
	foreach($postsToSave as $postURL)
	{
		$numMatches = preg_match('@http://(?P<user>\w+)\.jaiku.com/presence/(?P<id>\w+)@i', $postURL, $matches);
	
		if ($numMatches > 0)
		{
			/* It's a user post */
			
			$post_user = $matches['user'];
			$post_id = $matches['id'];
	
		}
		else
		{
			/* Might be a channel post */
			$numMatches = preg_match('@http://www.jaiku.com/channel/(?P<channel>\w+)/presence/(?P<id>\w+)@i', $postURL, $matches);
			
			if ($numMatches > 0)
			{
				$post_user = 'channel_' . $matches['channel'];
				$post_id = $matches['id'];
			}
			else
			{
				$post_user = '_ERROR_';
				$post_id = '$postURL';
			}
		}

		echo $post_user . ": " . $post_id . "\n";
		
		fwrite($listPosts, $postURL . "\n");
		fwrite($listJSONs, $postURL . "/json\n");
		if ($jaikuuser && $jaikupassword)
		{
			$cookie = ' --cookie "jaikuuser=' . $jaikuuser . '; jaikupassword=' . $jaikupassword . '"';
		}
		else
		{
			$cookie = '';
		}
		fwrite($listJSONscript, 'curl' . $cookie . ' --create-dirs -o ' . $username . '_json/' . $post_user . '/' . $post_id . '.json ' . $postURL . "/json\n");
	}

	$nextPage = findNextPage($xml);

	echo 'Next Page: ' . $nextPage . "\n";
	$nextPageFile = fopen($username . '_nextpage.url', 'w+');
	fwrite($nextPageFile, $nextPage);
	fclose($nextPageFile);

	if ($pages > 0 && $pagesProcessed++ == $pages)
	{
		$nextPage = false;
		unlink($username . '_nextpage.url');
	}
}

fclose($listPages);
fclose($listPosts);
fclose($listJSONs);
fclose($listJSONscript);
echo "\nDone\n";
exit();

/***** INTELLIGENCE *****/

function processPosts($xml) 
{
	/* First, the presence posts */
	
	$posts = do_xpath($xml, '//li[@class="presence"]//h3/a');

	#echo "*ARGH*<pre>" . dom_dump($posts) . "</pre>";

	foreach ($posts as $post) {
		#echo "POST: {$post->nodeValue}\n";
		echo "POST: {$post->getAttribute('href')}\n";
		
		$returnPosts[] = $post->getAttribute('href');
	}
	
	/* Now the comments, slightly more difficult query */
	
	$posts = do_xpath($xml, '//li[@class="comment"]//p[@class="meta"]/a[@title]');

	foreach ($posts as $post) {
		$url = preg_replace('/\#c-.*/', '', $post->getAttribute('href'));
		#echo "COMMENT: {$post->nodeValue}\n";
		echo "COMMENT: {$url}\n";
		
		$returnPosts[] = $url;
	}
	
	
	return array_unique($returnPosts);
}

function findNextPage($xml)
{
	global $jaiku_start_url;
	$npResult = do_xpath($xml, '//div[@class="paging"]//a');
	foreach ($npResult as $link)
	{
		if (trim($link->nodeValue) == 'Older') {
			return $jaiku_start_url . $link->getAttribute('href');
		}
	}
	return false;
}

/***************/

function dom_dump($obj) {
    if ($classname = get_class($obj)) {
        $retval = "Instance of $classname, node list: \n";
        switch (true) {
            case ($obj instanceof DOMDocument):
                $retval .= "XPath: {$obj->getNodePath()}\n".$obj->saveXML($obj);
                break;
            case ($obj instanceof DOMElement):
                $retval .= "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
                break;
            case ($obj instanceof DOMAttr):
                $retval .= "XPath: {$obj->getNodePath()}\n".$obj->ownerDocument->saveXML($obj);
                //$retval .= $obj->ownerDocument->saveXML($obj);
                break;
            case ($obj instanceof DOMNodeList):
                for ($i = 0; $i < $obj->length; $i++) {
                    $retval .= "Item #$i, XPath: {$obj->item($i)->getNodePath()}\n".
"{$obj->item($i)->ownerDocument->saveXML($obj->item($i))}\n";
                }
                break;
            default:
                return "Instance of unknown class";
        }
    } else {
        return 'no elements...';
    }
    return htmlspecialchars($retval);
}

?>
