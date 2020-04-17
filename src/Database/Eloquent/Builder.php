<?php
namespace Eav\Database\Eloquent;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class Builder extends EloquentBuilder
{
    public function baseEntity()
    {
        return $this->getModel()->baseEntity();
    }

    public function getFacets($count = false)
    {
        $baseEntity = $this->baseEntity();
        $filterable = $baseEntity
            ->attributes()
            ->with('optionValues')
            ->where('is_filterable', 1)
            ->get()
            ->patch();

        $baseQuery = (clone $this->query);

        return $filterable->map(function ($filter, $code) use ($count, $baseQuery) {
            $query = (clone $baseQuery);

            foreach ($this->query->attributeWheresRef as $column => $values) {
                if ($filter->getAttributeCode() == $column) {
                    continue;
                }

                foreach ($values as $value) {
                    $query->addWhereAttribute($column, $value);
                }
            }

            $query->select($filter->getAttributeCode())
                ->groupBy($filter->getAttributeCode());

            if ($count) {
                $query->selectRaw('count(1) as count');
            }

            $options = $filter->options();

            $result = [];

            return $query->get()->each(function ($option, $key) use ($filter, $options, $count) {
                $value = $option->{$filter->getAttributeCode()};
                if ($value === null) {
                    return null;
                }
                $data = [
                    'value' => $value,
                    'label' => isset($options[$value])?$options[$value]:$value
                ];

                if ($count) {
                    if (isset($result[$data['value']])) {
                        $data['count'] = $result[$data['value']]['count'] + $option->count;
                    } else {
                        $data['count'] = $option->count;
                    }
                }

                $result[$data['value']] = $data;
            });
            
            $result = array_filter($result);

            return collect($result);
        });
    }
}
