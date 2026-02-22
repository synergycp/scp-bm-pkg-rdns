---
weight: 100
title: "RDNS"
---

# SynergyCP rDNS Package
## About

The rDNS package adds a frontend to SynergyCP that allows Clients and Administrators to set rDNS PTR records. BIND, PowerDNS v3/v4, and Cloudflare are supported.

An rDNS PTR is a single DNS record pointing a unique IP address to a hostname.
When someone uses a server to send out emails, the receiving server usually does
a [reverse DNS lookup](https://en.wikipedia.org/wiki/Reverse_DNS_lookup) on the IP address which helps determine whether or not the host is a spammer. This is called [forward-confirmed reverse DNS](https://en.wikipedia.org/wiki/Forward-confirmed_reverse_DNS).

You can host a DNS server that answers with the hostname of the IP address that the receiving server looked up. The purpose of this package is to allow Administrators and Clients to easily configure that DNS server with rDNS PTRs. Clients can only configure rDNS PTRs for IP Entities that are in use on one of their servers.

--- 

## Setting up rDNS

The rDNS package supports different DNS servers. If uncertain which setup to use, we recommend the PowerDNS v4 setup.

IPv6 is supported with PowerDNS v4 and Cloudflare.

### PowerDNS v4 setup

---

#### New PowerDNS v4 installation

{{% hint `info` %}}
If you have PowerDNS already setup, please skip to the [Existing PowerDNS v4 server](#existing-powerdns-v4-server) section below.
{{% /hint %}}

This command must be run as root on a fresh OS with nothing else installed. It has only been tested on Debian 9 but should work on most apt-based Linux distributions. It cannot be run on the server Synergy is running on due to port conflicts. Make sure to save the details that are shown at the end of the installation in the SynergyCP Admin > System > Settings > DNS page.

```
mkdir -p /scp/dns
cd /scp/dns
wget https://install.synergycp.com/bm/packages/dns-http-control-powerdns-v4.tgz -O - | tar -zxvf -
./bin/install.sh
```

#### Existing PowerDNS v4 server

{{% hint `info` %}}
You can skip this section if you used the above install command as it does this for you.
{{% /hint %}}

{{% hint `warning` %}}
Please be advised that we can provide only limited support for issues with existing PowerDNS installations. If you encounter issues, please install a fresh server for the sole purpose of RDNS using the instructions above. 
{{% /hint %}}

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

--- 

### PowerDNS v3 setup

{{% hint `warning` %}}
PowerDNS v3 was deprecated by PowerDNS. v4 is the new recommended version, so we do not recommend using v3. As such, we no longer provide installation information for a new PowerDNS v3 server.
{{% /hint %}}

#### Existing PowerDNS v3 server

If you already have PowerDNS v3 installed and want to add support to manage it from SynergyCP, first you must make sure that the PowerDNS version running is PowerDNS v3.4 with MySQL backend. Open up `/etc/powerdns/pdns.conf` as root and add the following config variables:

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

---

### Cloudflare setup

Cloudflare can be used as a managed DNS provider for rDNS PTR records. Unlike the other methods, Cloudflare does not require you to host your own DNS server. SynergyCP will automatically create reverse DNS zones in your Cloudflare account as needed.

#### Creating a Cloudflare API Token

1. Log in to your Cloudflare account and go to **My Profile > API Tokens**.
1. Click **Create Token**.
1. Select **Create Custom Token**.
1. Give the token a name (e.g., "SynergyCP rDNS").
1. Add the following permissions:
   - **Account** > **Account Settings** > **Read**
   - **Zone** > **Zone** > **Edit**
   - **Zone** > **DNS** > **Edit**
1. Under **Account Resources**, select the account that will hold the reverse DNS zones.
1. Under **Zone Resources**, select **All zones** (zones are created automatically as PTRs are added).
1. Click **Continue to summary**, then **Create Token**.
1. Copy the token and save it — it will not be shown again.

#### Configuring SynergyCP

1. In SynergyCP Admin, go to **Settings > DNS**.
1. Set **API Type** to **Cloudflare**.
1. Paste the API token into the **API Key** field.
1. Save the settings.

#### Nameserver delegation

When SynergyCP creates a new reverse DNS zone in Cloudflare for the first time, Cloudflare assigns nameservers for that zone. These nameservers are shown in the SynergyCP log entry for the PTR creation (e.g., "Zone '0.168.192.in-addr.arpa' created in Cloudflare. Assign these nameservers: ns1.example.com, ns2.example.com").

You must configure these nameservers as the authoritative nameservers for your IP range with your RIR or upstream provider. You can also find the assigned nameservers in your Cloudflare dashboard under the zone's settings.

As of this writing, Cloudflare gives you 30 days to delegate the zone. Reverse DNS lookups _*will not*_ work until the delegation is set. Check your Cloudflare dashboard for the zone statuses.

---

### Bind setup

This must be run as root on a fresh Debian server with nothing else installed. It cannot be run on the server Synergy is running on due to port conflicts. Save the details that are shown at the end of the installation.

Note that you do _*NOT*_ need to do this if you're using the Cloudflare method above.

```
mkdir -p /scp/dns
cd /scp/dns
wget https://install.synergycp.com/bm/packages/dns-http-control-bind.tgz -O - | tar -zxvf -
./bin/install.sh
```

--- 

## Setting up the Package on SynergyCP

1. Install the rDNS package in SynergyCP.
1. Go into the SynergyCP Admin > Settings > DNS and then enter the settings displayed at the end of the DNS Server install.
1. Add an rDNS PTR from the dashboard of SynergyCP and make sure it works:

   ```
   TEST_IP=10.0.0.1
   DNS_SERVER=1.1.1.1

   dig +noall +answer -x $TEST_IP @$DNS_SERVER
   ```

1. If applicable, import your rDNS zone files from your previous DNS server on the Network > rDNS PTRs page of SynergyCP.
1. Configure the DNS Server's IP as the nameserver for your IP range announcements.
