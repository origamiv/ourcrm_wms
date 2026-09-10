import { defineConfig } from "@playwright/test";
export default defineConfig({
    testDir: "./tests/browser",
    workers: 1,
    timeout: 60000,
    use: {
        baseURL: "http://127.0.0.1:8137",
        headless: true,
        screenshot: "only-on-failure",
        trace: "retain-on-failure",
    },
    webServer: {
        command:
            "php tests/Support/prepare_browser.php && cd public && php -S 127.0.0.1:8137 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php",
        url: "http://127.0.0.1:8137/login",
        reuseExistingServer: false,
        stderr: "ignore",
        env: {
            APP_ENV: "testing",
            APP_DEBUG: "true",
            APP_URL: "http://127.0.0.1:8137",
            DB_CONNECTION: "pgsql",
            DB_URL: "",
            DB_HOST: "/var/run/postgresql",
            DB_PORT: "5432",
            DB_DATABASE: "wms_browser_test",
            DB_USERNAME: "root",
            DB_PASSWORD: "",
            DB_SCHEMA: "public",
            SESSION_DRIVER: "file",
            SESSION_SECURE_COOKIE: "false",
            SESSION_DOMAIN: "",
            CACHE_STORE: "array",
            PULSE_ENABLED: "false",
        },
    },
});
