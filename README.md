## About

The rDNS package adds a frontend to SynergyCP that allows Clients and Administrators to
set rDNS PTR records. Currently, BIND and PowerDNS v3/v4 are supported.

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
If you want redundancy and scalability, you should use the PowerDNS v4 setup.

### PowerDNS v4 setup

**NOTE:** If you have PowerDNS already setup, you can skip to "Existing PowerDNS v4 server" below.

This command must be run as root on a fresh OS with nothing else installed. It has only been tested on Debian 9 but should work on most linux distributions. It cannot be run on the server Synergy is running on due to port conflicts. Make sure to save the details that are shown at the end of the installation in the SynergyCP Admin > System > Settings > DNS page.

```
mkdir -p /scp/dns
cd /scp/dns
wget https://install.synergycp.com/bm/packages/dns-http-control-powerdns-v4.tgz -O - | tar -zxvf -
./bin/install.sh
```

#### Existing PowerDNS v4 server

**NOTE:** You can skip this section if you used the above install command as it does this for you.

If you already have PowerDNS installed and want to add support to manage it from SynergyCP, first you must make sure that the PowerDNS version running is PowerDNS v4 with MySQL backend. Then you must enable API access if you haven't yet: open up your PowerDNS config (typically `/etc/powerdns/pdns.conf`) as root and add the following config variables:

```
# Required for SynergyCP Integration:
webserver=yes
webserver-port=80
webserver-address=0.0.0.0
webserver-allow-from=0.0.0.0/0
webserver-password=<generate a very strong password>
api=yes
api-key=<generate a very strong password>
```

Allow port 80 through the firewall if one is setup.
Save the `api-key` password for later.

Finally, restart the PowerDNS service so that your changes take effect (must be run as root):

```
service pdns restart
```

Then, add the PowerDNS server to SynergyCP using the details added to the config file.

### PowerDNS v3 setup

**WARNING:** PowerDNS v3 was deprecated by PowerDNS. v4 is the new recommended version, so we do not recommend using v3.
**NOTE:** If you have PowerDNS already setup, you can skip to "Existing PowerDNS v3 server" below.

This command must be run as root on a fresh _Debian 8_ (Debian 9 does not have the right PowerDNS installer, currently) server with nothing else installed.
It cannot be run on the server Synergy is running on due to port conflicts.
Save the details that are shown at the end of the installation.

```
mkdir -p /scp/dns
cd /scp/dns
wget https://install.synergycp.com/bm/packages/dns-http-control-powerdns.tgz -O - | tar -zxvf -
./bin/install.sh
```

#### Existing PowerDNS v3 server

**NOTE:** You can skip this section if you used the above install command as it does this for you.

If you already have PowerDNS installed and want to add support to manage it from SynergyCP, first you must make sure that the PowerDNS version running is PowerDNS v3.4 with MySQL backend. Open up `/etc/powerdns/pdns.conf` as root and add the following config variables:

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

Finally, restart the PowerDNS service so that your changes take effect (must be run as root):

```
service pdns restart
```

Then, add the PowerDNS server to SynergyCP using the details added to the config file. Note that you must use the following syntax for the hostname: `your.hostname:8081` for SynergyCP to know the port that PowerDNS is configured to use.

### Bind setup

**NOTE:** You can skip this section if you have setup PowerDNS through one of the above methods

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

1. Install the rDNS package on SynergyCP.
2. Go into the SynergyCP Admin > Settings > DNS and then enter the settings displayed at the end of the DNS Server install.
3. Add an rDNS PTR from the dashboard of SynergyCP and make sure it works:

   ```
   TEST_IP=10.0.0.1
   DNS_SERVER=1.1.1.1

   dig +noall +answer -x $TEST_IP @$DNS_SERVER
   ```

4. If applicable, import your rDNS zone files from your previous DNS server on the
   Network > rDNS PTRs page of SynergyCP.
5. Configure the DNS Server's IP as the nameserver for your IP address announcements.

### Does it support IPv6?

Yes! Although currently, the BIND integration does not support IPv6.
