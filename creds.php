<?php
  $url = parse_url(getenv("JAWSDB_URL"));

  $id = array('S3' => array('access' => getenv("AWS_ACCESS_KEY"),
                            'secret' => getenv("AWS_SECRET_KEY"),
                            'bucket' => getenv("AWS_BUCKET")),

              'key' => '831IbRe',

              'DB' => array(
                'db' => substr($url["path"], 1),
                'host' => $url["host"],
                'user' => $url["user"],
                'pass' => $url["pass"]
               ),

              'base' => 'http://hondo.media/'
          );
?>
