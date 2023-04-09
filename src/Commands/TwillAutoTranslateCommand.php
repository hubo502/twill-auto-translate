<?php

namespace Xdarko\TwillAutoTranslate\Commands;

use Illuminate\Console\Command;

class TwillAutoTranslateCommand extends Command
{
    public $signature = 'twill-auto-translate';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
