#/bin/sh

if [ "$#" != 3 ]; then
    cat <<EOF
usage: $0 {etcd-url} {public-host} {public-port}
  - etcd-url is typically http://172.17.42.1:4001/ 
  - public-host is the IP allocated to the host (e.g. run 'hostname' and use that)
  - public-port is the port (you might start up multiple on the one host,
    so give them each a unique port number)
EOF
    exit 1
fi

set -x

CONTAINER=$(docker run -d -e ETCD_URL=${1} -e PUBLIC_HOST_AND_PORT=${2}:${3} -p ${3}:80 cluster-control-demo-webserver)
sleep 3
docker logs $CONTAINER
# (sleep 20; docker kill $CONTAINER; docker rm $CONTAINER) &
