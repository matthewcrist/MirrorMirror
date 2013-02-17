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
	$filename = $_FILES['media']['name'];

	// Image filetype check source:
	// http://designshack.net/articles/php-articles/smart-file-type-detection-using-php/
	$imginfo_array = getimagesize($localfile);

	if ($imginfo_array !== false) {
	    $mime_type = $imginfo_array['mime'];
	    $mime_array = array("video/quicktime", "image/png", "image/jpeg", "image/gif", "image/bmp");
	    if (in_array($mime_type , $mime_array)) { 
			
			$now = time();
			$dotwhat = ".png";
			if ($mime_type == "video/quicktime") {
				$dotwhat = ".mov";
			}
			$uploadFilename = $now.$dotwhat;

			$response;
			if ($s3->putObjectFile($localfile, $id['bucket'], $uploadFilename, S3::ACL_PUBLIC_READ)) {
				$response = "<mediaurl>https://s3.amazonaws.com/".$id['bucket']."/".$uploadFilename."</mediaurl>";
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