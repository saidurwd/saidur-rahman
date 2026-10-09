#!/usr/bin/env bash
#
# Writes an Nginx snippet that makes Nginx trust the visitor address Cloudflare passes along, so logs, rate limits and
# analytics see the real client instead of a Cloudflare server. Run it once, and again every few months (the lists change).
#
#   sudo ./deploy/cloudflare-ips.sh > /etc/nginx/snippets/cloudflare-realip.conf && sudo nginx -t && sudo systemctl reload nginx

set -euo pipefail

echo "# Generated $(date -u +%F) from https://www.cloudflare.com/ips/"
for list in ips-v4 ips-v6; do
    curl -fsS "https://www.cloudflare.com/$list" | sed 's/^/set_real_ip_from /; s/$/;/'
    echo
done
echo "real_ip_header CF-Connecting-IP;"
