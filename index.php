<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once('ReviewGrabber.php');

function printX($x) {
    echo '<pre>';
    print_r($x);
    echo '</pre>';
}

$url = 'http://wikicity.kz/biz/janym-soul-almaty';

//$startTime = microtime(true);

try 
{
    $grabber = new ReviewGrabber($url);
    $data = $grabber->getReviewData();
    //echo "<br>Time:  " . number_format(( microtime(true) - $startTime), 4) . " Seconds\n";
    printX($data);
} catch (Exception $e) 
{
    echo $e->getMessage();
    die();
}
?>