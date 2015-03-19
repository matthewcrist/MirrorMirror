<?php
require_once 'creds.php';

if($_GET['key'] && $_GET['key'] == $id['key']) {
	up($id['S3'], $id['DB'], $id['base']);
}elseif(isset($_GET['short'])) {
	get($_GET['short'], $id['DB']);
}else {
	exit("Hello"); #e9
}

if(!function_exists('getallheaders')) {
    function getallheaders()
    {
           $headers = '';
       foreach ($_SERVER as $name => $value)
       {
           if (substr($name, 0, 5) == 'HTTP_')
           {
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           }
       }
       return $headers;
    }
}

function up($id, $dbinfo, $baseURL) {
	$sentInfo  = sendToS3($id);
	$publicURL = addToDB($dbinfo, $baseURL, $sentInfo);

	echo $publicURL;
	exit;
}

function get($short, $dbinfo) {
	$newshort = checkValidity($short);
	if(isset($newshort)) {
		displayImage($newshort, $dbinfo);
	}
}

function sendToS3($id) {
	// include the API
	if (!class_exists('S3'))require_once('S3.php');

	//AWS access info
	if (!defined('awsAccessKey')) define('awsAccessKey', $id['access']);
	if (!defined('awsSecretKey')) define('awsSecretKey', $id['secret']);

	//instantiate the class
	$s3 = new S3(awsAccessKey, awsSecretKey);

	// store image information from Tweetbot
	$localfile = $_FILES['media']['tmp_name'];
	// $filename = $_FILES['media']['name'];

	// Image filetype check source:
	// http://designshack.net/articles/php-articles/smart-file-type-detection-using-php/
	$mimetype = image_type_to_mime_type(exif_imagetype($localfile));

	if (isset($mimetype)) {
	    $allowedmimes = array("video/quicktime", "image/png", "image/jpeg", "image/gif", "image/bmp");
	    if(in_array($mimetype, $allowedmimes)) {

			$now = time();
			$dotwhat = ".png";
			$isMov = 0;
			if ($mimetype == "video/quicktime") {
				$dotwhat = ".mov";
				$isMov = 1;
			}
			$uploadname = $now.$dotwhat;


			if ($s3->putObjectFile($localfile, $id['bucket'], $uploadname, S3::ACL_PUBLIC_READ)) {
				return array('url' => "https://s3.amazonaws.com/".$id['bucket']."/".$uploadname,
					'isMov' => $isMov);
			}else{
				exit("Image was not uploaded. Internal-use error code: 1."); #e1
			}
	    }
	}
	else {
	    exit("This is not a valid image file. Internal-use error code: 2."); #e2
	}
}

function randNum() {
	return substr(md5(time()), $rand, 5);
}

function generateShortCode($dbinfo) {
	$con = mysql_connect($dbinfo['host'], $dbinfo['user'], $dbinfo['pass']) or die(mysql_error());
	$db = mysql_select_db($dbinfo['db'], $con) or die(mysql_error());

	$short;
	$okay = false;
	$i = 0;

	while(!$okay && $i < 100) {
		$short = randNum();

		$query  = "SELECT * FROM `images` WHERE `short` = '$short'";
		$search = mysql_query($query, $con);
		$rownum = mysql_num_rows($search);

		if($rownum == 0) {
			$okay = true;
		}else {
			$i++;
		}
	}

	if($okay) {
		return $short;
	}else {
		return "No free short URL codes. E8"; #e8
	}
}

function addToDB($dbinfo, $baseURL, $sentInfo) {
	$S3url = $sentInfo['url'];

	$con = mysql_connect($dbinfo['host'], $dbinfo['user'], $dbinfo['pass']) or die(mysql_error());
	$db = mysql_select_db($dbinfo['db'], $con) or die(mysql_error());

	$short = generateShortCode($dbinfo);

	if($short != "No free short URL codes. E8" && strlen($short) == 5) {
		$query = "INSERT INTO `images` (`url`, `short`) VALUES ('$S3url', '$short');";
		mysql_query($query, $con);

		$returnURL;
		if($sentInfo['isMov'] == 1) {
			$returnURL = $S3url;
		}else {
			$returnURL = $baseURL.$short.".png";
		}

	}elseif($short == "No free short URL codes. E8") {
		$returnURL = $S3url;

	}else {
		exit("Somehow the short URL mutated and is now sentient (or something like that). Internal-use error code: 10."); #e10
	}

	return "<mediaurl>".$returnURL."</mediaurl>";
}

function checkValidity($short) {
	if(strlen($short) == 5) {
		return $short;
	}elseif(strlen($short) == 9) {
		return substr($short, 0, 5);
	}else{
		exit("Invalid URL. Internal-use error code: 3."); #e3
	}
}

function imageRetrieve($short, $dbinfo, $isBrowser) {
	$con = mysql_connect($dbinfo['host'], $dbinfo['user'], $dbinfo['pass']) or die(mysql_error());
	$db = mysql_select_db($dbinfo['db'], $con) or die(mysql_error());

	$query  = "SELECT * FROM `images` WHERE `short` = '$short'";
	$search = mysql_query($query, $con);
	$rownum = mysql_num_rows($search);

	if($rownum == 1) {
		$assoc = mysql_fetch_assoc($search);
		$id = $assoc['id'];

		if($isBrowser == 0) {
			$cviews = $assoc['cviews'];
			$cviews++;
			$updatequery = "UPDATE  `images` SET  `cviews` =  $cviews WHERE `id` = $id";
		}elseif($isBrowser == 1) {
			$views = $assoc['views'];
			$views++;
			$updatequery = "UPDATE  `images` SET  `views` =  $views WHERE `id` = $id";
		}else{
			exit("Could not update view count because the browser type is invalid. Internal-use error code: 4."); #e4
		}

		mysql_query($updatequery, $con);

		return $assoc;

	}elseif($rownum == 0) {
		exit("Image does not exist. Internal-use error code: 5."); #e5
	}else {
		exit("More than one image with this short URL exists? What? Internal-use error code: 6."); #e6
	}
}

function checkBrowser() {
	$a = http_get_request_headers();
	var_dump($a);

	if(strpos($a['Accept'],"text/html") === false) {
		return 0;
	}else{
		return 1;
	}
}

function displayImage($short, $dbinfo) {
	$isBrowser = checkBrowser();
	$info = imageRetrieve($short, $dbinfo, $isBrowser);

	$html = '<!DOCTYPE html>
<html lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0" /><title>'.$info['date'].'</title><link rel="stylesheet" type="text/css" href="display.css" /></head><body><div id="wrapper"><img class="out" src="'.$info['url'].'" onclick="var c=body.className,r;if(c==\'out\'){r=\'in\'}else{r=\'out\'}body.className=r;" alt="'.$info['date'].'"></div></body></html>';

	if($isBrowser == 0) {
		header('Location: '.$url);
		exit;
	}elseif($isBrowser == 1) {
		echo $html;
		exit;
	}else {
		exit("Could not display the image because the browser type is invalid. Internal-use error code: 7."); #e7
	}
}

?>
