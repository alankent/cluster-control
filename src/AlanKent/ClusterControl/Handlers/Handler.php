<?php
/**
 * Handlers deal with the specifics of each type of configuration file that needs to be written out.
 * Normally there would be a template file that the array of cluster members would be added to
 */

namespace AlanKent\ClusterControl\Handlers;


interface Handler {

    /**
     * Write the array of members to a configuration file.
     * @param $members An array of strings being the members of the cluster.
     * @return void
     */
    public function writeConfigFile($members);

} 