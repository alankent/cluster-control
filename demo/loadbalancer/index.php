<?php

header("Content-Type: text/plain");

$webservers = json_decode(file_get_contents("webservers.json"));
foreach ($webservers as $svr) {
    $resp = file_get_contents("http://".$svr."/");
    echo "$svr: $resp";
}
