# SNACBox
Note: Repository had to be rebuilt on 31-07-25<br><br>
Simple Network Access Control Box (SNACBox) is a home router designed to provide access to child safe websites, protect devices and restrict access to the internet based on the users devices. The hope is that SNACBox can  help parents and guardians creating as child safe internet experiance.

![Device Access](screenshots/SNACBox-DeviceAccess-ScreenShot.png)

![ADGuardHome Dashboard](screenshots/SNACBox-ADGuardHome-ScreenShot.png)

# Open Source Components
<h3>OpenWRT</h3> - https://openwrt.org/<br>
    The OpenWrt Project is a Linux operating system targeting embedded devices to create an router. <br>
<h3>AdguardHome</h3> - https://github.com/AdguardTeam/AdGuardHome<br>
    Free and open source, powerful network-wide ads & trackers blocking DNS server.<br>
<h3>Docker</h3> - https://www.docker.com/community/open-source/<br>
    Docker is built for developers, by developers. We enable team collaboration, efficient packaging, and scalable distribution of open-source projects, all with a focus on trust and security.<br>
<h3>e2Guardian</h3> - https://github.com/e2guardian/e2guardian<br>
    e2guardian is a content filtering proxy that can work in explicit and transparent proxy mode or as a ICAP server mode.<br>
<h3>Unbound</h3> - https://www.nlnetlabs.nl/projects/unbound/about/<br>
    Unbound is a validating, recursive, caching DNS resolver.<br>
<h3>PHP</h3> - https://www.php.net/<br>
    A popular general-purpose scripting language that is especially suited to web development. Fast, flexible and pragmatic, PHP powers everything from your blog to the most popular websites in the world.<br>

# Hardware

[Compatible Hardware](hardware.md)

# Fuctionality
![SNACBox Design](screenshots/SNACBox-Traffic.png)

# Configurating SNACBox
Please follow the steps below to setup SNACBox.<br>
<h2>Step 1</h2>
Chooses from the compatible hardware.<br>
It is also possible to complete the next steps using the Pi 4 or Pi 5 hardware<br>
Make sure the hardware is OpenWRT compatible and follow the OpenWRT Firmware Flashing guidlines.<br>
<h2>Step 2</h2>
Download and flash the SD card.<br>
Goto https://firmware-selector.openwrt.org<br>
Enter chosen hardware eg. "Raspberry Pi"<br>
Select based on the Hardware choice made e.g "Raspberry Pi 4B/400/CM4 (64bit)"<br>
Choose "Customize installed packages"<br>
Goto "Installed Packages"<br>
Add to the end of the list<br>
Raspberry Pi CM4/5

```bash
kmod-usb-net-rtl8152
```

Other hardware

```bash
 parted losetup resize2fs blkid php8 php8-cli php8-mod-curl luci-mod-rpc luci-ssl at jq adguardhome luci-base luci-theme-openwrt-2020 curl
```

iPhone Teathering

```bash
libusbmuxd libplist usbmuxd-utils
```

Goto "Script to run on first boot (uci-defaults)"

Add this resize script to resize the storage.

