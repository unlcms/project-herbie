import UNLDialog from 'https://wdn-cdn.unl.edu/wdn/templates_6.0/js/components/component.dialog.js';

export default class MuseumSearch {
    searchContainer = null;

    searchDialogElement = null;

    domDesktopSearchLink = null;

    domDesktopSearchBtns = [];

    domMobileSearchLink = null;

    domMobileSearchBtns = [];

    mobileSearchBtn = null;

    domSearchResultWrapper = null;

    domQ = null;

    domSearchForm = null;

    domEmbed = null;

    searchEmbedVersion = '5.0';

    submitted = false;

    postReady = false;

    searchHost = 'https://search.unl.edu'; // domain of UNL Search app

    searchPath = '/results'; // path to UNL Search app

    allowSearchParams = ['u', 'cx'];  // QS Params allowed by UNL Search app

    searchAction = '';

    searchFrameAction = '';

    siteHomepage = '';

    localSearch = null;

    progress = null;

    museumSearch = null;

    searchOpenedEvent = new Event(MuseumSearch.events('searchOpened'));

    searchClosedEvent = new Event(MuseumSearch.events('searchClosed'));

    constructor() {
        this.searchContainer = document.getElementById('dcf-search');

        this.searchDialogElement = document.querySelector('dialog#dcf-search-dialog');
        if (this.searchDialogElement === null) {
            throw new Error('Missing Search Dialog Element');
        }

        // Get Search links and buttons
        this.domDesktopSearchLink = document.getElementById('dcf-search-toggle-link');
        this.domDesktopSearchBtns = Array.from(document.getElementsByClassName('dcf-btn-search-desktop'));
        this.domMobileSearchLink = document.getElementById('dcf-mobile-search-link');
        this.domMobileSearchBtns = Array.from(document.getElementsByClassName('dcf-btn-search-mobile'));

        console.log(this.domDesktopSearchLink);
        console.log(this.domDesktopSearchBtns);

        // Disable links and Enable buttons
        this.mobileSearchBtn = null;
        if (this.domMobileSearchLink && this.domMobileSearchBtns && this.domMobileSearchBtns.length) {
            this.domMobileSearchLink.setAttribute('hidden', '');
            this.domMobileSearchBtns.forEach((singleMobileButton) => {
                singleMobileButton.removeAttribute('hidden');
                singleMobileButton.setAttribute('aria-expanded', 'false');
                singleMobileButton.setAttribute('aria-label', 'Open search');
                singleMobileButton.innerHTML = this.domMobileSearchLink.innerHTML;
                this.mobileSearchBtn = singleMobileButton;
            });
        }

        if (this.domDesktopSearchLink && this.domDesktopSearchBtns && this.domDesktopSearchBtns.length) {
            this.domDesktopSearchLink.setAttribute('hidden', '');
            this.domDesktopSearchLink.setAttribute('aria-hidden', true);
            this.domDesktopSearchBtns.forEach((singleDesktopButton) => {
                singleDesktopButton.removeAttribute('hidden');
                singleDesktopButton.setAttribute('aria-expanded', 'false');
                singleDesktopButton.setAttribute('aria-label', 'Open search');
                singleDesktopButton.innerHTML = this.domDesktopSearchLink.innerHTML;
            });
        }

        this.domSearchResultWrapper = document.getElementById('dcf-search-results-wrapper');
        this.domQ = document.getElementById('dcf-search_query');
        this.domSearchForm = document.getElementById('dcf-search-form');
        this.searchAction = `${this.searchHost}${this.searchPath}`;
        this.searchFrameAction = `${this.searchAction}?embed=${this.searchEmbedVersion}`;
        this.siteHomepage = `${location.protocol}//${location.host}/`;
        this.localSearch = this.#getLocalSearch();

        // Give up if the search form has been unexpectedly removed
        if (!this.domSearchForm) {
            return;
        }

        // Ensure the default action is the UNL Search app
        if (this.domSearchForm.action !== this.searchAction) {
            this.domSearchForm.setAttribute('action', this.searchAction);
        }

        // Create a loading indicator
        this.progress = document.createElement('progress');
        this.progress.setAttribute('id', 'wdn_search_progress');
        this.progress.innerText = 'Loading...';

        // Add an input to the form to let the search application know that we want the embedded format
        this.domEmbed = document.createElement('input');
        this.domEmbed.type = 'hidden';
        this.domEmbed.name = 'embed';
        this.domEmbed.value = this.searchEmbedVersion; // Specify which theme version for search

        // Add a parameter for triggering the iframe compatible rendering
        this.domSearchForm.appendChild(this.domEmbed);

        // Add an event listener for close search from search iframe
        window.addEventListener('message', function(event) {
            if (event.data === MuseumSearch.events('iframeMessage')) {
                this.closeSearch();
            }
        }, false);

        // Set up dialog open and close listeners
        this.searchDialogElement.addEventListener(UNLDialog.events('dialogPreOpen'), this.#dialogOpened.bind(this));
        this.searchDialogElement.addEventListener(UNLDialog.events('dialogPostClose'), this.#dialogClosed.bind(this));

        // Set up form submit listeners
        this.domSearchForm.addEventListener('submit', this.#handleFormSubmit.bind(this));

        this.searchContainer.dispatchEvent(new CustomEvent(MuseumSearch.events('searchReady'), {
            detail: {
                classInstance: this,
            },
        }));

        this.searchDialogElement.dispatchEvent(new CustomEvent(MuseumSearch.events('searchReady'), {
            detail: {
                classInstance: this,
            },
        }));
    }

    // The names of the events to be used easily
    static events(name) {
        const events = {
            searchReady: 'searchReady',
            iframeMessage: 'wdn.search.close',
            searchClosed: 'searchClosed',
            searchOpened: 'searchOpened',
        };
        Object.freeze(events);

        return name in events ? events[name] : undefined;
    }

    /**
     * Handles when the search form submits
     *
     * @param {Event} event
     * @returns { Void }
     */
    #handleFormSubmit(event) {
        event.preventDefault();

        // Enable the iframe search params
        this.#createSearchFrame();
        this.#activateSearch();
        this.domEmbed.disabled = false;

        // This is band-aid to fix the issue with the double scroll bar
        this.domSearchResultWrapper.parentElement.classList.add('dcf-overflow-y-hidden');
        this.domSearchResultWrapper.parentElement.classList.remove('dcf-overflow-y-auto');

        if (!event.detail || event.detail !== 'auto') {
            // a11y: send focus to the results if manually submitted
            this.museumSearch.focus();
        }

        // Support sending messages to iframe without reload
        if (this.postReady) {
            this.#postSearchMessage(`${this.domQ.value} site:https://museum.unl.edu/`);
        }
    }

    /**
     * Creates the search iframe element
     *
     * @returns { Void }
     */
    #createSearchFrame() {
        // Lazy create the search iframe
        if (this.museumSearch === null) {
            this.museumSearch = document.createElement('iframe');
            this.museumSearch.name = 'museumSearch';
            this.museumSearch.setAttribute('id', 'wdn_search_frame');
            this.museumSearch.title = 'Search';
            this.museumSearch.className = 'dcf-b-0 dcf-w-100% dcf-h-100%';
            this.museumSearch.src = `${this.searchFrameAction}&q=${this.domQ.value} site:https://museum.unl.edu/&u=https://museum.unl.edu/&type=this_site`;

            this.museumSearch.addEventListener('load', function() {
                this.postReady = true; // iframe should be ready to post messages to
                this.progress.remove();
            }.bind(this));

            this.domSearchResultWrapper.appendChild(this.progress);
            this.domSearchResultWrapper.appendChild(this.museumSearch);

            this.searchDialogElement.style.height = '100%';
        }
    }

    /**
     * Activates search input
     *
     * @returns { Void }
     */
    #activateSearch() {
        this.domSearchForm.parentElement.classList.add('active');
        this.progress.remove();
    }

