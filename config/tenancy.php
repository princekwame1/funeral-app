<?php

return [
    /**
     * Hosts treated as the platform "root" — subdomains of these are looked up
     * as tenant slugs. The APP_URL host is added automatically.
     *
     * Example: for `boateng.funeraldonations.com` add `funeraldonations.com`.
     * For local dev use `.localhost` or `.test`.
     */
    'base_hosts' => [
        'localhost',
        '127.0.0.1',
        '.test',
        'funeraldonations.com',
    ],

    'reserved_subdomains' => ['www', 'app', 'admin', 'super', 'api', 'mail', 'static'],
];