```bash
# Configure startup scripts
cat << "EOF" > /etc/uci-defaults/70-rootpt-resize
if [ ! -e /etc/rootpt-resize ] \
&& type parted > /dev/null \
&& lock -n /var/lock/root-resize
then
ROOT_BLK="$(readlink -f /sys/dev/block/"$(awk -e \
'$9=="/dev/root"{print $3}' /proc/self/mountinfo)")"
ROOT_DISK="/dev/$(basename "${ROOT_BLK%/*}")"
ROOT_PART="${ROOT_BLK##*[^0-9]}"
parted -f -s "${ROOT_DISK}" \
resizepart "${ROOT_PART}" 100%
mount_root done
touch /etc/rootpt-resize

if [ -e /boot/cmdline.txt ]
then 
NEW_UUID=`blkid ${ROOT_DISK}p${ROOT_PART} | sed -n 's/.*PARTUUID="\([^"]*\)".*/\1/p'`
sed -i "s/PARTUUID=[^ ]*/PARTUUID=${NEW_UUID}/" /boot/cmdline.txt
fi

reboot
fi
exit 1
EOF
cat << "EOF" > /etc/uci-defaults/80-rootfs-resize
if [ ! -e /etc/rootfs-resize ] \
&& [ -e /etc/rootpt-resize ] \
&& type losetup > /dev/null \
&& type resize2fs > /dev/null \
&& lock -n /var/lock/root-resize
then
ROOT_BLK="$(readlink -f /sys/dev/block/"$(awk -e \
'$9=="/dev/root"{print $3}' /proc/self/mountinfo)")"
ROOT_DEV="/dev/${ROOT_BLK##*/}"
LOOP_DEV="$(awk -e '$5=="/overlay"{print $9}' \
/proc/self/mountinfo)"
if [ -z "${LOOP_DEV}" ]
then
LOOP_DEV="$(losetup -f)"
losetup "${LOOP_DEV}" "${ROOT_DEV}"
fi
resize2fs -f "${LOOP_DEV}"
mount_root done
touch /etc/rootfs-resize
reboot
fi
exit 1
EOF
cat << "EOF" >> /etc/sysupgrade.conf
/etc/uci-defaults/70-rootpt-resize
/etc/uci-defaults/80-rootfs-resize
EOF
sh /etc/uci-defaults/70-rootpt-resize
```

Click "Request Build"<br>
Click and download "Factory (Ext4)"<br>
Get SD Card min 16GB<br>
Download and install Raspberry Pi imager https://www.raspberrypi.com/software/<br>
Insert SD card into computer or adapter<br>
Run Raspberry Pi Imager<br>
<img src="screenshots/RPI-Imager-01.png" alt="Raspberry Pi Imager Step 1" width="400" height="200"/><br>
Click "Select Hardware"
<img src="screenshots/RPI-Imager-02.png" alt="Raspberry Pi Imager Step 2" width="400" height="200"/><br>
Choose Hardware
<img src="screenshots/RPI-Imager-03.png" alt="Raspberry Pi Imager Step 3" width="400" height="200"/><br>
Click "Select OS"
<img src="screenshots/RPI-Imager-04.png" alt="Raspberry Pi Imager Step 4" width="400" height="200"/><br>
Choose "Custom Image"
<img src="screenshots/RPI-Imager-05.png" alt="Raspberry Pi Imager Step 5" width="400" height="200"/><br>
Click "Select Storage"
<img src="screenshots/RPI-Imager-06.png" alt="Raspberry Pi Imager Step 6" width="400" height="200"/><br>
Choose SD Card
<img src="screenshots/RPI-Imager-07.png" alt="Raspberry Pi Imager Step 7" width="400" height="200"/><br>
Click "No" to customise OS
<img src="screenshots/RPI-Imager-08.png" alt="Raspberry Pi Imager Step 8" width="400" height="200"/><br>
Click "Complete"
<img src="screenshots/RPI-Imager-09.png" alt="Raspberry Pi Imager Step 9" width="400" height="200"/><br>
Now the SD card is almost ready for use in the device<br>
Make sure you device is not connected to network or power
<h2>Setp 3</h2>
Insert SD card into device<br>
If the device has HDMI, connect it to a monitor and keyboard<br>
<h2>Step 4</h2>
Power on or Boot device<br>
Connect LAN port directly to a computer, the device will provide computer with new DHCP address<br>
Open web browser on computer and enter http://192.168.1.1 to access OpenWRT<br>
If computer does not get new DHCP address then WAN port was connected<br>
SSH to root@192.168.1.1 or use HDMI and Keyboard
Run the command 'ifconfig -a' and you should see the IP address and network ports<br>
Connect WAN port to local network using second ethernet cable<br>
Configure the ethx without IP (WAN Port) for DHCP<br>
<br>Configure OpenWRT Password

```bash
passwd
```

Enter Password = xxxxxxx<br>
Note: Enter this password later into the Web Guard - Configuration section of the WebRated App software.<br>
<br>Configure WAN for DHCP

```bash
uci set network.eth1=interface
uci set network.eth1.ifname='eth1'
uci set network.eth1.proto='dhcp'
uci commit network
/etc/init.d/network restart
```

<br>Run the command 'ifconfig -a' and you should see the IP address

```bash
ifconfig -a
```

