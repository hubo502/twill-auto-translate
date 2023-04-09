<?php

namespace Xdarko\TwillAutoTranslate\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Xdarko\TwillAutoTranslate\TwillAutoTranslate
 */
class TwillAutoTranslate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Xdarko\TwillAutoTranslate\TwillAutoTranslate::class;
    }
}
