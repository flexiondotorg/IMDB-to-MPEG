<?php
/**
 * If it show "Fatal error: Call to undefined function: imagecreate()" then 
 * you have to install GD. To know detail about GD 
 * see that documentation : http://jp.php.net/imagecreate
 * 
 */


ini_set("display_errors",1);

require_once('TextToImage.class.php');

$_im = new TextToImage();
$_im->makeImageF("New life in programming.","CENTURY.TTF");

//$_im->showAsJpg();

//$_im->showAsPng(); 
//$_im->saveAsPng("Image1");


$_im->showAsGif();



?>
