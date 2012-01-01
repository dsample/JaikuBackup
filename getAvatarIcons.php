<?php
/*

USAGE INSTRUCTIONS

This script looks at the JSON files the Jaiku backup script helped you download, and downloads the user avatar images for all the users in all of the posts

Run it:

find -name *.json . | php getAvatarIcons.php > avatars.list

*/

/***** LEAVE EVERYTHING BELOW HERE ALONE *****/

if (!function_exists('json_decode')) die('Need to compile PHP with JSON');

$imageVariations = array('u','t','l','m','f');

$input = fopen("php://stdin", 'r');
$filelist = "";
while (!feof($input))
{
	$filelist .= fread($input, 1024);
}
fclose($input);

$files = preg_split('/\r\n|\r|\n/', $filelist);

foreach($files as $file)
{
	if (strlen($file) > 5)
	{
		$fp = fopen($file, 'r');
		$raw_json = "";
		while(!feof($fp)) {
			$raw_json .= fread($fp, 1024);
		}
		fclose($fp);
		
		$json = json_decode($raw_json, true);

		switch (json_last_error()) {
	        case JSON_ERROR_NONE:
	            #No error, continue
	        break;
	        case JSON_ERROR_DEPTH:
	            echo '# JSON ERROR: ' . $file . ' - Maximum stack depth exceeded' . "\n";
	        break;
	        case JSON_ERROR_STATE_MISMATCH:
	            echo '# JSON ERROR: ' . $file . ' - Underflow or the modes mismatch' . "\n";
	        break;
	        case JSON_ERROR_CTRL_CHAR:
	            echo '# JSON ERROR: ' . $file . ' - Unexpected control character found' . "\n";
	        break;
	        case JSON_ERROR_SYNTAX:
	            echo '# JSON ERROR: ' . $file . ' - Syntax error, malformed JSON' . "\n";
	        break;
	        case JSON_ERROR_UTF8:
	            echo '# JSON ERROR: ' . $file . ' - Malformed UTF-8 characters, possibly incorrectly encoded' . "\n";
	        break;
	        default:
	            echo '# JSON ERROR: ' . $file . ' - Unknown error' . "\n";
	        break;
	    }

		/* User first */
	
		if ($json['user']['avatar'])
		{
			$user_nick = $json['user']['nick'];
			$user_avatar = $json['user']['avatar'];
			$avatars[$user_nick] = $user_avatar;
		}
		
		
		foreach ($json['comments'] as $comment)
		{
			if ($comment['user']['avatar'])
			{
				$user_nick = $comment['user']['nick'];
				$user_avatar = $comment['user']['avatar'];
				$avatars[$user_nick] = $user_avatar;
			}
		}
	}
	else
	{
		echo $file . "\n";
	}
}

foreach ($avatars as $nick => $avatar)
{
	foreach ($imageVariations as $var)
	{
		$image_url = preg_replace('@_\w\.jpg$@i', '_' . $var . '.jpg', $avatar);
		echo 'curl -C --create-dirs -o avatars/' . $nick . '_' . $var . '.jpg ' . $image_url . "\n";
	}
}

?>