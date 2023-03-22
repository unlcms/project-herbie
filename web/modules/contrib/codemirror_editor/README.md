CodeMirror Editor
=================


INTRODUCTION
------------

This module integrates the CodeMirror editor library into Drupal.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/codemirror_editor
 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/codemirror_editor

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

Install the CodeMirror Editor module as you would normally install a
contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
further information.

By default CodeMirror library is loaded from CDN. If you prefer to install it
locally download and unpack the library to the libraries directory. Make sure
the path to the library becomes: libraries/codemirror. Use
`drush codemirror:download` command for quick installation.

If you are using Composer for downloading third-party libraries turn off
the 'minified' setting as asset-packagist.org does not provide minified files.

See https://www.drupal.org/node/2718229/#third-party-libraries

CONFIGURATION
-------------

1. Navigate to Administration > Extend and enable the module.
2. Navigate to Administration > Configuration > Content authoring >
   CodeMirror for configuration.

KNOWN ISSUES
------------

The editor may not work correctly when Big pipe module is enabled and
parent form is rendered within cache placeholder.
