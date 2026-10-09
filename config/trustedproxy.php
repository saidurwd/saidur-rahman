<?php

/*
| Which reverse proxies Laravel believes about the real client address and https. This is read from config, not env(),
| so it keeps working once configuration is cached on the server.
|
| TRUSTED_PROXIES is a comma separated list of addresses (for example 127.0.0.1 for Nginx on the same machine), or *
| to trust any sender, which is only safe when the firewall lets nothing but Cloudflare or your proxy reach PHP.
*/
$raw = trim((string) env('TRUSTED_PROXIES', ''));

return [
    'proxies' => $raw === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $raw)))),
];
