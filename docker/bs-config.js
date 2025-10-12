// Browser-Sync Configuration for Web 1.0 Development
// Provides hot-reloading while maintaining the authentic Web 1.0 experience

module.exports = {
    // Proxy the nginx server
    proxy: {
        target: "localhost:8084",
        ws: true
    },

    // Files to watch for changes
    files: [
        "/var/www/web1-site1/*.html",
        "/var/www/web1-site1/**/*.html",
        "/var/www/web1-site1/**/*.php",          // Watch PHP files
        "/var/www/web1-site1/includes/*.php",    // Watch includes specifically
        "/var/www/web1-site1/**/*.css",          // Watch CSS files (including styles.css)
        "/var/www/web1-site1/images/**/*",
        "/var/www/web1-site1/assets/**/*"
    ],

    // Port for browser-sync server
    port: 3000,

    // Disable browser-sync UI to keep it minimal
    ui: false,

    // Disable notifications to maintain Web 1.0 aesthetic
    notify: false,

    // Don't open browser automatically
    open: false,

    // Disable ghost mode (syncing interactions across devices)
    ghostMode: false,

    // Log level
    logLevel: "debug",

    // Log prefix
    logPrefix: "Web1.0-Sync",

    // Reload delay to prevent too frequent refreshes
    reloadDelay: 100,

    // Reload throttle to batch multiple file changes
    reloadThrottle: 500,

    // Don't inject CSS changes, always do full reload for Web 1.0 simplicity
    injectChanges: false,

    // Disable snippet injection - Nginx handles this via sub_filter
    snippet: false,

    // Script injection settings
    scriptPath: function (path) {
        return "/browser-sync" + path;
    },

    // Watch options
    watchOptions: {
        ignoreInitial: true,
        ignored: [
            "**/node_modules/**",
            "**/counter/counter.txt",
            "**/counter/ips/**",
            "**/counter/*.php"
        ]
    }
};
