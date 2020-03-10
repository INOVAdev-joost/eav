<?php

namespace Eav\Traits;

use Cache;
use Eav\Attribute\Collection;
use Illuminate\Support\Arr;

trait Attribute
{
    protected static $attributesCollection = [];

    protected static $attributesCollectionKeys = [];

    /**
     * Reset static arrays to empty.
     * While in a testing environment these static 'caches' need to be removed to prevent scenario's where attributes with same
     * attribute_code would result in different outcome when next test runs.
     * This reset method can be used in the teardown() method to reset the attributes and fixed this caching between test jobs.
     */
    public static function reset()
    {
        self::$attributesCollection = [];
        self::$attributesCollectionKeys = [];
    }

    public function loadAttributes($attributes = [], $static = false, $required = false)
    {
        $attributes = collect($attributes)->unique();
        $code = $this->baseEntity()->code();

        if ($attributes->isEmpty()) {
            $this->saveAttribute(
                $this->fetchAttributes([], $static, $required)
            );
        } else {
            $newAttribute = $attributes->diff(Arr::get(static::$attributesCollectionKeys, $code, []));
            if ($newAttribute->isNotEmpty()) {
                $this->saveAttribute(
                    $this->fetchAttributes($newAttribute->all(), $static, $required)
                );
            }
        }

        if ($attributes->isEmpty()) {
            return static::$attributesCollection[$code];
        }
        return static::$attributesCollection[$code]->only($attributes->all())->patch();
    }

    protected function saveAttribute(Collection $loadedAttributes)
    {
        $code = $this->baseEntity()->code();
        if (!isset(static::$attributesCollection[$code])) {
            static::$attributesCollection[$code] = $loadedAttributes;
        } else {
            static::$attributesCollection[$code] = static::$attributesCollection[$code]->merge($loadedAttributes);
        }

        static::$attributesCollectionKeys[$code] = static::$attributesCollection[$code]->code()->toArray();
    }

    protected function fetchAttributes($attributes = [], $static = false, $required = false)
    {
        $query = $this->baseEntity()
            ->attributes()
            ->where(function ($query) use ($static, $required, $attributes) {
                if (!empty($attributes)) {
                    $query->orWhereIn('attribute_code', $attributes);
                }

                if ($static) {
                    $query->orWhere('backend_type', 'static');
                }
                if ($required) {
                    $query->orWhere('is_required', 1);
                }
            });
        $cacheKey = md5(implode('|', $attributes)."|{$this->baseEntity()->getCode()}|{$query->toSql()}");
        return Cache::remember($cacheKey, 10, function () use ($query) {
            return $query->get()->patch();
        });
    }

    public function getMainTableAttribute($loadedAttributes)
    {
        $mainTableAttributeCollection = $loadedAttributes->filter(function ($attribute) {
            return $attribute->isStatic();
        });

        $mainTableAttribute = $mainTableAttributeCollection->code()->toArray();

        $mainTableAttribute[] = 'entity_id';
        $mainTableAttribute[] = 'attribute_set_id';

        return $mainTableAttribute;
    }
}
