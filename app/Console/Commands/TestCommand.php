<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:test-command')]
#[Description('Command description')]
class TestCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle() // viene sempre richiamato quando viene eseguito il comando
    {
        echo "ciao\n";
        $this->test();
    }

    public function test() {
        echo "test";
    }
}
