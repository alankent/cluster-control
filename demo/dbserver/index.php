<?php

header("Content-Type: text/plain");
header("X-Test: PHP Invoked");

$file = "myname.txt";

if (!file_exists($file)) {

    echo "File '$file' does not exist!\n";

} else {

    $id = file_get_contents($file);
    if (strlen($id) == 0) {
        echo "??\n";
    } else {
        echo "$id\n";
    }

}

