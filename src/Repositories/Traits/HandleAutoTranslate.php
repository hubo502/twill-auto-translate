<?php

namespace Xdarko\TwillAutoTranslate\Repositories\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Xdarko\TwillAutoTranslate\TwillAutoTranslate;

trait HandleAutoTranslate
{
    public function autoTranslateModels($forceTranslate = false, $activeTranslate = true)
    {
        $models = $this->model->get();

        if ($this->hasBehavior('blocks')) {
            $models->load('blocks');
        }

        $models->each(function ($model) use ($forceTranslate, $activeTranslate) {
            $this->translateModel($model, $forceTranslate, $activeTranslate);
        });
    }

    protected function translateModel($model, $force = false, $publish = true)
    {
        if ($model->isTranslatable()) {

            $defaultLocale = app(TwillAutoTranslate::class)->getDefaultLocale();
            $baseValues = $model->translate($defaultLocale);
            $translatedAttributes = $model->translatedAttributes;

            $locales = collect(app(TwillAutoTranslate::class)->getLocales())->filter(function ($locale) use ($defaultLocale) {
                return $locale !== $defaultLocale;
            })->toArray();

            $translated = collect($locales)->mapWithKeys(function ($locale) use ($translatedAttributes, $baseValues, $defaultLocale, $model, $force, $publish) {
                $result = collect($translatedAttributes)->mapWithKeys(function ($attr) use ($baseValues, $locale, $defaultLocale, $model, $force, $publish) {

                    if ($attr == 'active') {
                        return [$attr => $publish ? 1 : 0];
                    }

                    $modelTranslated = $model->translate($locale);
                    $old_result = $modelTranslated?->$attr;

                    $string = $baseValues->$attr;
                    $base_locale = $defaultLocale;
                    $translate_locale = $locale;

                    if (!$string) {
                        $result = $string;
                    } else {
                        $result = ($old_result && !$force)
                            ? $old_result
                            : app(TwillAutoTranslate::class)->translateString($string, $translate_locale, $base_locale);
                    }
                    return [$attr => $result];
                })->toArray();
                return [$locale => $result];
            })->toArray();


            $model->blocks?->each(function ($block) use ($force) {
                $content = $this->translateBlockContent($block->content, $force);
                $block->fill(compact('content'));
                $block->save();
            });

            $model->fill(Arr::except($translated, $this->getReservedFields()));
            $model->save();
        }
    }

    public function prepareFieldsBeforeSaveHandleAutoTranslate($object, $fields)
    {
        return $this->autoTranslateInputs($object, $fields);
    }

    protected function autoTranslateInputs($object, $fields)
    {
        $configs = $this->getAutoTranslateConfigs($fields);

        $enabled = $configs->get('auto_translate', false);
        $force = $configs->get('force_translate', false);
        $publish = $configs->get('publish_translate', false);

        if ($enabled) {
            $fields = $this->translateFields($object, $fields, $force, $publish);
            $fields = $this->translateBlocks($fields, $force);
        }

        return $fields;
    }

    protected function translateFields($object, $fields, $force = false, $publish = false): array
    {
        collect($object->translatedAttributes)->each(function ($attribute) use (&$fields, $force, $publish) {
            $field = $fields[$attribute] ?? null;
            $fields[$attribute] = $attribute == 'active' ? $this->publishTranslation($field, $publish)->toArray() : app(TwillAutoTranslate::class)->translateAttribute(collect($field), $force)->toArray();
        });

        return $fields;
    }

    protected function publishTranslation($field, $publish = true): Collection
    {
        return collect($field)->transform(function ($value, $locale) use ($publish) {
            return app(TwillAutoTranslate::class)->isDefaultLocale($locale) ? $value : ($publish ? 1 : 0);
        });
    }

    protected function translateBlockContent($content, $force): array
    {
        return collect($content)->transform(function ($field) use ($force) {
            return $this->blockFieldTranslatable($field) ? app(TwillAutoTranslate::class)->translateAttribute($this->formatLocalizedField($field), $force)->toArray() : $field;
        })->toArray();
    }

    protected function translateBlocks($fields, $force = false)
    {
        $fields['blocks'] = collect($fields['blocks'])->transform(function ($block) use ($force) {
            $block['content'] = $this->translateBlockContent($block['content'], $force);

            if (count($block['blocks'])) {
                $block['blocks'] = $this->translateNestedBlocks($block['blocks'], $force);
            }

            return $block;
        })->toArray();

        return $fields;
    }

    protected function translateNestedBlocks($blocks, $force = false): array
    {
        return collect($blocks)->transform(function ($childBlocks, $type) use ($force) {
            return collect($childBlocks)->transform(function ($child) use ($force) {

                $child['content'] = collect($child['content'])->transform(function ($field, $key) use ($force) {
                    return $this->blockFieldTranslatable($field) ? app(TwillAutoTranslate::class)->translateAttribute($this->formatLocalizedField($field), $force)->toArray() : $field;
                })->toArray();

                return $child;
            })->toArray();
        })->toArray();
    }

    protected function blockFieldTranslatable($field)
    {
        $defaultLocale = app(TwillAutoTranslate::class)->getDefaultLocale();
        return isset($field[$defaultLocale]);
    }

    protected function formatLocalizedField($field)
    {
        $locales = app(TwillAutoTranslate::class)->getLocales();
        return collect($locales)->mapWithKeys(function ($locale) use ($field) {
            return [$locale => $field[$locale] ?? null];
        });
    }

    protected function getAutoTranslateConfigs($fields): Collection
    {
        return collect($fields)->only(['auto_translate', 'force_translate', 'active_translate']);
    }
}
