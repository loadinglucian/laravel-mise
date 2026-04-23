<?php

declare(strict_types=1);

return [
    'require' => [
        'livewire/flux' => [
            'requires' => 'livewire/livewire',
            'post_payload_commands' => [
                ['php', 'artisan', 'flux:activate'],
            ],
        ],
        'spatie/laravel-data' => [
            'commands' => [
                ['php', 'artisan', 'vendor:publish', '--provider=Spatie\LaravelData\LaravelDataServiceProvider', '--tag=data-config'],
            ],
        ],
    ],
    'require-dev' => [
        'loadinglucian/laravel-mise:dev-main' => [
            'composer' => [
                'scripts' => [
                    'pall' => [
                        'composer pint && composer pstan && composer rect && composer pest',
                    ],
                    'pest' => [
                        'vendor/bin/pest --parallel --coverage',
                    ],
                    'pint' => [
                        'vendor/bin/pint --parallel',
                    ],
                    'pstan' => [
                        'vendor/bin/phpstan analyse --memory-limit=2G',
                    ],
                    'rect' => [
                        'vendor/bin/rector process',
                    ],
                ],
            ],
        ],
        'loadinglucian/deployer-php' => [
            'commands' => [
                ['vendor/bin/deployer', 'scaffold:ai', '--agent=claude'],
                ['vendor/bin/deployer', 'scaffold:crons'],
                ['vendor/bin/deployer', 'scaffold:hooks'],
                ['vendor/bin/deployer', 'scaffold:supervisors'],
            ],
            'command_options' => ['destination', 'force'],
        ],
        'barryvdh/laravel-ide-helper' => [
            'composer' => [
                'scripts' => [
                    'post-update-cmd' => [
                        'Illuminate\\Foundation\\ComposerScripts::postUpdate',
                        '@php artisan ide-helper:generate',
                        '@php artisan ide-helper:meta',
                        '@php artisan ide-helper:models --nowrite',
                    ],
                ],
            ],
            'commands' => [
                ['php', 'artisan', 'ide-helper:generate'],
                ['php', 'artisan', 'ide-helper:meta'],
                ['php', 'artisan', 'ide-helper:models', '--nowrite'],
            ],
        ],
        'beyondcode/laravel-query-detector' => [
        ],
        'rector/rector' => [
            'post_payload_commands' => [
                ['vendor/bin/rector'],
            ],
            'command_options' => ['ignore_errors'],
        ],
        'laravel/pint' => [
            'post_payload_commands' => [
                ['vendor/bin/pint', '--repair'],
            ],
            'command_options' => ['ignore_errors'],
        ],
        'larastan/larastan' => [
            'post_payload_commands' => [
                ['vendor/bin/phpstan', 'analyse'],
            ],
            'command_options' => ['ignore_errors'],
        ],
        'pestphp/pest',
        'pestphp/pest-plugin-arch',
        'roave/security-advisories:dev-latest',
    ],
];
