#Install Docker
opkg update
opkg install docker dockerd
/etc/init.d/dockerd start
/etc/init.d/dockerd enable  # Auto-start on boot

# Create docker volumes for storage of config files
docker volume create etc-e2g-g
docker volume create etc-e2g-pg
docker volume create etc-e2g-12
docker volume create etc-e2g-15
docker volume create etc-e2g-16
docker volume create etc-unb-g
docker volume create etc-unb-pg
docker volume create etc-unb-12
docker volume create etc-unb-15
docker volume create etc-unb-16
docker volume create etc-unb-18
# Create docker volumes for storage of logs files
docker volume create log-e2g-g
docker volume create log-e2g-pg
docker volume create log-e2g-12
docker volume create log-e2g-15
docker volume create log-e2g-16

# Get latest docker containers
docker pull fredbcode/e2guardian:v5.5-arm
#Create webbound docker container
cd docker/no-access-alpine
docker build -t webbound:v1.0 .
#Create unboundns docker container
cd ../../docker/unbound
docker build -t unboundns:v1.0 .
cd ../..

#Create new Docker network
docker network create --driver=bridge --subnet=172.20.0.0/16 --gateway=172.20.0.1 doc1

# Start docker containersi
#web-rated-g-e2gns
docker run -d --name="web-rated-g-e2gns" -v etc-unb-g:/etc/unbound -v log-e2g-g:/var/log --net doc1 --ip 172.20.0.11 --restart=unless-stopped unboundns:v1.0
docker run -d --name="web-rated-g-e2g" -v etc-e2g-g:/etc/e2guardian -v log-e2g-g:/var/log --net "container:web-rated-g-e2gns" --restart=unless-stopped fredbcode/e2guardian:v5.5-arm
docker exec web-rated-g-e2gns cp /tmp/resolv.conf /etc/resolv.conf
#web-rated-pg-e2gns
docker run -d --name="web-rated-pg-e2gns" -v etc-unb-pg:/etc/unbound -v log-e2g-pg:/var/log --net doc1 --ip 172.20.0.12 --restart=unless-stopped unboundns:v1.0
docker run -d --name="web-rated-pg-e2g" -v etc-e2g-pg:/etc/e2guardian -v log-e2g-pg:/var/log --net "container:web-rated-pg-e2gns" --restart=unless-stopped fredbcode/e2guardian:v5.5-arm
docker exec web-rated-pg-e2gns cp /tmp/resolv.conf /etc/resolv.conf
#web-rated-12-e2gns
docker run -d --name="web-rated-12-e2gns" -v etc-unb-12:/etc/unbound -v log-e2g-12:/var/log --net doc1 --ip 172.20.0.13 --restart=unless-stopped unboundns:v1.0
docker run -d --name="web-rated-12-e2g" -v etc-e2g-12:/etc/e2guardian -v log-e2g-12:/var/log --net "container:web-rated-12-e2gns" --restart=unless-stopped fredbcode/e2guardian:v5.5-arm
docker exec web-rated-12-e2gns cp /tmp/resolv.conf /etc/resolv.conf
#web-rated-15-e2gns
docker run -d --name="web-rated-15-e2gns" -v etc-unb-15:/etc/unbound -v log-e2g-15:/var/log --net doc1 --ip 172.20.0.14 --restart=unless-stopped unboundns:v1.0
docker run -d --name="web-rated-15-e2g" -v etc-e2g-15:/etc/e2guardian -v log-e2g-15:/var/log --net "container:web-rated-15-e2gns" --restart=unless-stopped fredbcode/e2guardian:v5.5-arm
docker exec web-rated-15-e2gns cp /tmp/resolv.conf /etc/resolv.conf
#web-rated-16-e2gns
docker run -d --name="web-rated-16-e2gns" -v etc-unb-16:/etc/unbound -v log-e2g-16:/var/log --net doc1 --ip 172.20.0.15 --restart=unless-stopped unboundns:v1.0
docker run -d --name="web-rated-16-e2g" -v etc-e2g-16:/etc/e2guardian -v log-e2g-16:/var/log --net "container:web-rated-16-e2gns" --restart=unless-stopped fredbcode/e2guardian:v5.5-arm
docker exec web-rated-16-e2gns cp /tmp/resolv.conf /etc/resolv.conf
#web-rated-18-unb
docker run -d --name="web-rated-18-unb" -v etc-unb-18:/etc/unbound --net doc1 --ip 172.20.0.16 --restart=unless-stopped unboundns:v1.0
docker exec web-rated-18-unb cp /tmp/resolv.conf /etc/resolv.conf
#web-rated-none
docker run -d --name="web-rated-none" --net doc1  --ip 172.20.0.101 --restart=unless-stopped webbound:v1.0

# Copy config files
cp -r docker/etc-e2g-g/_data/* /opt/docker/volumes/etc-e2g-g/_data/
cp -r docker/etc-e2g-pg/_data/* /opt/docker/volumes/etc-e2g-pg/_data/
cp -r docker/etc-e2g-12/_data/* /opt/docker/volumes/etc-e2g-12/_data/
cp -r docker/etc-e2g-15/_data/* /opt/docker/volumes/etc-e2g-15/_data/
cp -r docker/etc-e2g-16/_data/* /opt/docker/volumes/etc-e2g-16/_data/
cp -r docker/etc-e2g-18-vpn/_data/* /opt/docker/volumes/etc-e2g-18-vpn/_data/

# Restart all the docker containers
/etc/init.d/dockerd restart
