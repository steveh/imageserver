<?php
require "../global.php";

$bucket = isset($_GET["bucket"]) ? $_GET["bucket"] : "";
$original_key = isset($_GET["key"]) ? $_GET["key"] : "";

$w = isset($_GET["w"]) ? (int) $_GET["w"] : null;
$h = isset($_GET["h"]) ? (int) $_GET["h"] : null;
$x = isset($_GET["x"]) ? (int) $_GET["x"] : null;
$y = isset($_GET["y"]) ? (int) $_GET["y"] : null;

$quality = isset($_GET["quality"]) ? (int) $_GET["quality"] : 80;

$expires = 1 * 365 * 24 * 60 * 60;

$object = $s3->get_object($bucket, $original_key);

$srcgd = imagecreatefromstring($object->body);

if ($x || $y) {

  $resgd = crop_resize_image ($srcgd, $w, $h, $w, $h, $x, $y);

  $generated_filename = "{$w}x{$h}-{$x}x{$y}y";

} else {

  $resgd = resize_image ($srcgd, $w, $h);

  $generated_filename = "{$w}x{$h}";

}

$temp_path = tempnam(null, null);

imagejpeg($resgd, $temp_path, $quality);

$file = new stdClass();

$file->original_filename = $object->header["x-amz-meta-original-filename"];

$file->bucket = $bucket;

$file->width = imagesx($resgd);
$file->height = imagesy($resgd);

$file->size = filesize($temp_path);

$prefix = "thumbnails/deadbeef/";

$extension = "jpg";

$filename = "{$generated_filename}.{$extension}";

$file->key = "{$prefix}{$filename}";

$dimensions = getimagesize($temp_path);

list ($file->width, $file->height) = $dimensions;

$file->md5 = md5_file($temp_path);
$file->sha1 = sha1_file($temp_path);

$file->url = "http://{$bucket}.s3.amazonaws.com/{$file->key}";

$object = $s3->create_object(
  $bucket,
  $file->key,
  array(
    "acl" => AmazonS3::ACL_PUBLIC,
    "fileUpload" => $temp_path,
    "contentType" => "image/jpeg",
    "headers" => array(
      "Expires" => ($expires * 1000),
      "Cache-Control" => "max-age={$expires}",
    ),
    "meta" => array(
      "md5" => $file->md5,
      "sha1" => $file->sha1,
      "original-filename" => $file->original_filename,
      "width" => $file->width,
      "height" => $file->height,
    ),
  )
);

header("Content-type: text/javascript");

print json_encode($file);