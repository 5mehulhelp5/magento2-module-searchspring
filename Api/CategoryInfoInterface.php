<?php

namespace SearchSpring\Feed\Api;

interface CategoryInfoInterface
{
    /**
     * Get details of all categories in the store.
     *
     * @return array
     */
    public function getAllCategories(): array;
}
