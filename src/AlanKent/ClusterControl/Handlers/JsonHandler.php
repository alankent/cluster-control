<?php

namespace AlanKent\ClusterControl\Handlers;


class JsonHandler implements Handler {

    private $filename;

    function __construct($config)
    {
        $this->filename = $config;
    }

    /**
     * Write the array of members to a configuration file.
     * @param $members An array of strings being the members of the cluster.
     * @return void
     */
    public function writeConfigFile($members)
    {
        $json = json_encode($members);
        file_put_contents($this->filename, $json);
    }
}