<h2>Step 5</h2>
Stop the firewall using the command '/etc/init.d/firewall stop'

```bash
/etc/init.d/firewall stop
```

<br>Disable the firewall using the command '/etc/init.d/firewall disable'

```bash
/etc/init.d/firewall disable
```
<br>Optional:
<br>If this worked and WAN has IP address disconnect the LAN cable form the computer
<br>Now SSH on to the device using the WAN IP
<h2>Step 6</h2>
Resize the SD card partition<br>
<br>Download expand-root.sh

```bash
wget -U "" -O expand-root.sh "https://openwrt.org/_export/code/docs/guide-user/advanced/expand_root?codeblock=0"
```

<br>Source the script (creates /etc/uci-defaults/70-rootpt-resize and /etc/uci-defaults/80-rootpt-resize, and adds them to /etc/sysupgrade.conf so they will be re-run after a sysupgrade)

```bash
chmod +x expand-root.sh
./expand-root.sh
```

<br>Resize root partition and filesystem (will resize partiton, reboot resize filesystem, and reboot again)

```bash
sh /etc/uci-defaults/70-rootpt-resize
```

<br>
SSH connection will close and it can take 2 minutes for resize to happen
<br>
SSH back onto the device.<br>
Run command 'df -h' to check the disk space

```bash
df -h
```

# Install SNACBox Software
After the creation of the OpenWRT device the software necessary needs to be installed and configured to create the SNACBox.<br>
Download the contents of the software folder either as a zip file or clone the git respository to the OpenWRT device.<br>

```bash
opkg update
opkg install git git-http
git clone https://github.com/AKrawczyk/SNACBox.git
```

If its in zip format extract and goto the software folder.<br>
The software folder contains all the setup files and software needed to create a SNACBox.<br>
<br>Make script files executable

```bash
chmod +x *.sh
```

<h2>Step 1</h2>
ADGuardHome
<br>This will install and configure ADGuardHome on OpenWRT.

```bash
wget https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/luci-app-adguardhome-plugin-1.0.0-r1.apk
apk add --allow-untrusted luci-app-adguardhome-plugin-1.0.0-r1.apk
```

<br>Test ADGuardHome setup
<br>If connected to LAN use http://192.168.1.1.3080
<br>If connected to WAN use http://WAN_IP:3080
<br>User = root
<br>Password = xxxxxxxx
<br>Note: Set this password later into the AdGuard Home - Configuration section of the WebRated App software.
<br>If everthing worked there should be access to ADGuardHome web interface<br>
Do not proceed unless the ADGuardHome works.<br>
<h2>Step 2</h2>
Docker
<br>This will install, configure, get all containers and run all containers needed.
<br>NOTE: e2Guardian configuration files need to be updated as they are not work currently

```bash
./docker-setup.sh
```

<br>Test DNS Docker Container
<br>Once the setup script has been run the docker containers will need to be tested.

```bash
nslookup www.youtube.com 172.20.0.16
```

<br>All the following nslookup results should be diffrent.

```bash
nslookup www.youtube.com 172.20.0.11 
nslookup www.youtube.com 172.20.0.12
nslookup www.youtube.com 172.20.0.13
nslookup www.youtube.com 172.20.0.14
nslookup www.youtube.com 172.20.0.15
```

<br>Test Proxy Docker Container

```bash
opkg install curl
curl -x http://172.20.0.11:8080 https://www.google.com
curl -x http://172.20.0.12:8080 https://www.google.com
curl -x http://172.20.0.13:8080 https://www.google.com
curl -x http://172.20.0.14:8080 https://www.google.com
curl -x http://172.20.0.15:8080 https://www.google.com
```

Do not proceed unless the docker containers work.<br>
<h2>Step 3</h2>
Web Rated App<br>
Create OpenWRT App folders

```bash
mkdir -p /usr/lib/lua/luci/controller
mkdir -p /usr/lib/lua/luci/view
mkdir -p /www/html
grep -q ' config webguard' /etc/config/webguard 2>/dev/null || { echo ' config webguard' >> /etc/config/webguard }
```

<br>To install the Web Rated App copy web rated folders

