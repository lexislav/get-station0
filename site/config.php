<?php

return function (
    string $station0Root,
    string $siteRoot,
    string $projectRoot = "",
): array {
    if ($projectRoot === "") {
        $projectRoot = dirname($siteRoot);
    }

    return [
        "name" => "Station0",
        "baseUrl" => $_ENV["BASE_URL"] ?? "http://localhost:8080",
        "debug" => filter_var($_ENV["DEBUG"] ?? false, FILTER_VALIDATE_BOOLEAN),
        "adminPath" => rtrim($_ENV["ADMIN_PATH"] ?? "/admin", "/"),

        // Admin UI language (station0 >= 0.5). Shipped translations: 'en', 'cs'.
        // Unknown locales and missing keys fall back to the English base.
        "admin_locale" => $_ENV["ADMIN_LOCALE"] ?? "cs",

        // Admin editor preferences (station0 >= 0.7).
        // blockCollapse: 'remember' (default) | 'expanded' | 'collapsed' —
        // initial collapse state of block editors on the page edit screen.
        "admin" => [
            "blockCollapse" => $_ENV["ADMIN_BLOCK_COLLAPSE"] ?? "remember",
        ],

        "paths" => [
            "content" => $siteRoot . "/content",
            "templates" => $siteRoot . "/templates",
            "adminTemplates" => $station0Root . "/admin/templates",
            "cache" => $projectRoot . "/writable/cache",
            "sessions" => $projectRoot . "/writable/sessions",
            "logs" => $projectRoot . "/writable/logs",
            "db" => $projectRoot . "/writable/db.sqlite",
            "uploads" => $projectRoot . "/public/uploads",
            "projectRoot" => $projectRoot,
        ],
        "mail" => [
            "host" => $_ENV["MAIL_HOST"] ?? "localhost",
            "port" => (int) ($_ENV["MAIL_PORT"] ?? 587),
            "username" => $_ENV["MAIL_USERNAME"] ?? "",
            "password" => $_ENV["MAIL_PASSWORD"] ?? "",
            "from" => $_ENV["MAIL_FROM"] ?? "noreply@localhost",
            "fromName" => $_ENV["MAIL_FROM_NAME"] ?? "Station0",
            "encryption" => $_ENV["MAIL_ENCRYPTION"] ?? "tls",
        ],
        "session" => [
            "name" => "station0",
            "cookieLifetime" => 0,
            "sameSite" => "Lax",
            "secure" => filter_var(
                $_ENV["HTTPS"] ?? false,
                FILTER_VALIDATE_BOOLEAN,
            ),
        ],
    ];
};
