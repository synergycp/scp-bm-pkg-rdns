## About

The rDNS package adds a frontend to SynergyCP that allows Clients and Administrators to set rDNS PTR records. It requires a separate DNS server to be setup and conform to a specific HTTP API. Currently, only [BIND](https://github.com/synergycp/bind-http-control) is supported. 

An rDNS PTR is a single DNS record pointing a unique IP address to a hostname. When someone uses a server to send out emails, the receiving server usually does a [reverse DNS lookup](https://remote.12dt.com/) on the IP address which helps determine whether or not the host is a spammer. We host a DNS server that answers with the hostname of the IP address that the receiving server looked up. The purpose of this package is to allow Administrators and Clients to easily configure that DNS server with rDNS PTRs. Clients can only configure rDNS PTRs for IP Entities that are in use on one of their servers.

## Setting up rDNS

1. Install the DNS Server. This must be run as root on a fresh Debian 8 server with nothing else installed. It cannot be run on the server Synergy is running on due to port conflicts. Save the details that are shown at the end of the installation.

```bash
mkdir -p /scp/dns
cd /scp/dns 
wget https://install.synergycp.com/bm/packages/dns-http-control-bind.tgz -O - | tar -zxvf -
./bin/install.sh
```

2. Go into the SynergyCP application and add the rDNS setting values shown at the end of the DNS Server install.
3. Add an rDNS PTR from the dashboard of SynergyCP and make sure it works:

```bash
TEST_IP=10.0.0.1
DNS_SERVER=1.1.1.1

dig +noall +answer -x $TEST_IP @$DNS_SERVER
```

5. If applicable, import your rDNS zone files from your previous DNS server on the Network > rDNS PTRs page of SynergyCP.
6. Configure the DNS Server's IP as the nameserver for your IP address announcements.
