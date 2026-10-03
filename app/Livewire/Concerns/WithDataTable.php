<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

/**
 * Shared datatable behaviour for Livewire index screens.
 *
 * Components using this trait must implement:
 *  - sortableColumns(): array<string, string|Closure>  (whitelist: key => column or closure(Builder, direction))
 *  - filterProperties(): array<string, mixed>          (filter property => default value)
 *
 * Sorting is strictly whitelisted so a client-supplied sort key can never reach orderBy() directly.
 */
trait WithDataTable
{
    use WithPagination;

    /** @var list<int> */
    public array $perPageOptions = [10, 25, 50, 100];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'sort', except: '')]
    public string $sortBy = '';

    #[Url(as: 'dir', except: 'asc')]
    public string $sortDirection = 'asc';

    #[Url(as: 'per', except: 10)]
    public int $perPage = 10;

    /**
     * @return array<string, string|Closure>
     */
    abstract protected function sortableColumns(): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function filterProperties(): array;

    public function sort(string $column): void
    {
        if (! array_key_exists($column, $this->sortableColumns())) {
            return;
        }

        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        foreach ($this->filterProperties() as $property => $default) {
            $this->{$property} = $default;
        }

        $this->search = '';
        $this->resetPage();
    }

    /**
     * Livewire trait hook: reset pagination whenever a search/filter/page-size value changes.
     */
    public function updatedWithDataTable(string $property): void
    {
        if ($property === 'perPage' && ! in_array($this->perPage, $this->perPageOptions, true)) {
            $this->perPage = $this->perPageOptions[0];
        }

        if (in_array($property, ['search', 'perPage', ...array_keys($this->filterProperties())], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function activeFiltersCount(): int
    {
        $count = 0;

        foreach ($this->filterProperties() as $property => $default) {
            if ($this->{$property} !== $default) {
                $count++;
            }
        }

        return $count + ($this->search !== '' ? 1 : 0);
    }

    public function activeFilterCount(): int
    {
        return $this->activeFiltersCount();
    }

    /**
     * Apply whitelisted sorting; falls back to the given default ordering.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function applySorting(Builder $query, string $defaultColumn, string $defaultDirection = 'asc'): Builder
    {
        $columns = $this->sortableColumns();
        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';

        if ($this->sortBy === '' || ! array_key_exists($this->sortBy, $columns)) {
            return $query->orderBy($defaultColumn, $defaultDirection);
        }

        $target = $columns[$this->sortBy];

        if ($target instanceof Closure) {
            $target($query, $direction);

            return $query;
        }

        return $query->orderBy($target, $direction);
    }

    protected function perPageValue(): int
    {
        return in_array($this->perPage, $this->perPageOptions, true) ? $this->perPage : $this->perPageOptions[0];
    }
}
