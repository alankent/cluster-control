# Mock Web Server

This server will be called /cluserdemo/webserver/{host-and-port} in etcd.

The web server has a single index.php page which fires a simple HTTP request
at every listed database server in a JSON file updated by the scripts. (Real
web servers would of course only ask a request of one of the database servers.)
The results of each database server are displayed on the page.

