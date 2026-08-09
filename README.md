## About

The rDNS package adds a frontend to SynergyCP that allows Clients and Administrators to set rDNS PTR records. Currently, BIND, PowerDNS v3/v4, and Cloudflare are supported.

An rDNS PTR is a single DNS record pointing a unique IP address to a hostname.
When someone uses a server to send out emails, the receiving server usually does
a [reverse DNS lookup](https://en.wikipedia.org/wiki/Reverse_DNS_lookup) on the IP address which helps determine whether or not the host is a spammer. This is called [forward-confirmed reverse DNS](https://en.wikipedia.org/wiki/Forward-confirmed_reverse_DNS).

You can host a DNS server that answers with the hostname of the IP address that the receiving server looked up. The purpose of this package is to allow Administrators and Clients to easily configure that DNS server with rDNS PTRs. Clients can only configure rDNS PTRs for IP Entities that are in use on one of their servers.

## Features

- Manage PTR records for both IPv4 and IPv6 addresses, synced automatically to your DNS provider.
- Search PTRs by full or partial IP address (IPv4 or IPv6) or by hostname.
- Import BIND-style reverse zone files: standard `in-addr.arpa` and `ip6.arpa` zones of any size, as well as bulk multi-zone dumps with fully-qualified record names. Imports are batched for speed and report added, updated, and unchanged counts.
- Migrate between DNS providers with the `rdns:sync-to-dns` artisan command, which re-syncs every stored PTR to the currently configured provider.
- Clients validate against forward DNS (the hostname must have a matching A/AAAA record) before their PTR changes are accepted.

## Setup

Please refer to [our documentation](https://kb.synergycp.com/docs/packages/rdns/) to get started.

Looking for further assistance? Please [contact us](https://kb.synergycp.com/#contacting-support).
