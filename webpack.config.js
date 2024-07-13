const Encore = require('@symfony/webpack-encore');

Encore
    // Configurez ici les chemins de sortie et publics
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    // D'autres configurations selon vos besoins
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .addEntry('app', './assets/app.js')
    .enablePostCssLoader()
    .enableSassLoader()

    // Configurez le runtime chunk
    .enableSingleRuntimeChunk()

    // Configurez Babel si nécessaire
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    });

module.exports = Encore.getWebpackConfig();
