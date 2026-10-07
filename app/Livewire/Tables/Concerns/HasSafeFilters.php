<?php

declare(strict_types=1);

namespace App\Livewire\Tables\Concerns;

use Exception;

trait HasSafeFilters
{
    /**
     * Safely clear filters avoiding PHP 8.4 TypeError on null nested array keys.
     *
     * @throws Exception
     */
    public function clearFilter(string $field = ''): void
    {
        collect($this->filters())
            ->each(function ($filter) use ($field) {
                $extraFieldsToClear = [];

                if (! empty($this->filters['number'])) {
                    $numberField = str($field)->beforeLast('_start')->beforeLast('_end')->append('')->toString();

                    if (isset($this->filters['number'][$numberField])) {
                        $field = $numberField;
                        $extraFieldsToClear = [$numberField.'_start', $numberField.'_end'];
                    }
                }

                if (data_get($this->filters, "multi_select.{$field}")) {
                    $this->dispatch('pg:clear_multi_select::'.$this->tableName.':'.$field);
                }

                if (isset($this->filters['datetime'][$field]) || isset($this->filters['date'][$field])) {
                    $this->dispatch('pg:clear_flatpickr::'.$this->tableName.':'.$field);
                }

                $unset = function ($filter, $field, $column) {
                    $key = data_get($filter, 'key');

                    if ($key && isset($this->filters[$key]) && is_array($this->filters[$key])) {
                        if (str($field)->contains('.')) {
                            $explodeField = explode('.', $field);
                            $parent = $explodeField[0];
                            $child = $explodeField[1] ?? '';

                            if (isset($this->filters[$key][$parent]) && is_array($this->filters[$key][$parent])) {
                                unset($this->filters[$key][$parent][$child]);

                                if (empty($this->filters[$key][$parent])) {
                                    unset($this->filters[$key][$parent]);
                                }
                            }
                        }

                        if (isset($this->filters[$key][$field])) {
                            unset($this->filters[$key][$field]);
                        }

                        if (empty($this->filters[$key])) {
                            unset($this->filters[$key]);
                        }
                    }

                    $this->enabledFilters = array_filter(
                        $this->enabledFilters,
                        fn ($f) => $f['field'] !== ($column ?? $field)
                    );
                };

                $column = data_get($filter, 'column');
                $filterField = data_get($filter, 'field');

                if ($field === $column) {
                    $unset($filter, $filterField, $field);
                } elseif ($field === $filterField) {
                    $unset($filter, $field, null);

                    foreach ($extraFieldsToClear as $fieldToClear) {
                        $unset($filter, $fieldToClear, null);
                    }
                }
            });

        if ($this->emitClearFiltersEvent) {
            $this->dispatch('pg:events', ['event' => 'clearFilters', 'field' => $field, 'tableName' => $this->tableName]);
        }

        $this->persistState('filters');
    }
}
