<?php
/**
 * Copyright (C) 2023 Searchspring <https://searchspring.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace SearchSpring\Feed\Model\Feed\DataProvider\Configurable;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Psr\Log\LoggerInterface;

class GetChildCollection
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;
    /**
     * @var Status
     */
    private $status;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetChildCollection constructor.
     * @param CollectionFactory $collectionFactory
     * @param Status $status
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        Status $status,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->status = $status;
        $this->logger = $logger;
    }

    /**
     * @param Product[] $parentProducts
     * @param string[] $attributeCodes
     *
     * @return Collection
     */
    public function execute(
        array $parentProducts,
        array $attributeCodes = []
    ): Collection {
        $collection = $this->collectionFactory->create();

        $parentIds = [];
        $duplicateParentIds = [];
        $seenParentIds = [];

        foreach ($parentProducts as $parentProduct) {
            $parentProductId = $parentProduct->getId();
            if (in_array($parentProductId, $seenParentIds)) {
                $duplicateParentIds[] = $parentProductId;
            } else {
                $seenParentIds[] = $parentProductId;
                $parentIds[] = $parentProductId;
            }
        }

        if (!empty($duplicateParentIds)) {
            $this->logger->warning(
                'Duplicate parent IDs found, keeps only unique IDs for child product collection.',
                [
                    'method' => __METHOD__,
                    'parentIds' => $parentIds,
                    'duplicateParentIds' => $duplicateParentIds,
                ]
            );
        }

        $parentIds = array_unique($parentIds);

        $defaultAttributes = [
            ProductInterface::STATUS,
            ProductInterface::SKU,
            ProductInterface::NAME,
            'special_price',
            'special_to_date',
            'special_from_date',
        ];

        $attributeCodes = array_unique(
            array_merge($attributeCodes, $defaultAttributes)
        );

        $collection->addAttributeToSelect($attributeCodes);
        $collection->addAttributeToFilter(
            ProductInterface::STATUS,
            ['in' => $this->status->getVisibleStatusIds()]
        );
        $collection->getSelect()
            ->join(
                [
                    'sl' => $collection->getTable('catalog_product_super_link'),
                ],
                'e.entity_id = sl.product_id',
                []
            )
            ->where('sl.parent_id IN (?)', $parentIds);
        $collection->addPriceData();
        $collection->getSelect()->columns(['parent_id' => 'sl.parent_id']);
        $collection->load();

        $this->logger->debug(
            'ConfigurableChildEntityCollectionProvider',
            [
                'method' => __METHOD__,
                'message' =>
                    sprintf(
                        'Query: %s',
                        $collection->getSelect()->__toString()
                    ),
                'parentIds' => $parentIds,
                'attributeCodes' => $attributeCodes,
            ]
        );

        return $collection;
    }
}
