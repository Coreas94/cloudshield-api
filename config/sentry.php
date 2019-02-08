<?php

return array(
    //'dsn' => env('SENTRY_DSN'),
    'dsn' => 'https://1217784893374aa694b048762b1affd4@sentry.io/1381556',

    // capture release as git sha
    // 'release' => trim(exec('git log --pretty="%h" -n1 HEAD')),

    // Capture bindings on SQL queries
    'breadcrumbs.sql_bindings' => true,

    // Capture default user context
    'user_context' => true,
);
