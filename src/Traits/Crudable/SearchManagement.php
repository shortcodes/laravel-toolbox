<?php

namespace Shortcodes\Toolbox\Traits\Crudable;

use Illuminate\Support\Collection;

trait SearchManagement
{
    public function search()
    {
        $object = new $this->model;

        if (method_exists($object, 'specificSearch')) {
            return $object->specificSearch();
        }

        return $this->eloquentSearch();
    }

    private function eloquentSearch()
    {
        $query = $this->model::query();

        $object = new $this->model;

        if (method_exists($object, 'scopeSearch')) {
            $query->search();
        }

        if (!$this->disableAutoSortResolving && request()->get('sort_by') && request()->get('sort_direction')) {
            $query->orderBy(request()->get('sort_by', 'id'), request()->get('sort_direction', 'desc'));
        }

        if (isset($this->shouldReturnQuery) && $this->shouldReturnQuery) {
            return $query;
        }

        if ((isset($this->pagination) && !$this->pagination ) || request()->get('pagination') === 'false') {
            return $query->get();
        }

        return $query->paginate(request()->get('length', 10), ['*'], 'page', request()->get('page', 0));
    }
}