```bash
cp -r /root/SNACBox/software/"Web Rated"/web-rated/*.* /www/html/
mv /www/html/config.js /www/luci-static/resources/view/
cp -r /root/SNACBox/software/"Web Rated"/controler/*.* /usr/lib/lua/luci/controller/
cp -r /root/SNACBox/software/"Web Rated"/view/*.* /usr/lib/lua/luci/view/
opkg update
opkg install luci-app-openvpn
opkg install php8 php8-cli php8-mod-curl luci-mod-rpc
opkg install luci-ssl at jq
uci set uhttpd.main.redirect_https=1
uci commit uhttpd
/etc/init.d/uhttpd restart
```

login to OpenWRT<br>
Goto Web Guard -> Configuration<br>
Enter root password<br>
Click 'Save and Apply'<br>
Click on Web Guard

<h2>Step 4</h2>
SNACBox theme<br>
Install SNACBox Theme

```bash
wget https://github.com/AKrawczyk/SNACBox/raw/refs/heads/main/software/OpenWRT-Packages/luci-theme-openwrt-snacbox-1.0.0-r1.apk
apk add --allow-untrusted luci-theme-openwrt-snacbox-1.0.0-r1.apk
```

<h2>Step 5</h2>
<br>Configure AdGuardHome plugin for SNACBox

Login to OpwnWRT and see SNACBox theme<br>
Goto Webguard -> AdGuardHome<br>
Select Configuration tab<br>
Enter username and password to ADGuardHome<br>
Click 'Change Password' button<br>
Click 'Save and Apply'<br>
Select Status tab<br>
Click on ADGuardHome button<br>
Login to ADGuardHome Web UI

<h2>Step 6</h2>
Deamons<br>
Setup the Dcoker maintainer deamon

```bash
cd /root/SNACBox/software/Daemon/docker-checker-daemon
chmod +x *.sh
./docker-checker-setup.sh
```

<br>Setup but dont enable the New Device Access daemon

```bash
cd /root/SNACBox/software/Daemon/new-device-access-daemon
chmod +x *.sh
./new-device-access-setup.sh
```

<br>Optional: Setup the Mobile WAN daemon

```bash
cd /root/SNACBox/software/Daemon/moibile-wan-daemon
chmod +x *.sh
./mobile-wan-setup.sh
```

<h2>Step 7</h2>
Newtwork Interfaces and Firewall configuration<br>
<br>Setup and enable network interfaces<br><br>
If connected to LAN use http://192.168.1.1<br>
User = root<br>
Password = xxxxxx<br><br>
At login a popup will appear informing it needs to do a reconfigure, Agree to this and proceed<br><br>
Click on 'Network -> Interfaces'<br>
<img src="screenshots/open-wrt-interfaces.png" alt="Open-WRT Interfaces" width="1000" height="800"/>
There are two interface that need to be enabled<br>
'DCO' and 'WAN'<br><br>
Find 'DCO'<br>
On the right hand side click on 'Edit'<br>
<img src="screenshots/open-wrt-interface-dco.png" alt="Open-WRT Interface DCO" width="900" height="300"/>
Find 'Device' Drop down<br>
Select 'br-xxxxxxxxx'<br>
Click 'Save'<br><br>
Find 'WAN'<br>
On the right hand side click on 'Edit'<br>
<img src="screenshots/open-wrt-interface-wan.png" alt="Open-WRT Interface WAN" width="900" height="300"/>
Find 'Device' Drop down<br>
Select 'eth1'<br>
Click 'Save'<br><br>
Note: This part must be done by a device connected to the LAN port<br>
Find 'eth1'<br>
On the right hand side click on 'Edit'<br>
<img src="screenshots/open-wrt-interface-eth1.png" alt="Open-WRT Interface ETH1" width="900" height="300"/>
Find 'Device' Drop down<br>
Select 'unspecified'<br>
Click 'Save'<br>
Click 'Save and Apply'<br>
On the right hand side click on 'Delete'<br>
Click 'Save and Apply'<br><br>

<br>Setup and enable firewall

```bash
cp /root/SNACBox/software/firewall/firewall /etc/config/
/etc/init.d/firewall enable
/etc/init.d/firewall start
/etc/init.d/firewall status
```

