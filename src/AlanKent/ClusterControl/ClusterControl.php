<?php

namespace AlanKent\ClusterControl;

use AlanKent\ClusterControl\Handlers\Handler;
use LinkORB\Component\Etcd\Client as EtcdClient;
use LinkORB\Component\Etcd\Exception\KeyNotFoundException;

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
    public function __construct($configFilename)
    {
        $contents = file_get_contents($configFilename);
        $config = json_decode(utf8_encode($contents), true);

        $this->selfKey = $config['self']['key'];
        $this->ttl = $config['self']['ttl'];
        $this->heartbeatInterval = $config['self']['heartbeat'];

        $server = $config['etcd']['server'];

        $this->clusterPaths = array();
        $this->clusterHandlers = array();

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
     * @param string $data The value to set the key to.
     */
    public function setKey($data)
    {
        // The value for the key is empty at present.
        $this->etcd->set($this->selfKey, $data, $this->ttl);
    }

    /**
     * Update the current container's key in etcd with a TTL value as specified
     * by the configuration file. If the key does not exist this will fail.
     * @param string $data The value to set the key to.
     * @return bool Returns true if key was updated, false if key no longer exists.
     */
    public function updateKey($data)
    {
        // The value for the key is empty at present.
        $resp = $this->etcd->set($this->selfKey, $data, $this->ttl, ['prevExist' => 'true']);
        return !isset($resp['errorCode']);
    }

    /**
     * Watch until the key is removed, then return.
     */
    public function watchKey()
    {
        // Get the value, but waiting until some change.
        // If 'value' is not set, then key has been removed.
        do {
            $node = $this->etcd->getNode($this->selfKey, ['wait' => 'true']);
        } while (isset($node['value']));
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
    public function readClusterMembers($cluster, $waitIndex)
    {
        $options = ['recursive' => 'true'];
        if ($waitIndex != null) {
            $options['wait'] = 'true';
            $options['waitIndex'] = $waitIndex;
        }
        $node = $this->etcd->getNode($this->clusterPaths[$cluster], $options);
echo "NODE: ";
var_dump($node);

$index = $node['modifiedIndex'];
        $members = array();
        foreach ($node['nodes'] as $dir) {
            $path = $dir['key'];
            $key = substr(strrchr($path, '/'), 1);
            $members[] = $key;

 if ($dir['modifiedIndex'] > $index) {
$index = $dir['modifiedIndex'];
}
        }

echo "index = $index\n";
        return ['index' => $index, 'members' => $members];
    }

    /**
     * Look up the list of current cluster members, write that out to a config file,
     * the return the wait index for cc:clusterwatch to start from.
     * @param string $cluster The cluster to process.
     * @param array $clusterMembers The members that are a part of the cluster.
     * @return void
     */
    public function writeClusterConfig($cluster, $clusterMembers)
    {
        $this->clusterHandlers[$cluster]->writeConfigFile($clusterMembers);
    }
}
