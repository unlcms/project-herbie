The *[Digital Campus Framework](https://github.com/d-c-n/dcf)* (DCF) provides a brand-agnostic foundation for creating websites and web applications. DCF makes available a set of tools that significantly reduce development time, and its support for CSS grid sets it apart from other frameworks.

This project contains modules to facilitate Drupal implementation:

# DCF Classes
In the DCF framework, CSS classes are used for everything from adding padding to an element to defining page layouts. In some cases, it makes sense to expose a subset of these classes to content creators. The *DCF Classes* module allows for a site builder to define a whitelist of classes to make available to content creators. See README.md in the *DCF Classes* module for more information.

# DCF Layouts
The *DCF Layouts* module provides CSS-grid-based layouts for core's *Layout Discovery*, which can be used in *Layout Builder*. Four layouts are provided: 1) One column (DCF), 2) Two column (DCF), 3) Three column (DCF), and 4) Four column (DCF). A number of configuration options are provided for each layout. See README.md in the *DCF Layouts* module for more information.

# DCF CKEditor
The *DCF CKEditor* module provides CKEditor plugins to integrate with the DCF. Currently, it provides a *DCF Table* plugin, which automatically applies the `dcf-table` class to tables created in CKEditor. See README.md in the *DCF CKEditor* module for more information.

# DCF Lazy Loading
The *DCF Lazy Loading* module allows for a site builder to use *DCF Lazy Loading* with Drupal. It supports the base lazying loading functionality where images are loaded prior to entering the viewport. It also provides the ability to automatically calculate the *sizes* attribute based on the parent element.
