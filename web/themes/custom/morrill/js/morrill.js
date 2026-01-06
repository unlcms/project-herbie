const basePath = drupalSettings.path_to_theme.path;
const MuseumSearchUrl = `${basePath}/js/plugins/single/museum-search.js`;

delete window.UNL.autoLoader.config.plugins.UNLIdm;
delete window.UNL.autoLoader.config.plugins.UNLSearch;
delete window.UNL.autoLoader.config.plugins.UNLQa;

window.UNL.autoLoader.config.plugins.MuseumSearch = {
    optOutSelector: null,
    optInSelector: null,
    customConfig: {},
    onPluginLoadedElement: null,
    url: MuseumSearchUrl,
};

window.UNL.banner.config.enabled = false;
window.UNL.alert.config.enabled = false;
window.UNL.analytics.config.enabled = false;
window.UNL.chat.config.enabled = false;

