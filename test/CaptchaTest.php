<?php
include '../webpie.php';
new Webpie;
session_start();
$a = new Webpie_Captcha();
$a->createImage();
