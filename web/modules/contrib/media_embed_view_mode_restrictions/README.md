INTRODUCTION
------------

This module extends the Media Embed functionality provided by core's Media
module. Media Embed provides an editor plugin and a text format filter that
allow for media entities to be embedded inside CKEditor. It allows site
builders to determine which media bundles can be embedded (e.g. images and
remote videos but not files). When rendered, media entities are displayed with
a designated view mode. Media Embed allows site builders to control which view
modes can be selected by end users for display; however, the list of allowed
view modes is controlled on a per-text format basis, not on a per-bundle basis.
This module modifies Media Embed to allow view modes to be designated on a
per-bundle basis.

For example, a site builder has created two media bundles: 1) image and
2) remote video. For the images, two view modes are needed: 1) 'Narrow'
and 2) 'Wide'. For remote videos, only one view mode is needed, 'Remote Video'.
Without this module, end users would see all three view modes as options when
embedding media in CKEditor for both images and remotes videos. Ideally, when
embedding an image, a content editor would only be given 'Narrow' and 'Wide'
as options; and when embedding a remote video, a content editor wouldn't see
any options because 'Remote Video' is the only view mode allowed for remote
videos. This module fills that functionality gap.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/media_embed_view_mode_restrictions

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/media_embed_view_mode_restrictions

REQUIREMENTS
------------

This module requires the following modules:
 * CKEditor (contrib)
 * Filter (provided by core)
 * Media (provided by core)
 * Text Editor (provided by core)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
[https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)
for further information.

 * On installation, existing Media Embed configuration is retained.

 * On un-installation, per-bundle configuration is removed.

CONFIGURATION
-------------

Media Embed configuration remains unchanged following installation. To
configure Media Embed for a given text format/editor, navigate to its
configuration page (/admin/config/content/formats/manage/{name}).

 * The _Media types selectable in the Media Library_ setting is unchanged.
 * The _Default view mode_ field now exists on a per-bundle basis.
 * The _View modes selectable in the 'Edit media' dialog_ field now exists on
   a per-bundle basis.

RELATED ISSUES
-----------

 * [CKEditor Media: Only allow enabled view modes](https://www.drupal.org/project/drupal/issues/3097416)
 * [Optionally allow choosing a view mode for embedded media in EditorMediaDialog](https://www.drupal.org/project/drupal/issues/3074608)

MAINTAINERS
-----------

Current maintainers:
 * Chris Burge - https://www.drupal.org/user/1826152

Initial development was sponsored by:
 * UNIVERSITY OF NEBRASKA-LINCOLN
   https://www.drupal.org/university-of-nebraska
   https://dxg.unl.edu
