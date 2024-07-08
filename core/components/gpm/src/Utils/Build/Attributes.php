<?php
namespace GPM\Utils\Build;

use xPDO\Transport\xPDOTransport;

class Attributes
{
    public static $category = [
        xPDOTransport::UNIQUE_KEY => 'category',
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => true,
        xPDOTransport::RELATED_OBJECTS => true,
        xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
            'Children' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => ['parent', 'category'],
            ],
            'Snippets' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
            ],
            'Chunks' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
            ],
            'Templates' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'templatename',
            ],
            'TemplateVars' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
            ],
            'PropertySets' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
            ],
            'Plugins' => [
                xPDOTransport::UNIQUE_KEY => 'name',
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::RELATED_OBJECTS => true,
                xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                    'PluginEvents' => [
                        xPDOTransport::PRESERVE_KEYS => true,
                        xPDOTransport::UPDATE_OBJECT => true,
                        xPDOTransport::UNIQUE_KEY => ['pluginid','event'],
                        xPDOTransport::RELATED_OBJECTS => true,
                        xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                            'PropertySet' => [
                                xPDOTransport::UNIQUE_KEY => 'name',
                                xPDOTransport::PRESERVE_KEYS => false,
                                xPDOTransport::UPDATE_OBJECT => false,
                            ]
                        ]
                    ],
                ],
            ]
        ]
    ];

    public static $menu = [
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => true,
        xPDOTransport::UNIQUE_KEY => 'text',
        xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
            'Children' => [
                xPDOTransport::PRESERVE_KEYS => true,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'text',
            ],
        ],
    ];

    public static $setting = [
        xPDOTransport::UNIQUE_KEY => 'key',
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => false,
    ];

    public static $widget = [
        xPDOTransport::UNIQUE_KEY => ['name', 'namespace'],
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => true,
    ];

    public static $fredTheme = [
        xPDOTransport::UNIQUE_KEY => 'uuid',
        xPDOTransport::UPDATE_OBJECT => true,
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::RELATED_OBJECTS => true,
        xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
            'ElementCategories' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'uuid',
                xPDOTransport::RELATED_OBJECTS => true,
                xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                    'Elements' => [
                        xPDOTransport::PRESERVE_KEYS => false,
                        xPDOTransport::UPDATE_OBJECT => true,
                        xPDOTransport::UNIQUE_KEY => 'uuid',
                        xPDOTransport::RELATED_OBJECTS => false,
                    ]
                ]
            ],
            'BlueprintCategories' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'uuid',
                xPDOTransport::RELATED_OBJECTS => true,
                xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                    'Blueprints' => [
                        xPDOTransport::PRESERVE_KEYS => false,
                        xPDOTransport::UPDATE_OBJECT => true,
                        xPDOTransport::UNIQUE_KEY => 'uuid'
                    ]
                ]
            ],
            'RTEConfigs' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => ['name', 'theme'],
            ],
            'OptionSets' => [
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => ['name', 'theme'],
            ]
        ]
    ];
}
