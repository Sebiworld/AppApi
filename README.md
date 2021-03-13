**Connect your apps to ProcessWire CMS!**

This module helps you to create an api, to which an app or an external service can connect to.

**A special thanks goes to [Thomas Aull](https://github.com/thomasaull)** , whose module [RestApi](https://modules.processwire.com/modules/rest-api/) was the starting point to this project.

**Credits:** go to [Benjamin Milde](https://github.com/LostKobrakai) for his code example on how to use FastRoute with ProcessWire and [Camilo Castro](https://gist.github.com/clsource) for this [Gist](https://gist.github.com/clsource/dc7be74afcbfc5fe752c)

[![Current Version](https://img.shields.io/github/v/tag/Sebiworld/AppApi?label=Current%20Version)](https://img.shields.io/github/v/tag/Sebiworld/AppApi?label=Current%20Version) [![Current Version](https://img.shields.io/github/issues-closed-raw/Sebiworld/AppApi?color=%2356d364)](https://img.shields.io/github/issues-closed-raw/Sebiworld/AppApi?color=%2356d364) [![Current Version](https://img.shields.io/github/issues-raw/Sebiworld/AppApi)](https://img.shields.io/github/issues-raw/Sebiworld/AppApi)

<a href="https://www.buymeacoffee.com/Sebi.dev" target="_blank"><img src="https://cdn.buymeacoffee.com/buttons/default-orange.png" alt="Buy Me A Coffee" height="41" width="174"></a>

| | |
| ------------------: | -------------------------------------------------------------------------- |
| ProcessWire-Module: | [https://modules.processwire.com/modules/app-api/](https://modules.processwire.com/modules/app-api/)                                                                    |
|      Support-Forum: | [https://processwire.com/talk/topic/24014-new-module-appapi/](https://processwire.com/talk/topic/24014-new-module-appapi/)                                                                      |
|         Repository: | [https://github.com/Sebiworld/AppApi](https://github.com/Sebiworld/AppApi) |
| Wiki: | [https://github.com/Sebiworld/AppApi/wiki](https://github.com/Sebiworld/AppApi/wiki) |
| | |

<a name="features"></a>

## Features

- **Simple routing definition**
- **Authentication** - Three different authentication-mechanisms are ready to use.
- **Access-management via UI**
- **Multiple different applications** with unique access-rights and authentication-mechanisms can be defined

## Table of Contents

- [1: Home, Installation & Quickstart](https://github.com/Sebiworld/AppApi/wiki)
- [2: Defining Applications](https://github.com/Sebiworld/AppApi/wiki/2.0:-Defining-Applications)
  - [2.1: Api-Keys](https://github.com/Sebiworld/AppApi/wiki/2.1:-Api-Keys)
  - [2.2: PHP-Session (Recommended for on-site usage)](https://github.com/Sebiworld/AppApi/wiki/2.2:-PHP-Session)
  - [2.3: Single JWT (Recommended for external server-calls)](https://github.com/Sebiworld/AppApi/wiki/2.3:-Single-JWT)
  - [2.4: Double JWT (Recommended for apps)](https://github.com/Sebiworld/AppApi/wiki/2.4:-Double-JWT)
- [3: Creating Endpoints](https://github.com/Sebiworld/AppApi/wiki/3.0:-Creating-Endpoints)
  - [3.1: Output Formatting](https://github.com/Sebiworld/AppApi/wiki/3.1:-Output-Formatting)
  - [3.2: Error Handling](https://github.com/Sebiworld/AppApi/wiki/3.2:-Error-Handling)
  - [3.3: Example: Listing Users](https://github.com/Sebiworld/AppApi/wiki/3.3:-Example:-Listing-Users)
  - [3.4: Example: Universal Twack Api](https://github.com/Sebiworld/AppApi/wiki/3.4:-Example:-Universal-Twack-Api)
    - [3.4.1: Routes](https://github.com/Sebiworld/AppApi/wiki/3.4:-Example:-Universal-Twack-Api#example2-routes)
    - [3.4.2: Page Handlers](https://github.com/Sebiworld/AppApi/wiki/3.4:-Example:-Universal-Twack-Api#example2-page-handlers)
    - [3.4.3: File Handlers](https://github.com/Sebiworld/AppApi/wiki/3.4:-Example:-Universal-Twack-Api#example2-file-handlers)

<a name="installation"></a>

## Installation

AppApi can be installed like every other module in ProcessWire. Check the following guide for detailed information: [How-To Install or Uninstall Modules](http://modules.processwire.com/install-uninstall/)

The prerequisites are **PHP>=7.2.0** and a **ProcessWire version >=3.93.0**. However, this is also checked during the installation of the module. No further dependencies.


<a name="changelog"></a>

## Changelog

### Changes in 1.1.5 (2021-03-13)
- Fixes a critical error that occured with ProcessWire versions >= 1.0.173 (thank you @csaggo.com and @psy for reporting it 🤗)

### Changes in 1.1.4 (2021-02-28)
- Fixes an issue with routes including query-parameters (by @twinklebob, thanks for PR 🤗)

### Changes in 1.1.3 (2021-02-09)

- Fixes an issue with the constructor signature of the modules AppApiException class (by @twinklebob, thanks for PR 🤗)
- Fixes an issue with the error-handler, which made it mistakenly catch errors that should have been ignored via @ operator (Thanks to @eelke)
- Switched from `wire('input')->url` to `$_SERVER['REQUEST_URI']` for reading the base-url, because ProcessWire's internal function transferred everything to lowercase (Thanks to @pauldro)

### Changes in 1.1.2 (2021-01-18)

- Fixes an error that occurred when something other than an array was to be output as response

### Changes in 1.1.1 (2021-01-13)

- Fixes critical issue "incorrect integer value" that happened in some db-configurations

### Changes in 1.1.0 (2021-01-03)

- Improved AppApi-dashboard
- Allow multiple levels to routing config (by @twinklebob, thanks for PR 🤗)
- Allow requests without an api-key: You can now mark an application as "default application". If you did so, every request without an apikey will be linked with that application.
- You can now set a custom response-code in case of success. Simply include your response-code number on key "responseCode" in your result-array.
- Optional access-logging: You can enable access-logging in the module's configuration. After that, every successful request will be logged with it's application-id, apikey-id and token-id.
- Added hooks to all essential functions - that should enable you to customize the module's behavior even more. E.g. you could add custom logging on a hook, if you need that
- Database-scheme does not need foreign-key constraints any more. That should fix @thomasaull 's issue with db-backups. After the update, you must remove the constraint manually because I did not find a way to remove the foreign key safely in all database-environments.
- Multiple other bugfixes

### Changes in 1.0.4 (2020-08-17)

- Usage of `ProcessPageView::pageNotFound`-hook to allow other modules and functions to initialize before the api renders.
- The path to `Routes.php` is now configurable via module's settings
(Thanks to @thomasaull and @spoetnik)

### Changes in 1.0.3 (2020-08-08)

- Bugfix in Router.php (Thanks to @thomasaull)

### Changes in 1.0.2 (2020-07-25)

- Documentation improvements

### Changes in 1.0.1 (2020-07-11)

- Changed all auth-routes to the /auth (or /auth/access) endpoints.
- Updated readme and examples

### Changes in 1.0.0 (2019-08-19)

- Rewritten most of the code
- Setup application management ui
- New authentication methods

<a name="versioning"></a>

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/Sebiworld/AppApi/tags).

<a name="license"></a>

## License

This project is licensed under the Mozilla Public License Version 2.0 - see the [LICENSE.md](LICENSE.md) file for details.

***


[**:arrow_right: Continue with 2: Defining Applications**](https://github.com/Sebiworld/AppApi/wiki/2.0:-Defining-Applications)
