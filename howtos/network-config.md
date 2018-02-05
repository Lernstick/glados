## Network configuration

There are 2 possible ways you can configure your setup. You can either configure the network, that the client can autodiscover the exam server, or configure the clients to access a fixed IP-address. The client setup can be found [here](client-config.md).

### Autodiscovery of the exam server

For this to work, you need to be able to discover the exam server via [Bonjour](https://developer.apple.com/library/content/documentation/Cocoa/Conceptual/NetServices/Introduction.html). Therefore DNS Service Discovery (DNS-SD) must be allowed in the network. It is necessary to strictly allow this in your network. If you use a wireless network as exam network, notice that most accesspoints will disable this by default. If your server sits in another subnet, you have to route the needed ports, according to the Bonjour standard. Bonjour clients will talk via UDP port `5353` and multicast IP-address `224.0.0.251` (IPv4), `ff02::fb` (IPv6) respectively.

Avahi includes several utilities which help you discover the services running on a network. For example, run

    avahi-browse -r --no-db-lookup _http._tcp

to discover services in your network. If your network is configured appropriately, you should discover the exam server.

### Exam server with fixed IP-address

This is a more secure setup. To run the exam server in a network, you have to make sure that clients can connect to the server. On the other hand, the server needs to be able to connect to the clients.

This is a list of services and ports that must be allowed:

Connection        | Service     | Port
----------------- | ----------- | -----
Client to Server  | http/https  | `80` for http, `443` for https (by default)
Server to Client  | ssh         | `22` (by default)

<br>
If your exam server sits in another subnet than your exam clients, make sure to disable [NAT](https://en.wikipedia.org/wiki/Network_address_translation) in the routing configuration. This is necessary because the server determines the clients IP-address (which is then used in remote backup) via the HTTP header. With NAT enabled the header would hold the IP-address of the router instead.