<br>Enable New Device Access daemon

```bash
/etc/init.d/new-device-access enable
/etc/init.d/new-device-access start
/etc/init.d/new-device-access status
```

<h2>Step 8</h2>
IP Blacklist<br>
Enable IP Blacklist Scheduled task<br>

```bash
cd /root/SNACBox/software/cronjob/blacklist-ip/
chmod +x *.sh
./setup-blacklist-ip.sh
```

Note: This updates a firewall rule to drop any outbound traffic to the IPs listed in the rule 
<h2>Step 9</h2>
HTTPS Configuration<br>
<br>Setup and enable HTTPS<br><br>
If connected to LAN use http://192.168.1.1<br>
If connected to WAN use http://WAN_IP<br>
User = root<br>
Password = xxxxxxx<br><br>
Click on 'System -> Administration -> HTTP(S) Access'<br>
<img src="screenshots/open-wrt-system-admin-https.png" alt="Open-WRT Interfaces" width="1000" height="250"/>
Tick 'Redirect to HTTPS'<br>
Click 'Save and Apply'
<h2>Step 10</h2>
Configure LAN IP and DHCP<br>
<br>Setup LAN IP and DHCP Range<br><br>
If connected to WAN use https://WAN_IP<br>
User = root<br>
Password = xxxxxxxx<br><br>
Click on 'Network -> Interfaces'<br>
<img src="screenshots/open-wrt-interfaces.png" alt="Open-WRT Interfaces" width="1000" height="800"/>
Note: This part must be done by a device connected to the WAN port<br>
On the right hand side click on 'Edit'<br>
<img src="screenshots/open-wrt-interface-lan.png" alt="Open-WRT Interface LAN" width="900" height="300"/>
Change 'IPv4 Addresses' to '192.168.12.1'<br><br>
Click 'DHCP Server'<br>
<img src="screenshots/open-wrt-interface-land.png" alt="Open-WRT Interface LAN" width="900" height="300"/>
Change 'Start' to '50'<br>
Change 'Limit' to '200'<br>
Change 'Lease time' to '120h'<br><br>
Click ' Advanced Settings'<br>
<img src="screenshots/open-wrt-interface-lana.png" alt="Open-WRT Interface LAN" width="900" height="300"/>
Tick 'Force'<br>
Change 'DHCP-Options' to '6,192.168.12.1'<br>
Click '+'<br>
Click 'Save'<br>
Click 'Save and Apply'

<h2>Step 11</h2>
Test SNACBox<br>
1. Login to the SNACBox web interface
2. Check 'Device Access', 'Schedule Access', 'Network -> Firewall'

<h2>Completed</h2>
At this point the SNACBox should be ready to deploy on the Home Router<br>

# Setup SNACBox on Home Network Router
Note: This is setup currently requires basic IT technical skill.<br>
<h2>Step 1</h2>
Connect SNACBox to Home Router<br>
<img src="screenshots/SNACBox-Conectivity.png" alt="SNACBox Connectivity" width="250" height="375"/>
Power on SNACBox<br>
<br>Conncet Home Router eth port to SNACBox WAN port
<br>Connect SNACBox LAN port to Laptop
<br>SSH to ssh root@192.168.12.1 or Web UI to https://192.168.12.1
<br>
<br>Skip to  'Configure Static WAN' if SNACBox was made using OpenWRT image and configured manually.
<br>Only if the SNACBox-OpenWRT-0.9.42.69.img was used.

```bash
rm -f /etc/rootpt-resize /etc/rootfs-resize
sh -x /etc/uci-defaults/70-resize-rootfs
```