    /**
     * Posts new queries into the iframe instead of having to reload it every time
     *
     * @param { String } query
     * @returns { Void }
     */
    #postSearchMessage(query) {
        this.museumSearch.src = `${this.searchFrameAction}&q=${query}&u=https://museum.unl.edu/&type=this_site`;
        this.progress.remove();
    }

    /**
     * Function to be called when the dialog closes
     *
     * @returns { Void }
     */
    #dialogClosed() {
        this.domQ.value = '';
        this.domSearchForm.parentElement.classList.remove('active');
        this.domSearchForm.reset();

        window.dataLayer = window.dataLayer || [];
        function gtag() {
            window.dataLayer.push(arguments);
        }
        gtag('event', 'UNL_search_closed', {
            'app_name': 'UNL_search',
        });

        if (this.museumSearch) {
            this.museumSearch = null;
            this.domSearchResultWrapper.innerHTML = '';
            this.postReady = false;
        }

        document.dispatchEvent(this.searchClosedEvent);
    }

    /**
     * Function to be called when the dialog Opens
     *
     * @returns { Void }
     */
    #dialogOpened() {
        document.dispatchEvent(this.searchOpenedEvent);

        window.dataLayer = window.dataLayer || [];
        function gtag() {
            window.dataLayer.push(arguments);
        }
        gtag('event', 'UNL_search_opened', {
            'app_name': 'UNL_search',
        });
    }

    /**
     * Gets local search link on the page
     *
     * @returns { String|null }
     */
    #getLocalSearch() {
        const link = document.querySelector('link[rel="search"]');
        if (link && link.type !== 'application/opensearchdescription+xml') {
            return link.href;
        }

        return null;
    }
}
