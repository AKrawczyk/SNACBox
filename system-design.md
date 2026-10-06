# Design
![SNACBox Design](screenshots/SNACBox-Traffic.png)
![SNACBox Design](screenshots/Canvas1.jpg)
<h2>How it works</h2>
When a device connects to the SNACBox OpenWRT it will get a DHCP address. Once the client device resgisters with the SNACBox it can then be configured into one of 6 catorgies or ratings (G, PG, 12, 15, 16 ,18). When a client device want to connect to the internet it must first be porcessed by the SNACBox rules.<br><br>
In this example we will use the G rating as example.<br>
The G device send a request to access a website. OpenWRT Firewall will forward the requests (80 HTTP, 443 HTTPS, 53 DNS) to a Docker container.<br>
The G docker container runs 2 services, E2Guardian Proxy and Unbound DNS.<br>
The G docker container processes the DNS requests and fowards them to ADGuardHome for processing.<br>
ADGuardHome will have a set of filter rules (Child safe websites, Specific restricted services and Safe Search) setup for the G docker container.<br>
ADGuardHome processes the DNS request, based on the filter rules, if the request is fowards to the internet ADGuardHome using DOH (DNS over HTTPS).<br>
If ADGuardHome DNS returns the address to the site requested then the G docker container will permit the HTTP/HTTPS request access the internet.<br>
If ADGuardHome DNS does not return the address to the site requested then the G docker container will close the request and the HTTP/HTTPS will go nowhere.<br>
<h3>Note:</h3> The reason for the E2Guardian Proxy is to provent DNS bypass by using DOH on the client device.
