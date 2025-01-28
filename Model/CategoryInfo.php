<?php

namespace SearchSpring\Feed\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use SearchSpring\Feed\Api\CategoryInfoInterface as CategoryInfoApi;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;

class CategoryInfo implements CategoryInfoApi
{
    protected $categoryRepository;
    protected $categoryFactory;
    protected $categoryCollectionFactory;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        CategoryFactory             $categoryFactory,
        CategoryCollectionFactory   $categoryCollectionFactory,
    )
    {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * @param bool $activeOnly
     * @param string $delimiter
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAllCategories(bool $activeOnly = true, string $delimiter = '>'): array
    {
        $categories = [];

        // Get the category collection
        $categoryCollection = $this->categoryCollectionFactory->create();
        $categoryCollection->addAttributeToSelect('*'); // Load all attributes

        if ($activeOnly) {
            $categoryCollection->addIsActiveFilter(); // Only get active categories
        }

        // Loop through the categories and collect the relevant data
        foreach ($categoryCollection as $category) {
            $categories[] = [
                'ID' => $category->getId(),
                'Name' => $category->getName(),
                'PageLink' => $category->getUrl(),
                'ImageLink' => $category->getImageUrl(),
                'ParentId' => $category->getParentId(),
                'DisplayName' => $category->getName(),
                'FullHierarchy' => $this->getFullCategoryHierarchy($category, $delimiter),
                'NumProducts' => $category->getProductCount(),
            ];
        }

        return $categories;
    }

    /**
     * Get full hierarchy of a category
     *
     * @param Category $category
     * @param string $delimiter
     * @return string
     * @throws NoSuchEntityException
     */
    private function getFullCategoryHierarchy(Category $category, string $delimiter): string
    {
        $pathIds = $category->getPathIds();
        $categoryHierarchy = [];

        foreach ($pathIds as $pathId) {
            $categoryEntity = $this->categoryRepository->get($pathId);
            $categoryHierarchy[] = $categoryEntity->getName();
        }

        return implode($delimiter, $categoryHierarchy);
    }
}
