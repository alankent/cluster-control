<?php

header("Content-Type: text/plain");

$jsonFile = "webservers.json";

if (!file_exists($jsonFile)) {
    throw \Exception("JSON file $jsonFile does not exist");
}

$webservers = json_decode(file_get_contents("webservers.json"));

foreach ($webservers as $svr) {
    try {
        $resp = file_get_contents("http://".$svr."/");
    }
    catch (Exception $ex) {
        echo "$ex\n";
    }
    echo "$svr: $resp";
}
