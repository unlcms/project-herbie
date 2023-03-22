# Block Content Permission

The Block Content Permissions module adds permissions for administering
"block content types" and "block content". The "Administer blocks" permission
normally manages these entities on the "Custom block library" pages:
"Block types" and "Blocks".

For a full description of the module, visit the
[project page](https://www.drupal.org/project/block_content_permissions).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/block_content_permissions).


## Table of contents

- Requirements
- Recommended modules
- Installation
- Configuration
- Troubleshooting
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Recommended modules

[Block Region Permissions](https://www.drupal.org/project/block_region_permissions):
Adds permissions for administering the "Block layout" pages.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

1. Enable the module at Administration > Extend.
1. Configure user permissions at Administration > People > Permissions:

    - Block > Administer blocks

      (Required) Allows management of blocks. **Warning:** This permission
      grants access to block pages not managed by this module. Use the
      recommended modules to restrict the rest. Requirements for this permission
      have been removed for most pages, so it is not required for some use
      cases. It is still required for navigational purposes and the "Blocks"
      views page.

    - Block Content Permissions > [*type*]: Create new block content

      Create block content for a specific type. View on "Blocks" page.

    - Block Content Permissions > [*type*]: Delete any block content

      Delete block content for a specific type. View on "Blocks" page.

    - Block Content Permissions > [*type*]: Edit any block content

      Edit block content for a specific type. View on "Blocks" page.

    - Block Content Permissions > Administer block content types

      (**Trusted roles only**) Allows management of all block content types. The
      "Field UI" permissions fully manage the displays, fields, and form
      displays.

    - Block Content Permissions > View restricted block content

      Allows viewing and searching of block content for all types. Disabling
      this permission restricts the types to ones the user can manage. This
      permission is only used on the "Blocks" views page and will not affect the
      "Create", "Edit" and "Delete" restrictions. The views page requires the
      "Administer blocks" permission.

    - Field UI > Custom block: Administer display

      (**Trusted roles only**) Allows management of displays for all block
      content types.

    - Field UI > Custom block: Administer fields

      (**Trusted roles only**) Allows management of fields for all block content
      types.

    - Field UI > Custom block: Administer form display

      (**Trusted roles only**) Allows management of form display for all block
      content types.

    - System > Use the administration pages and help

      Allows use of admin pages during navigation.

    - System > View the administration theme

      Allows use of administrative theme for aesthetics.

    - Toolbar > Use the toolbar

      Allows use of toolbar during navigation.


## Troubleshooting

List of pages that should deny access depending on permissions.

"Block types" pages ("Administer block content types" permission):
- List:
    - Path: /admin/structure/block/block-content/types
    - Route: entity.block_content_type.collection
- Add:
    - Path: /admin/structure/block/block-content/types/add
    - Route: block_content.type_add
- Edit:
    - Path:/admin/structure/block/block-content/manage/{block_content_type}
    - Route: entity.block_content_type.edit_form
- Delete:
    - Path: /admin/structure/block/block-content/manage/{block_content_type}/delete
    - Route: entity.block_content_type.delete_form

"Blocks" pages ("Create", "Delete", "Edit" and "View" permissions):
- List:
    - Path: /admin/structure/block/block-content
    - Route: entity.block_content.collection
    - Route: view.block_content.page_1
- Add:
    - Path: /block/add
    - Route: block_content.add_page
- Add type:
    - Path: /block/add/{block_content_type}
    - Route: block_content.add_form
- Edit:
    - Path: /block/{block_content}
    - Route: entity.block_content.canonical
    - Route: entity.block_content.edit_form
- Delete:
    - Path: /block/{block_content}/delete
    - Route: entity.block_content.delete_form


## Maintainers

- Joshua Roberson - [joshua.roberson](https://www.drupal.org/u/joshuaroberson)
