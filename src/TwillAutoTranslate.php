<?php

namespace Xdarko\TwillAutoTranslate;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TwillAutoTranslate
{
    protected $defaultLocale;

    protected $locales;

    public function __construct()
    {
        $this->defaultLocale = config('app.fallback_locale', 'en');
        $this->locales = config('translatable.locales', []);
    }

    /**
     * 可用的语言
     */
    public function getLocales(): array
    {
        return $this->locales;
    }

    /**
     * 默认语言
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * 翻译文本
     *
     * @param  string  $string 待翻译内容
     * @param  string  $google_translate_locale 要翻译成的语言
     * @param  string  $base_locale 待翻译内容的语言
     * @param  bool  $with_attributes 是否带参数
     */
    public function translateString(string $string, string $google_translate_locale, string $base_locale, bool $with_attributes = false): mixed
    {
        if (strlen($string) == 0) {
            return null;
        }

        try {
            return $with_attributes ?
                Str::apiTranslateWithAttributes($string, $google_translate_locale, $base_locale) :
                Str::apiTranslate($string, $google_translate_locale, $base_locale);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 翻译字段组
     *
     * @param  Collection  $attribute 待翻译内容 {en:"xxx","ja":xxx}
     * @param  bool  $forceTranslate 是否强制翻译
     */
    public function translateAttribute(Collection $attribute, bool $forceTranslate = false): Collection
    {
        $base = $attribute->get($this->defaultLocale, null);

        if (! $base) {
            return $attribute;
        }

        return $attribute->transform(function ($string, $locale) use ($forceTranslate, $base) {
            return $this->shouldTranslateAttribute($locale, $string, $forceTranslate)
                ? $this->translateString($base, $locale, $this->defaultLocale, $with_attributes = false)
                : $string;
        });
    }

    /**
     * 判断是否需要翻译该字段
     */
    protected function shouldTranslateAttribute($locale, $string, bool $forceTranslate = false): bool
    {
        return (! $this->isDefaultLocale($locale)) && ($forceTranslate || is_null($string));
    }

    protected function isDefaultLocale($locale): bool
    {
        return $this->defaultLocale === $locale;
    }
}
