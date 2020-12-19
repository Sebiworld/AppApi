<?php

namespace ProcessWire;

/**
 * ProcessHello.info.php
 *
 * Return information about this module.
 *
 * If preferred, you can use a getModuleInfo() method in your module file,
 * or you can use a ModuleName.info.json file (if you prefer JSON definition).
 *
 */

$info = array(
    // Your module's title
    'title' => 'AppApi',

    // A 1 sentence description of what your module does
    'summary' => 'Module to create a REST API with ProcessWire',

    // Module version number: use 1 for 0.0.1 or 100 for 1.0.0, and so on
    'version' => '1.0.4',

    // Name of person who created this module (change to your name)
    'author' => 'Sebastian Schendel',

    // Icon to accompany this module (optional), uses font-awesome icon names, minus the "fa-" part
    'icon' => 'terminal',

    // URL to more info: change to your full modules.processwire.com URL (if available), or something else if you prefer
    'href' => 'https://modules.processwire.com/modules/app-api/',

    'requires'=> [
        'PHP>=7.2.0',
        'ProcessWire>=3.0.98'
    ],

    'autoload' => true,
    'singular' => true,

    // permissions that you want automatically installed/uninstalled with this module (name => description)
    'permissions' => array(
        'appapi_manage_applications' => 'Manage AppApi settings'
    ),

    // page that you want created to execute this module
    'page' => array(
        'name'   => 'appapi',
        'parent' => 'setup',
        'title'  => 'AppApi',
        'icon'   => 'terminal'
    ),

    // optional extra navigation that appears in admin
    // if you change this, you'll need to a Modules > Refresh to see changes
    // 'nav' => array(
    //     array(
    //         'url'   => 'applications/',
    //         'label' => 'Applications',
    //         'icon'  => 'plug',
    //     ),
    // )

    // for more options that you may specify here, see the file: /wire/core/Process.php
    // and the file: /wire/core/Module.php
);
