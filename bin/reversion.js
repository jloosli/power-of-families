var Version = require("node-version-assets");
var versionInstance = new Version({
    assets: ['build/vivial2016/assets/main.css', 'build/vivial2016/assets/scripts.js'],
    grepFiles: ['build/vivial2016/php-scripts/Bpm/ThemeSetup.class.php']
});
versionInstance.run();
