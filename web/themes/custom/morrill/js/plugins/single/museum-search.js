import { loadStyleSheet } from 'https://wdn-cdn.unl.edu/wdn/templates_6.0/js/lib/unl-utility.js';
const searchCssUrl = 'https://wdn-cdn.unl.edu/wdn/templates_6.0/css/components-js/_search.css';
const dialogCssUrl = 'https://wdn-cdn.unl.edu/wdn/templates_6.0/css/components-js/_dialogs.css';

/**
 * This is where the imported class will be stored
 * @type {?MuseumSearch} MuseumSearch
 */
let MuseumSearch = null;

/**
 * @type {?MuseumSearch} searchInstance
 */
let searchInstance = null;

// Query Selector for the search component
const querySelector = '.dcf-search';

// Type of plugin
const pluginType = 'single';

// Storing the state whether the plugin is initialized or not
let isInitialized = false;

/**
 * Gets the query selector which is used for this plugin's component
 * @returns { String }
 */
export function getQuerySelector() {
    return querySelector;
}

/**
 * Gets the plugin type
 * @returns { String }
 */
export function getPluginType() {
    return pluginType;
}

/**
 * Returns if the plugin has been initialized yet
 * @returns { Boolean }
 */
export function getIsInitialized() {
    return isInitialized;
}

export function isOnPage() {
    return document.querySelector(querySelector) !== null;
}

/**
 * Initializes plugin
 * @returns { Promise<MuseumSearch|Null> }
 */
export async function initialize(options={}) {
    if (isInitialized) { return searchInstance; }
    isInitialized = true;

    const searchElement = document.querySelector(querySelector);
    if (searchElement === null) { return null; }

    const basePath = drupalSettings.path_to_theme.path;
    const searchComponent = await import(`${basePath}/js/components/museum-search.js`);
    MuseumSearch = searchComponent.default;
    await loadStyleSheet(dialogCssUrl);
    await loadStyleSheet(searchCssUrl);

    searchInstance = new MuseumSearch(options);

    document.dispatchEvent(new CustomEvent('UNLPluginInitialized', {
        detail: {
            pluginType: pluginType,
            pluginComponent: MuseumSearch,
            classInstance: searchInstance,
            styleSheetsLoaded: [
                dialogCssUrl,
                searchCssUrl,
            ],
        },
    }));

    return searchInstance;
}
