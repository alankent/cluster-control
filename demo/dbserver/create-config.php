<?php

$template = <<<EOF
{
    "etcd": {
        "server": "ETCD_URL"
    },
    "self": {
        "key": "/clusterdemo/dbserver/PUBLIC_HOST_AND_PORT",
        "ttl": 10,
        "heartbeat": 5
    },
    "clusters": [
    ]
}
EOF;

$s = $template;
$s = str_replace('ETCD_URL', getenv('ETCD_URL'), $s);
$s = str_replace('PUBLIC_HOST_AND_PORT', urlencode(getenv('PUBLIC_HOST_AND_PORT')), $s);

file_put_contents('cluster-control.conf', $s);
