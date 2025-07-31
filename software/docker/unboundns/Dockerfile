FROM ubuntu:22.04
CMD ["bash"]

# Copies over the running config file to start e2guardian and unbound
# Makes the script executable
COPY run.sh /
COPY inotify.sh /
RUN chmod +x /run.sh
RUN chmod +x /inotify.sh

#Create a temp resolv.conf file then copies it
RUN echo "nameserver 127.0.0.1" > /tmp/resolv.conf
RUN cp /tmp/resolv.conf /etc/resolv.conf

RUN apt update
RUN apt install inotify-tools unbound -y

COPY unbound.conf /etc/unbound/

RUN service unbound start

ENTRYPOINT ["/run.sh"]
