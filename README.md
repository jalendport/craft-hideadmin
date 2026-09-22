<img src="src/icon.svg" alt="icon" width="100" height="100">

# Hide Admin plugin for Craft CMS 4

Hide Admin hides admin users from non-admin users, which is useful if you want to hand over the responsibility of user management without granting access to admin accounts.

## Overview

For non-admin users in the control panel:

- Admin users are hidden from the Users index, and the “Admins” source is removed from its sidebar
- The “Admin” option is removed from the Users index filter
- Admin users can’t be viewed, edited, or deleted — including by navigating to an admin’s edit screen directly

Admin users still appear where content refers to them, such as an entry’s author or a Users field, so non-admins can keep editing that content.

## Requirements

This plugin requires Craft CMS 4.3.0 or later.

## Installation

To install the plugin, either install it through the plugin store or follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require jalendport/craft-hideadmin

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Hide Admin.
