<?php

namespace SearchSpring\Feed\Api;

interface CategoryInfoInterface
{
    /**
     * Get details of all categories in the store.
     *
     * @param bool $activeOnly
     * @param string $delimiter
     * @return array
     */
    public function getAllCategories(bool $activeOnly = true, string $delimiter = '>'): array;
}
