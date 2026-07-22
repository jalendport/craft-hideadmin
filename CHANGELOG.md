# Hide Admin Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## Unreleased

### Changed
- Hide Admin now requires Craft CMS 4.3.0 or later

### Fixed
- Fixed a bug where a Users field limited to specific sources wouldn't let non-admins select any user ([#9](https://github.com/jalendport/craft-hideadmin/issues/9))
- Fixed an error that could occur on GraphQL and console requests when no user was logged in ([#10](https://github.com/jalendport/craft-hideadmin/issues/10))

### Security
- Prevented non-admins from viewing, editing, or deleting admin users directly by their element ID — previously only the Users index was filtered
- Excluded admin users from user relation fields and other control-panel user queries for non-admins

## 2.0.0-beta.1 - 2023-06-08

### Added
- Initial Craft 4 release

## 1.2.0 - 2023-06-08

### Changed
- Changed how the "Admins" source is hid to prevent the "All Users" source from being hid as well ([#11](https://github.com/jalendport/craft-hideadmin/issues/11))

## 1.1.0 - 2019-11-16

Transfer of ownership 👀

## 1.0.1 - 2018-04-30

### Changed
- Removed redundant files

## 1.0.0 - 2018-04-30

### Added
- Initial release
