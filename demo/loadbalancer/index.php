<?php

header("Content-Type: text/plain");
header("X-Test: PHP Invoked");

$jsonFile = "webservers.json";
echo "Reading $jsonFile...\n";

if (!file_exists($jsonFile)) {

    echo "File does not exist\n";

} else {

    $json = file_get_contents($jsonFile);
    echo "$jsonFile: $json\n\n";

    $webservers = json_decode($json, true);
    foreach ($webservers as $svr) {
        echo "$svr: ";
        try {
            $content = file_get_contents("http://".$svr."/");
            if (strlen($content) > 0) {
                echo $content;
            } else {
                echo "(no response)\n";
            }
        }
        catch (\Exception $ex) {
            echo "$ex\n";
        }
    }

}

echo "\nDone\n";
