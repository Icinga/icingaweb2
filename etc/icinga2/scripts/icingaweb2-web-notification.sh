#!/bin/sh

sse="http://localhost/icingaweb2/sse.php"
message="Service $SERVICEDESC on host $HOSTALIAS is $SERVICESTATE!"

curl -G --data "msg=$message&role=users" http://127.0.0.1/icingaweb2/sse.php 

