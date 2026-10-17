# SNACBox
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
Goto the [Dev Build](https://github.com/AKrawczyk/SNACBox/blob/main/software/OpenWRT-Packages/webguard/readme.md).<br>

# Setup SNACBox on Home Network Router
Note: This is setup currently requires simple IT skills.<br>
<h2>Step 1</h2>
Connect SNACBox WAN port to Home Router<br>
<img src="screenshots/SNACBox-Conectivity.png" alt="SNACBox Connectivity" width="250" height="375"/>
1. Power on SNACBox
<br>2. Conncet Home Router Network port to SNACBox WAN port
<br>3. Connect SNACBox LAN port to USB Network Dongle
<br>4. Connect USB Network Dongle to Laptop/Tablet/Smart Phone
<br>5. Browse to https://192.168.12.1
<br>6. Login to SNACBox, User = root, Password = xxxxxx
<br>
<h3>Configure Static WAN IP</h3>
Goto Webguard -> Web Access
<br>Select 'Configure'
<br>Click button 'Reset WAN'
<br>
<h3>Setup Admin USB Network Dongle</h3>
Goto Webguard -> Web Access
<br>Select 'Device Access'
<br>Now set the Rating to '18-Rated' for the admin usb network dongle<br>

![Screenshot](screenshots/SNACBox-DeviceAccess-ScreenShot.png)

<h3>Enable SNACBox on Home Router</h3>
1. Disconnect USB Network Dongle from Laptop/Tablet/Smart Phone
<br>2. Disconnect SNACBox LAN port into USB Network Dongle
<br>
<br><strong>3. Login to Home Router and disable DHCP</strong>
<br>Note: Consult Home Router suppliers web page for information on how to do this.
<br>Note: DNS configuration my be necessary if Home Router is set to secure DNS
<br>
<br>4. Connect SNACBox LAN port to Home Router Network port
<h2>Step 2</h2>
Setup Admin devices<br>
Connect at least two wireless devices (Computer, Tablet, Smart Phone) to Home WiFi<br>
If your using a mobile device IOS, MacOS or Android make sure to configure the WiFi connection's Private Wi-Fi Address to 'Fixed'<br>

<img src="screenshots/Apple-MAC-Rand.jpeg" alt="Apple MAC Random" width="200" height="500"/><br>
<img src="screenshots/Apple-MAC-Fixed.jpeg" alt="Apple MAC Fixed" width="200" height="200"/>

Note: These devices will automaticly be enroled into the 'No Access' rating<br>
<br>
1. Open Web browser and goto http://192.168.12.1<br>
2. Login to SNACBox, User = root, Password = xxxxxx
3. Now set the Rating to '18-Rated' for both admin devices<br>

![Screenshot](screenshots/SNACBox-DeviceAccess-ScreenShot.png)

4. Once the admin devices have been enabled proceed to step 3 to enable security and set time.
<h2>Step 3</h2>
<h3>Enable SNACBox security</h3>
Goto Webguard -> Web Access<br>
select 'Configure'<br>
Thick 'Enable Enhanced Security'<br>
<h3>Set Time</h3>
Click on 'System -> System'<br>
Find 'Timezone' dropdown list<br>
Select the correct timezone<br>

<h2>Device Setup</h2>
Now everthing should be setup and all devices connected to the Home WiFi should be appearing in the 'Device Access' web interface. All devices will automaticly be enroled into the 'No Access' rating<br><br>
1. Enable devices on home newtork<br>
2. https://192.168.12.1 -> Web Guard -> Web Access -> Device Access<br>
3. All devices trying to access the Home WiFi will have 'No Access' rating automaticly applied to them, but will have a 'Captive Portal' webpage appear showing there IP address.<br>

<img src="screenshots/No-Access-Captive-Portal.jpeg" alt="Captive Portal" width="200" height="500"/><br>

4. Now find that device on the list of devices in 'Device Access' and assign it the necessary access rights (G, PG, 12, 15, 16 or 18).<br>
    The PDF contains detailed information regarding age rated filter configuration <a href="SNACBox%20Age%20Rated%20Filters.pdf">SNACBox Age Rated Filters.pdf</a>

![Device Access](screenshots/SNACBox-DeviceAccess-ScreenShot.png)
