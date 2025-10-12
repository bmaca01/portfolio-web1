// Simple Browser-Sync Configuration
// Updated to watch PHP files and CSS changes
module.exports = {
    proxy: "localhost:8084",
    port: 3000,
    files: [
        "/var/www/web1-site1/**/*.html",
        "/var/www/web1-site1/**/*.css",
        "/var/www/web1-site1/**/*.js",
        "/var/www/web1-site1/**/*.php"       // Watch all PHP files
    ],
    ui: false,
    notify: false,
    open: false,
    logLevel: "info",
    logPrefix: "BrowserSync",
    watchOptions: {
        ignoreInitial: true,
        ignored: [
            "**/counter/counter.txt",
            "**/counter/ips/**",
            "**/counter/*.php"                // Exclude counter PHP files
        ]
    }
};
