<?php
require "../global.php";

$bucket = isset($_POST["bucket"]) ? $_POST["bucket"] : "";
$prefix = isset($_POST["prefix"]) ? $_POST["prefix"] : "";
$provided_signature = isset($_POST["signature"]) ? $_POST["signature"] : "";
$expires = 1 * 365 * 24 * 60 * 60;

if (!$bucket) {
  die("Bucket not provided\n");
}

if (!$prefix) {
  die("Prefix not provided\n");
}

if (!$provided_signature) {
  die("Signature not provided\n");
}

$params = array(
  "bucket" => $bucket,
  "prefix" => $prefix,
);

ksort($params);

$signing_string = http_build_query($params, null, "&");

$expected_signature = hash_hmac("sha1", $signing_string, $secret);

if ($provided_signature != $expected_signature) {
  die("Signature does not match\n");
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);

$files = array();

foreach ($_FILES as $name => $upload) {

  $file = new stdClass();

  $file->bucket = $bucket;
  $file->name = $name;

  $temp_path = $upload["tmp_name"];

  $file->size = $upload["size"];
  $file->original_filename = $upload["name"];

  $file->unique = generate_filename(8);

  $file->mime_type = finfo_file($finfo, $temp_path);

  if (!$file->mime_type) {
    continue;
  }

  $extension = mime_type_extension($file->mime_type);

  if (!$extension) {
    continue;
  }

  $filename = "{$file->unique}.{$extension}";

  $file->key = "{$prefix}{$filename}";

  $file->md5 = md5_file($temp_path);
  $file->sha1 = sha1_file($temp_path);

  $file->url = s3url($file->bucket, $file->key);

  $dimensions = getimagesize($temp_path);

  list ($file->width, $file->height) = $dimensions;
  
  $object = uploadS3($s3, $file->bucket, $file->key, $temp_path, array(
    "unique" => $file->unique,
    "mime_type" => $file->mime_type,
    "expires" => $expires,
    "md5" => $file->md5,
    "sha1" => $file->sha1,
    "original_filename" => $file->original_filename,
    "width" => $file->width,
    "height" => $file->height,
  ));

  $files[] = $file;

}

finfo_close($finfo);

header("Content-type: text/javascript");

print json_encode($files);