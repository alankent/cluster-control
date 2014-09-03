<?php

header("Content-Type: text/plain");
header("X-Test: PHP Invoked");

$jsonFile = "dbservers.json";
echo "($jsonFile) ";

if (!file_exists($jsonFile)) {

    echo "File does not exist!\n";

} else {

    $json = file_get_contents($jsonFile);
    #echo "$jsonFile: $json\n\n";

    $dbservers = json_decode($json, true);
    foreach ($dbservers as $svr) {
        echo "$svr=";
        try {
            $content = file_get_contents("http://".$svr."/");
            if (strlen($content) > 0) {
                echo $content;
            } else {
                echo "(no response)";
            }
        }
        catch (\Exception $ex) {
            echo "[$ex]";
        }
        echo " ";
    }

}

echo "\n";