This will resize the use the whole SD Card. 
<br>
<br>Configure Static WAN IP (This can be the eth1's IP or another IP in the same IP range)

```bash
ifconfig -a
uci set network.eth1=interface
uci set network.eth1.ifname='eth1'
uci set network.eth1.proto='static'
uci set network.eth1cfg.ipaddr='192.168.x.x'
uci set network.eth1cfg.netmask='255.255.255.0'
uci set network.eth1cfg.gateway='192.168.x.1'
uci set network.eth1cfg.dns='127.0.0.1'
uci commit network
/etc/init.d/network restart
```

<br>Dissconnect SNACBox LAN port from Laptop
<br>Connect to home WiFi
<br>Login to Home Router and disable DHCP
<br>Note: DNS configuration my be necessary if Home Router is set to secure DNS
<br>Connect Home Router other eth port to SNACBox LAN port
<h2>Step 2</h2>
Setup Admin devices<br>
Connect at least two wireless devices (Computer, Tablet, Smart Phone) to Home WiFi<br>
If your using a mobile device IOS, MacOS or Android make sure to configure the WiFi connection's Private Wi-Fi Address to 'Fixed'<br>

<img src="screenshots/Apple-MAC-Rand.jpeg" alt="Apple MAC Random" width="200" height="500"/><br>
<img src="screenshots/Apple-MAC-Fixed.jpeg" alt="Apple MAC Fixed" width="200" height="200"/>

Note: These devices will automaticly be enroled into the 'No Access' rating<br>
      This is done by the New Device Access daemon installed earlier<br>
      This will have no effect as the firewall rule 'lan' and recirect 'none-rated-any' are disabled<br>
1. Open Web browser and goto http://192.168.12.1<br>
2. Login to SNACBox, User = root, Password = xxxxxx
3. Now set the Rating to '18-Rated' for both admin devices<br>

![Screenshot](screenshots/SNACBox-DeviceAccess-ScreenShot.png)

4. Once the admin devices have been enabled proceed to step 3
<h2>Step 3</h2>
Enable SNACBox security<br>
Note: This can also be done via the Web UI, 'Network -> Firewall'<br>
      'Port Forwards' tick any unticked boxes<br>
      'Traffic Rules' thick any unticked boxes<br>

<br>Enable Firewall rule 'new-rated-doc' and 'lan' and forward rule 'none-rated-any'
<br>Find the firewall rulenumber [x]

```bash
uci show firewall | grep "name='new-rated-doc'"
```
Enter number [x] of firewall rule to update

```bash
uci set firewall.@rule[x].enabled='1'
```

eg. firewall.@rule[3].name='new-rated-doc'<br>

```bash
uci show firewall | grep "name='lan'"
```
Enter number [x] of firewall rule to update

```bash
uci set firewall.@rule[x].enabled='1'
```

eg. firewall.@rule[2].name='lan'<br>
<br>Find the firewall rule or redirect number [x]

```bash
uci show firewall | grep "name='none-rated-any'"
```
Enter number [x] of firewall redirect to update

```bash
uci set firewall.@redirect[x].enabled='1'
```

eg. firewall.@redirect[1].name='none-rated-any'<br>
<br>Set updated firewall entries

```bash
uci commit firewall
/etc/init.d/firewall restart
```

<h2>Step 4</h2>
Time<br>
<br>Setup Timezone<br><br>
If connected to LAN use http://192.168.12.1<br>
If connected to WAN use http://WAN_IP<br>
User = root<br>
Password = xxxxxx<br><br>
Click on 'System -> System'<br>
Find 'Timezone' dropdown list and select the timezone<br>
<br>Reapply theme

```bash
uci set luci.main.mediaurlbase='/luci-static/snacbox-theme'
uci commit luci
service uhttpd restart
```

<h2>Device Setup</h2>
Now everthing should be setup and all devices connected to the Home WiFi should be appearing in the 'Device Access' web interface. All devices will automaticly be enroled into the 'No Access' rating<br><br>
1. Enable devices on home newtork<br>
2. https://192.168.12.1 -> Web Guard -> Web Access -> Device Access<br>
3. All devices trying to access the Home WiFi will have 'No Access' rating automaticly applied to them, but will have a 'Captive Portal' webpage appear showing there IP address.<br>

<img src="screenshots/No-Access-Captive-Portal.jpeg" alt="Captive Portal" width="200" height="500"/><br>

4. Now find that device on the list of devices in 'Device Access' and assign it the necessary access rights (G, PG, 12, 15, 16 or 18).<br>
    The PDF contains detailed information regarding age rated filter configuration <a href="SNACBox%20Age%20Rated%20Filters.pdf">SNACBox Age Rated Filters.pdf</a>

![Device Access](screenshots/SNACBox-DeviceAccess-ScreenShot.png)
