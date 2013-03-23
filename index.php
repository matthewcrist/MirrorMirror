<?php
require_once 'creds.php';

if($_GET['key'] && $_GET['key'] == $id['key']) {
	up($id['S3'], $id['DB'], $id['base']);
}elseif(isset($_GET['short'])) {
	get($_GET['short'], $id['DB']);
}else {
	echo json_encode(array('error' => true));
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
	$imginfo = getimagesize($localfile);

	if ($imginfo !== false) {
	    $mimetype = $imginfo['mime'];
	    $allowedmimes = array("video/quicktime", "image/png", "image/jpeg", "image/gif", "image/bmp");
	    if (in_array($mimetype, $allowedmimes)) { 
			
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
				echo json_encode(array(error => true));
				exit;
			}
	    }
	}
	else {
	    echo "This is not a valid image file.";
	    exit;
	}
}

function addToDB($dbinfo, $baseURL, $sentInfo) {
	$S3url = $sentInfo['url'];

	$con = mysql_connect($dbinfo['host'], $dbinfo['user'], $dbinfo['pass']) or die(mysql_error());
	$db = mysql_select_db($dbinfo['db'], $con) or die(mysql_error());

	$rand = rand(0, 27);
	$short = substr(md5(time()), $rand, 5);

	$query = "INSERT INTO `images` (`url`, `short`) VALUES ('$S3url', '$short');";
	mysql_query($query, $con);

	$returnURL;
	if($sentInfo['isMov'] == 1) {
		$returnURL = $S3url;
	}else {
		$returnURL = $baseURL.$short.".png";
	}

	return "<mediaurl>".$returnURL."</mediaurl>";
}

function checkValidity($short) {
	if(strlen($short) == 5) {
		return $short;
	}elseif(strlen($short) == 9) {
		return substr($short, 0, 5);
	}else{
		echo "Invalid URL.";
		exit;
	}
}

function imageRetrieve($short, $dbinfo) {
	$con = mysql_connect($dbinfo['host'], $dbinfo['user'], $dbinfo['pass']) or die(mysql_error());
	$db = mysql_select_db($dbinfo['db'], $con) or die(mysql_error());

	$query  = "SELECT * FROM `images` WHERE `short` = '$short'";
	$search = mysql_query($query, $con);
	$rownum = mysql_num_rows($search);

	if($rownum == 1) {
		$assoc = mysql_fetch_assoc($search);
		$id = $assoc['id'];
		$views = $assoc['views'];
		$views++;
		$updatequery = "UPDATE  `images` SET  `views` =  $views WHERE `id` = $id";
		mysql_query($updatequery, $con);

		return $assoc['url'];
	}elseif($rownum == 0) {
		echo "Image does not exist.";
		exit;
	}else {
		echo "Uhh. IDK what happened here. That's weird. Kay.";
		exit;
	}
}

function displayImage($short, $dbinfo) {
	$url = imageRetrieve($short, $dbinfo);

	header('Content-Type: image/png');
	readfile($url);
}

?>