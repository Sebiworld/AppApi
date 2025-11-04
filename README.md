**Connect your apps to ProcessWire CMS!**

This module helps you to create an api, to which an app or an external service can connect to.

![A screen with the open AppApi module page](https://backend.sebi.dev/site/assets/files/1476/imac-and-ipad-on-top-of-a-white-desk-mockup-b4767.0x260.png)

**A special thanks goes to [Thomas Aull](https://github.com/thomasaull)** , whose module [RestApi](https://modules.processwire.com/modules/rest-api/) was the starting point to this project.

**Credits:** go to [Benjamin Milde](https://github.com/LostKobrakai) for his code example on how to use FastRoute with ProcessWire and [Camilo Castro](https://gist.github.com/clsource) for this [Gist](https://gist.github.com/clsource/dc7be74afcbfc5fe752c)

[![Current Version](https://img.shields.io/github/v/tag/Sebiworld/AppApi?label=Current%20Version)](https://img.shields.io/github/v/tag/Sebiworld/AppApi?label=Current%20Version) [![Current Version](https://img.shields.io/github/issues-closed-raw/Sebiworld/AppApi?color=%2356d364)](https://img.shields.io/github/issues-closed-raw/Sebiworld/AppApi?color=%2356d364) [![Current Version](https://img.shields.io/github/issues-raw/Sebiworld/AppApi)](https://img.shields.io/github/issues-raw/Sebiworld/AppApi)

<a href='https://ko-fi.com/E1E2LOEPF' target='_blank'><img height='36' style='border:0px;height:36px;' src='https://storage.ko-fi.com/cdn/kofi3.png?v=3' border='0' alt='Buy Me a Coffee at ko-fi.com' /></a>

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
  - [FAQ](https://github.com/Sebiworld/AppApi/wiki/FAQ)
  - [Changelog](https://github.com/Sebiworld/AppApi/wiki/Changelog)
  - [Module Configuration](https://github.com/Sebiworld/AppApi/wiki/Module-Configuration)
- [2: Defining Applications](https://github.com/Sebiworld/AppApi/wiki/2.0:-Defining-Applications)
  - [2.1: Api-Keys](https://github.com/Sebiworld/AppApi/wiki/2.1:-Api-Keys)
  - [2.2: PHP-Session (Recommended for on-site usage)](https://github.com/Sebiworld/AppApi/wiki/2.2:-PHP-Session)
  - [2.3: Single JWT (Recommended for external server-calls)](https://github.com/Sebiworld/AppApi/wiki/2.3:-Single-JWT)
  - [2.4: Double JWT (Recommended for apps)](https://github.com/Sebiworld/AppApi/wiki/2.4:-Double-JWT)
- [3: Creating Endpoints](https://github.com/Sebiworld/AppApi/wiki/3.0:-Creating-Endpoints)
  - [3.1: Output Formatting](https://github.com/Sebiworld/AppApi/wiki/3.1:-Output-Formatting)
  - [3.2: Error Handling](https://github.com/Sebiworld/AppApi/wiki/3.2:-Error-Handling)
  - [3.3: Example: Listing Users](https://github.com/Sebiworld/AppApi/wiki/3.3:-Example:-Listing-Users)
  - [3.4: Add Documentation](https://github.com/Sebiworld/AppApi/wiki/3.4:-Add-Documentation)
- [4: AppApi Modules](https://github.com/Sebiworld/AppApi/wiki/4.0:-AppApi-Modules)

<a name="installation"></a>

## Installation

AppApi can be installed like every other module in ProcessWire. Check the following guide for detailed information: [How-To Install or Uninstall Modules](http://modules.processwire.com/install-uninstall/)

The prerequisites are **PHP>=7.2.0** and a **ProcessWire version >=3.93.0**. However, this is also checked during the installation of the module. No further dependencies.

<a name="faq"></a>

## FAQ

Are you having problems or just don't know what to do? Take a look at the [frequently asked questions](https://github.com/Sebiworld/AppApi/wiki/FAQ)! Many questions have already been answered in the ProcessWire forum, and of course someone there is always happy to help.

<a name="app-api-modules"></a>

## AppApi Modules

Since version 1.2.0 it is possible to install AppApi modules that provide their own route handlers without having to change anything in Routes.php.

[Here](https://github.com/Sebiworld/AppApi/wiki/4.0:-AppApi-Modules) is a list of the currently available AppApi modules.

<a name="changelog"></a>

## Changelog

A detailed description of the changes per version can be found here: [**Changelog**](https://github.com/Sebiworld/AppApi/wiki/Changelog)

<a name="versioning"></a>

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/Sebiworld/AppApi/tags).

<a name="license"></a>

## License

This project is licensed under the Mozilla Public License Version 2.0 - see the [LICENSE.md](LICENSE.md) file for details.

***


[**:arrow_right: Continue with 2: Defining Applications**](https://github.com/Sebiworld/AppApi/wiki/2.0:-Defining-Applications)
