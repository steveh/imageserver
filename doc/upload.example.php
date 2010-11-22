<?php
require "../global.php";

$server = "upload.local";
$bucket = "localist-images";
$prefix = "users/1/";

$path = "http://{$server}/upload.php";

$params = array(
  "bucket" => $bucket,
  "prefix" => $prefix,
);

ksort($params);

$signing_string = http_build_query($params, null, "&");

$signature = hash_hmac("sha1", $signing_string, $secret);

$signed_params = array_merge($params, array("signature" => $signature));
?>
<form action="<?= $path ?>" method="post" enctype="multipart/form-data">

  <div>
    <label for="file">File</label>
    <input type="file" name="file" id="file" />
  </div>

  <div>
    <input type="hidden" name="bucket" value="<?= $bucket ?>" />
    <input type="hidden" name="prefix" value="<?= $prefix ?>" />
    <input type="hidden" name="signature" value="<?= $signature ?>" />

    <button type="submit">Upload</button>
  </div>

</div>