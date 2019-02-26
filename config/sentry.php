<?php

return array(
    //'dsn' => env('SENTRY_DSN'),
    //'dsn' => 'http://d5676e233af441cd907209e2ebc40f37:4f2a464c101d4ac982164ff1609700e4@172.16.20.234:9000/6',
    'dsn' => 'https://1217784893374aa694b048762b1affd4@sentry.io/1381556',

    // capture release as git sha
    // 'release' => trim(exec('git log --pretty="%h" -n1 HEAD')),

    // Capture bindings on SQL queries
    'breadcrumbs.sql_bindings' => true,

    // Capture default user context
    'user_context' => true,
);
