<?php
function generate_filename ($length = 8) {
  $r = "";

  for ($i = 0; $i < $length; $i++) {
    $r .= chr(rand(97, 122));
  }

  return $r;
}

function mime_type_extension ($mime_type) {

  static $map = array(
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/gif" => "gif",
  );

  return $map[$mime_type];

}

/**
 * Resize a GD image object, keeping proportion and optionally padding to desired size
 *
 * @param resource $gd GD image object
 * @param int $reqw Max width
 * @param int $reqh Max height
 * @param bool $pad Pad to max width and height?
 * @param int $cnvr Background colour red value
 * @param int $cnvg Background colour green value
 * @param int $cnvb Background colour blue value
 * @return resource Resized GD image object
 */
function resize_image ($gd, $reqw = false, $reqh = false, $pad = false, $cnvr = 255, $cnvg = 255, $cnvb = 255) {

  $srcw = imagesx ($gd);
  $srch = imagesy ($gd);

  $cnvw = $reqw;      // for now canvas width and height
  $cnvh = $reqh;    // equals requested width and height

  if ($reqw > $srcw)    // if requested width is greater than the image width
    $reqw = $srcw;    // change requested width to image width

  if ($reqh > $srch)  // same for image height
    $reqh = $srch;

  // if requested size = image size, or no size requested
  if (
    ($reqw == $srcw and $reqh == $srch)
    or ($reqh == false and $reqw == false)) {

    $resw = $reqw;    // resize width is requested width
    $resh = $reqh;  // as for height

  // requested size is different
  } else {

    $ratw = $reqw / $srcw;
    $rath = $reqh / $srch;

    // resize by width
    if ($reqh == false or $ratw < $rath) {

      $resw = $reqw;
      $resh = $srch * $ratw;

    // resize by height
    } elseif ($reqw == false or $rath < $ratw) {

      $resw = $srcw * $rath;
      $resh = $reqh;

    // resize equally
    } else {

      $resw = $srcw * $rath;
      $resh = $srch * $ratw;

    }

  }

  if ($pad) {

    $resx = ($cnvw - $resw) / 2;
    $resy = ($cnvh - $resh) / 2;

  } else {

    $resx = 0;
    $resy = 0;

    $cnvw = $resw;
    $cnvh = $resh;

  }

  if ($resw < 1)
    $resw = $cnvw;

  if ($resh < 1)
    $resh = $cnvh;

  if (!$resized = imagecreatetruecolor ($cnvw, $cnvh))
    return false;

  $fill = imagecolorallocate ($resized, $cnvr, $cnvg, $cnvb);

  if ($fill === false)
    return false;

  if (!imagefill ($resized, 0, 0, $fill))
    return false;

  if (!imagecopyresampled (
    $resized, $gd,
    $resx, $resy,
    0, 0,
    $resw, $resh,
    $srcw, $srch
  ))
    return false;

  return $resized;

}

function crop_resize_image ($gd, $cnvw, $cnvh, $reqw, $reqh, $reqx, $reqy) {

  $srcw = imagesx ($gd);
  $srch = imagesy ($gd);

  if (!$resized = imagecreatetruecolor ($cnvw, $cnvh))
    return false;

  if (!imagecopyresampled (
    $resized, $gd,
    0, 0,
    $reqx, $reqy,
    $cnvw, $cnvh,
    $reqw, $reqh
  ))
    return false;

  return $resized;

}

function uploadS3 ($s3, $bucket, $key, $temp_path, $params) {

  return $s3->create_object(
    $bucket,
    $key,
    array(
      "acl" => AmazonS3::ACL_PUBLIC,
      "fileUpload" => $temp_path,
      "contentType" => $params["mime_type"],
      "headers" => array(
        "Expires" => ($params["expires"] * 1000),
        "Cache-Control" => "max-age={$params["expires"]}",
      ),
      "meta" => array(
        "unique" => $params["unique"],
        "md5" => $params["md5"],
        "sha1" => $params["sha1"],
        "original-filename" => $params["original_filename"],
        "width" => $params["width"],
        "height" => $params["height"],
      ),
    )
  );

}

function s3url ($bucket, $key) {

  return "http://{$bucket}.s3.amazonaws.com/{$key}";

}