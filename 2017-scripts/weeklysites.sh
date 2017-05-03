#!/bin/bash
# /etc/cron.weekly/weeklysites.sh

webdir="/var/www/vhosts/rusenbu.com/archive-db/web/"
for file in "$webdir"*savename*.*; do
    # [ -e "$file" ] && echo "file exists" || echo "not exist"
    if [[ -f $file ]]; then
       #echo $file
       rm $file
       #break
    fi
done

#savename
savenamedir="/var/www/vhosts/oursite.com/httpdocs/"
tar -zcvf ${webdir}savename-$(date +%y%m%d).tar.gz ${savenamedir} --exclude download --exclude googlecheckout --exclude sws --exclude tmp --exclude blog --exclude images --exclude cache



