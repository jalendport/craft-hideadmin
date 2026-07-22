<img src="src/icon.svg" alt="icon" width="100" height="100">

# Hide Admin plugin for Craft CMS 4

Hide Admin hides admin users from non-admin users, which is useful if you want to hand over the responsibility of user management without granting access to admin accounts.

## Overview

For non-admin users in the control panel:

- Admin users are excluded from the Users index and from user relation fields (Users fields, entry authors)
- Admin users can't be viewed, edited, or deleted — including by navigating to an admin's edit screen directly
- The “Admin” option is removed from the Users index filter

Because admin users are filtered out of control-panel user queries, an entry authored by an admin will show a blank author to a non-admin — this is the same hiding at work, not a bug.

Hiding is scoped to the control panel: front-end `craft.users` queries and GraphQL are not filtered. Complete, configurable hiding — per-group visibility rules and opt-in front-end enforcement — ships in [Silo](https://github.com/jalendport/craft-silo), the Craft 5 successor.

## Requirements

This plugin requires Craft CMS 4.3.0 or later.

## Installation

To install the plugin, either install it through the plugin store or follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require jalendport/craft-hideadmin

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Hide Admin.
