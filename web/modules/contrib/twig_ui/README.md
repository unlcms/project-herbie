# CONTENTS OF THIS FILE

- Introduction
- Requirements
- Recommended modules
- Installation
- Configuration
- Maintainers

# INTRODUCTION

The *Twig UI Templates* module provides an interface to define Twig templates
in the admin user interface. A given Twig UI template will override any other
templates with the same theme suggestion for the designated theme(s).

- For a full description of this module, visit the project page:
  https://www.drupal.org/project/twig_ui

- To submit bug reports and feature suggestions, or track changes:
  https://www.drupal.org/project/issues/twig_ui

# REQUIREMENTS

This module requires only Drupal core:

Drupal core ^8.8.4 || ^9.0

# RECOMMENDED MODULES

This module provides CodeMirror through integration with the
*[CodeMirror Editor](https://www.drupal.org/project/codemirror_editor)* module.

# INSTALLATION

Install/Enable the twig_ui module as you would normally install a contributed
Drupal module.

- Visit https://www.drupal.org/node/1897420 for further information.

# CONFIGURATION

There are two levels of configuration: global and template.

## Global configuration

Global settings can be administered at
Admin => Configuration => System => Twig UI Templates.

### Default Selected Themes

Themes designated as default selected themes will be pre-selected for new Twig UI templates.

Access to global configuration is controlled by the 
'Administer Twig UI Templates Settings' permission.

### CodeMirror Configuration

When the CodeMirror Editor module is enabled, it is possible to configure the
CodeMirror instance for template code field on the template edit form.
Configuration is entered as YAML. For example:

```
lineNumbers: false
buttons:
  - bold
  - italic
```

See the [CodeMirror manual](https://codemirror.net/doc/manual.html) for more
configuration options.

## Template configuration

Twig UI templates can be managed at Admin => Structure => Twig templates.

Access to Twig UI templates is controlled by the
'Administer Twig templates' permission.

## Other considerations

Sites hosted on non-Apache web servers (e.g. Nginx) should ensure that the
`public://twig_ui` directory is not publicly accessible.

# TWIG TEMPLATE FIELDS

The following fields define a Twig UI template:

## Template name

This is the administrative name for the template. 

## Machine name

The machine name must be unique for this template. It is auto-generated from
the template name and can be overridden.

## Theme suggestion

The theme suggestion is is used to register the template with Drupal's theme system.
See [Twig Template naming conventions](https://www.drupal.org/docs/theming-drupal/twig-in-drupal/twig-template-naming-conventions) for more information.

## Template code

The template code is the code that will be written to the template file.

## Themes

A Twig UI template can be designated for use by any active theme.

## Status

The status is the enabled/disabled status of the Twig UI template. Only one
Twig UI template for a given theme suggestion and theme combination may be
active at a given time.

# MAINTAINERS

Current maintainers:
- Chris Burge - https://www.drupal.org/u/chris-burge

This project has been sponsored by:
- (University of Nebraska-Lincoln, Digital Experience Group)[https://dxg.unl.edu]
