<?php

declare(strict_types=1);

describe('DbMigrateCommand Integration Tests', function (): void {
    it('is registered in the console kernel', function (): void {
        // ARRANGE & ACT
        $commands = app()->make(\Illuminate\Contracts\Console\Kernel::class)->all();

        // ASSERT
        expect($commands)->toHaveKey('db:migrate');
    });
});
