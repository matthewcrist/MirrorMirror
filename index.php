<?php
require_once 'creds.php';

if($_GET['key'] == $id['key']) {
	upload($id['S3']);
} else{
	echo json_encode(array('error' => true));
}

function upload($id) {
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
			if ($mimetype == "video/quicktime") {
				$dotwhat = ".mov";
			}
			$uploadname = $now.$dotwhat;

			$response;
			if ($s3->putObjectFile($localfile, $id['bucket'], $uploadname, S3::ACL_PUBLIC_READ)) {
				$response = "<mediaurl>https://s3.amazonaws.com/".$id['bucket']."/".$uploadname."</mediaurl>";
			}else{
				$response = json_encode(array(error => true));
			}
			echo $response;
			
	 
	    }
	}
	else {
	    echo "This is not a valid image file";
	}
}

?>