#!/bin/bash
LISTS="/etc/unbound"
horodate=$(date +%d-%m-%Y_%H_%M)
if [ -d $LISTS ]
then
   while inotifywait -e modify,delete,create -r $LISTS --exclude \.swp
   do 
   # wait all modifications
     sleep 10
     horodate=$(date +%d-%m-%Y_%H_%M)
     service unbound restart &
     echo "RELOAD E2 $horodate: new files" >> /var/log/unbound-reloade2.log
     sleep 60
   done
fi

