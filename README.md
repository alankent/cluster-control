# Cluster Control

This is a set of PHP scripts to help managing a cluster of Docker nodes using
etcd as a service registry. It uses etcd to track what servers are currently
up and down, with a hook for updating a configuration file when the list of
servers changes.

For example, consider a load balancer in front of a set of web servers. When
a new web server starts up, it should be added to the load balancer
configuration file. For the web server, it may have multiple Redis and database
servers it talks to - again, if any new server arrives or goes away, the
local configuration file should be updated to reflect the new list of available
servers.

One extra feature (not sure if a good idea or not yet) is a script can run
watching the server's own entry. If it disappears, the current container exits.
This is assumed to be an administrator removing the entry by hand (probably
because something has gone wrong). So it is better to exit and restart cleanly.


## etcd Directory Structure

These scripts assume a particular key/value structure is used in etcd. In
particular, each cluster has its own directory (such as /cluseter/webapp or
/cluster/redis) under which each container has a key of the IP address of the
server. (Hostnames would work as well, but are discouraged to avoid host name
lookup problems across container boundaries.) Doing an 'ls' on the directory
will therefore return all the IP addresses that should be present in the
local configuraiton file to refer to that service.

This means the script can watch the directory for changes, then use the list
of key names in the directory to create the new configuration file.

## Configuration

The script is driven from a JSON configuration file.

    {
        "etcd": {
            "host": "127.0.0.1",
            "port": 3001
        },
        "self": {
            "key": "/clusters/webapp/myip",
            "ttl": 60,
            "heartbeat": 30
        },
        "clusters": [
            {
                "name": "redis",
                "path": "/clusters/redis",
                "handler": "AlanKent\\ClusterControl\\Handlers\\RedisHandler",
                "handlerConfig": "filename.conf"
            },
            {
                "name": "mysql",
                "path": "/clusters/mysql",
                "handler:" "AlanKent\\ClusterControl\\Handlers\\MySQLHandler",
                "handlerConfig": "filename.conf"
            }
        ]
    }

The "etcd" section contains connection details to the etcd server.

The "self" section contains the key path for the current container. One script
is provided to set this key periodically (according to "heartbeat") with a TTL
so if the container exits, the key value will automatically go away. Another
script is provided to create this key on startup and remove the key on shut down
to avoid the TTL delay.

The "clusters" section lists clusters to be watched, with a different handler
to process changes in each cluster. E.g. if a new database server is available,
the file containing the list of database servers would be rewritten and the
appropriate server informed of the change (e.g. by sending a signal to the
relevant process).

## Sample Startup

The following shows an example start up shell script for an apache server that
connects to a cluster of Redis servers and a cluster of MySQL instances. (This
is illustrative only.)

    #!/bin/sh

    # Refresh cluster member configuration files before we start web server.
    REDIS_INDEX=$(bin/clustercontrol cc:clusterprepare --cluster redis)
    MYSQL_INDEX=$(bin/clustercontrol cc:clusterprepare --cluster mysql)

    # Sleep a bit to give apache a chance to start up before we tell it to restart.
    (sleep 10 ; bin/clustercontrol cc:clusterwatch --cluster redis --index $REDIS_INDEX apachectl graceful) &
    (sleep 10 ; bin/clustercontrol cc:clusterwatch --cluster mysql --index $MYSQL_INDEX apachectl graceful) &

    # Start the heartbeat generator. Returns when fails to update key (e.g. if deleted
    # to make server shut down), so when it returns may as well ask apache to shut down.
    (sleep 5 ; bin/clustercontrol cc:heartbeat ; apachectl graceful-stop) &

    # Watch for the key disappearing, and exit immediately (don't wait for heartbeat)
    (sleep 5 ; bin/clustercontrol cc:watchkey ; apachectl graceful-stop) &

    # Start up web server, container will exit when this exits.
    apache -D FOREGROUND

    # Double check key has been removed (it will timeout anyway due to TTL)
    bin/clustercontrol cc:removekey
