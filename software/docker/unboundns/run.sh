#!/bin/bash
echo "nameserver 127.0.0.1" > /tmp/resolv.conf
cp /tmp/resolv.conf /etc/resolv.conf

service unbound restart &

/inotify.sh
