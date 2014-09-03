# Mock Load Balancer

This server will be call /cluserdemo/loadbalancer/{host-and-port} in etcd.

The load balancer has a single index.php page which fires a simple HTTP request
at every listed web server in a JSON file updated by the scripts. (Real
load balancers would of course only ask a request of one of the web servers.)
The results of each web server are displayed on the page.

As web server or database server instances are added to the cluster, this global
page should automatically refresh. All web servers should report the same list
of database servers or else there is a problem. All added web and database servers
should be added and removed at the right time or else there is a problem.
