<?php

namespace ProcessWire;

$info = array(
    'title' => 'AppApi',
    'summary' => 'Module to create a REST API with ProcessWire',
    'version' => '1.1.0',
    'author' => 'Sebastian Schendel',
    'icon' => 'terminal',
    'href' => 'https://modules.processwire.com/modules/app-api/',
    'requires'=> [
        'PHP>=7.2.0',
        'ProcessWire>=3.0.98'
    ],

    'autoload' => true,
    'permissions' => array(
        'appapi_manage_applications' => 'Manage AppApi settings'
    ),
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
