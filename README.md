## About

The rDNS package adds a frontend to SynergyCP that allows Clients and Administrators to 
set rDNS PTR records. Currently, [BIND](#bind-setup) 
and [PowerDNS](#powerdns-v3-setup) are supported. 

An rDNS PTR is a single DNS record pointing a unique IP address to a hostname. 
When someone uses a server to send out emails, the receiving server usually does 
a [reverse DNS lookup](https://remote.12dt.com/) on the IP address which helps 
determine whether or not the host is a spammer. You can host a DNS server that 
answers with the hostname of the IP address that the receiving server looked up. 
The purpose of this package is to allow Administrators and Clients to easily 
configure that DNS server with rDNS PTRs. Clients can only configure rDNS PTRs 
for IP Entities that are in use on one of their servers.

## Setting up rDNS

The rDNS package supports multiple different DNS servers. 
If you want simplicity, you should use the BIND setup.
If you want redundancy and scalability, you should use the PowerDNS setup.

### PowerDNS v3 setup

First, install PowerDNS v3 on a server if you do not already have it installed. 
This guide assumes usage of PowerDNS 3.4.1 which is currently the default when using `apt-get install pdns-server`. 
PowerDNS cannot be installed on the same server as any Synergy server without a multi-IP configuration due to port conflicts.

Open up `/etc/powerdns/pdns.conf` as root and add the following config variables:

```
# Required for SynergyCP Integration:
webserver=yes
webserver-port=8081
webserver-address=0.0.0.0
webserver-password=<generate a very strong password>
experimental-json-interface=yes
experimental-api-key=<generate a very strong password>
```

Allow port 8081 through the firewall if one is setup.
Save the `experimental-api-key` password for later.


### Bind setup

This must be run as root on a fresh Debian 8 server with nothing else installed. 
It cannot be run on the server Synergy is running on due to port conflicts. 
Save the details that are shown at the end of the installation.

```
mkdir -p /scp/dns
cd /scp/dns 
wget https://install.synergycp.com/bm/packages/dns-http-control-bind.tgz -O - | tar -zxvf -
./bin/install.sh
```

## Setting up the Package on SynergyCP
1. Install the rDNS package on SynergyCP. As a sudo user (or run as root user and remove sudo):

    ```
    sudo /scp/bin/scp-package rdns
    ```

2. Go into the SynergyCP application and add the rDNS setting values shown at the end of the DNS Server install.
   For PowerDNS, the API Host would be `<ip of PowerDNS Server>:8081` and the API Key would be the 
   `experimental-api-key` you generated. 
3. Add an rDNS PTR from the dashboard of SynergyCP and make sure it works:

    ```
    TEST_IP=10.0.0.1
    DNS_SERVER=1.1.1.1
    
    dig +noall +answer -x $TEST_IP @$DNS_SERVER
    ```

4. If applicable, import your rDNS zone files from your previous DNS server on the 
   Network > rDNS PTRs page of SynergyCP.
5. Configure the DNS Server's IP as the nameserver for your IP address announcements.
