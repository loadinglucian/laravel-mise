<?php

return [
    'require' => [
        'livewire/livewire' => [
            'commands' => [
                ['php', 'artisan', 'livewire:publish', '--config'],
            ],
        ],
        'livewire/flux' => [
            'post_dist_commands' => [
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
        'bigpixelrocket/laravel-mise:dev-main' => [
            'composer' => [
                'scripts' => [
                    'changed-php-files' => [
                        './scripts/get-changed-php-files.sh',
                    ],
                    'changed-php-files:no-tests' => [
                        './scripts/get-changed-php-files.sh | grep -v "^tests/"',
                    ],
                    'pall' => [
                        'composer pest && composer rect && composer pstan && composer pint',
                    ],
                    'pest' => [
                        'vendor/bin/pest --parallel --coverage',
                    ],
                    'pint' => [
                        'bash -c "FILES=$(composer changed-php-files 2>/dev/null | tail -n +2); if [ -n \'$FILES\' ]; then echo \'$FILES\' | xargs -r ./vendor/bin/pint --parallel; else echo \'No PHP files changed\'; fi"',
                    ],
                    'pstan' => [
                        'bash -c "FILES=$(composer changed-php-files:no-tests 2>/dev/null | tail -n +2); if [ -n \'$FILES\' ]; then echo \'$FILES\' | xargs -r ./vendor/bin/phpstan analyse --memory-limit=2G --; else echo \'No non-test PHP files changed\'; fi"',
                    ],
                    'rect' => [
                        'bash -c "FILES=$(composer changed-php-files 2>/dev/null | tail -n +2); if [ -n \'$FILES\' ]; then echo \'$FILES\' | xargs -r ./vendor/bin/rector process; else echo \'No PHP files changed\'; fi"',
                    ],
                ],
            ],
        ],
        'barryvdh/laravel-debugbar' => [
            'commands' => [
                ['php', 'artisan', 'vendor:publish', '--provider=Barryvdh\Debugbar\ServiceProvider'],
            ],
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
            'post_dist_commands' => [
                ['vendor/bin/rector'],
            ],
        ],
        'laravel/pint' => [
            'post_dist_commands' => [
                ['vendor/bin/pint', '--repair'],
            ],
        ],
        'larastan/larastan' => [
            'post_dist_commands' => [
                ['vendor/bin/phpstan', 'analyse'],
            ],
        ],
        'pestphp/pest',
        'pestphp/pest-plugin-arch',
        'roave/security-advisories:dev-latest',
    ],
];
