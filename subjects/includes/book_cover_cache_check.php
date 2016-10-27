<?php
/**
 * Created by PhpStorm.
 * User: acarrasco
 * Date: 9/2/2016
 * Time: 11:33 AM
 */

$book_cover_cache_check = function ($isbn) {

    $prefix = explode('subjects', dirname(__FILE__));
    $file = $prefix[0]."/assets/cache/".$isbn.".jpg";

    if (file_exists($file)) {
        echo $file;
    } else {
        echo '';
    }
};

$book_cover_cache_check(htmlspecialchars($_GET['isbn']));