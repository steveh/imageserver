<?php
require "config.php";
require "functions.php";
require_once "lib/aws/sdk.class.php";

$s3 = new AmazonS3($access_key_id, $secret_access_key);