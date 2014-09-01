<?php

namespace AlanKent\ClusterControl;

use AlanKent\ClusterControl\Handlers\Handler;
use LinkORB\Component\Etcd\Client as EtcdClient;

class ClusterControl
{
    /**
     * @var EtcdClient The connection to the etcd server.
     */
    private $etcd;

    /**
     * @var string The key this container uses to identify itself.
     */
    private $selfKey;

    /**
     * @var int Time to live (in seconds, same as used by etcd).
     */
    private $ttl;

    /**
     * @var int Period of time between updating the key in etcd.
     * This should be smaller than TTL to make sure we reset the key before
     * it times out.
     */
    private $heartbeatInterval;

    /**
     * @var array Array of etcd paths indexed by cluster name.
     */
    private $clusterPaths;

    /**
     * @var array Array of handlers for writing the configuration file for
     * the the cluster members.
     */
    private $clusterHandlers;

    /**
     * Default filename for configuration file.
     */
    const DEFAULT_CONFIG_FILE = 'cluster-control.conf';

    /**
     * Constructor.
     * @param string $configFilename The configuration file to read from.
     */
    public function __construct(string $configFilename)
    {
        $contents = file_get_contents($configFilename);
        $config = json_decode(utf8_encode($contents), true);

        $this->selfKey = $config['self']['key'];
        $this->ttl = $config['self']['ttl'];
        $this->heartbeatInterval = $config['self']['heatbeat'];

        $server = $config['etcd']['server'];

        $this->clusters = array();
        foreach ($config['clusters'] as $cluster) {
            $this->clusterPaths[$cluster['name']] = $cluster['path'];
            $handlerName = $cluster['handler'];
            $handlerConfig = $cluster['handlerConfig'];
            $handler = new $handlerName($handlerConfig);
            if (!($handler instanceof Handler)) {
                throw new \Exception("Specified handler '$handlerName' does not implement the Handler interface.");
            }
            $this->clusterHandlers[$cluster['name']] = $handler;
        }

        $this->etcd = new EtcdClient($server);
    }

    /**
     * Set the current container's key in etcd with a TTL value as specified
     * by the configuration file.
     */
    public function setKey()
    {
        // The value for the key is empty at present.
        $this->etcd->set($this->selfKey, '', $this->ttl);
    }

    /**
     * Update the current container's key in etcd with a TTL value as specified
     * by the configuration file. If the key does not exist this will fail.
     * @return bool Returns true if key was updated, false if key no longer exists.
     */
    public function updateKey()
    {
        try {
            // The value for the key is empty at present.
            $this->etcd->set($this->selfKey, '', $this->ttl, 'prevExist=true');
        }
        catch (KeyNotFoundException $e) {
            // Exit cleanly with 'false' to indicate key no longer exists.
            // This indicates an external request to shutdown.
            return false;
        }
        return true;
    }

    /**
     * Delete the container's key in etcd. This is done when the container
     * is about to exit.
     */
    public function removeKey()
    {
        $this->etcd->rm($this->selfKey);
    }

    /**
     * How long to wait between setting container's key as a heartbeat.
     * @return int Heartbeat interval in seconds.
     */
    public function heartbeatInterval()
    {
        return $this->heartbeatInterval;
    }

    /**
     * @param string $cluster
     * @param $waitIndex
     * @return array Returns array with 'index' holding an integer and 'members' holding
     * an array of strings, or null if failed to read directory.
     */
    public function readClusterMembers(string $cluster, $waitIndex)
    {
        $url = $this->clusterPaths[$cluster];
        if ($waitIndex != null) {
            $url .= '?wait=true&waitIndex=' . $waitIndex;
        }
        $body = $this->etcd->doRequest($url);
        var_dump($body);
        return array();
    }

    /**
     * Look up the list of current cluster members, write that out to a config file,
     * the return the wait index for cc:clusterwatch to start from.
     * @param string $cluster The cluster to process.
     * @return void
     */
    public function writeClusterConfig(string $cluster, array $clusterMembers)
    {
        $this->clusterHandlers[$cluster]->writeConfigFile($clusterMembers);
    }
}


//$client = new EtcdClient($server);
/*
$query = array('wait' => 'true');

if ($recursive) {
    $query['recursive'] = 'true';
}

if ($afterIndex) {
    $query['waitIndex'] = $afterIndex;
}

$data = $client->get($key, $query);
$output->writeln($data);
*/
