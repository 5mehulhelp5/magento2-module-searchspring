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

namespace SearchSpring\Feed\Model\Feed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Model\Feed\DataProviderInterface;

class ConfigurableChildProvider implements DataProviderInterface
{
    /**
     * @var Configurable
     */
    private $configurableType;

    /**
     * @param Configurable $configurableType
     */
    public function __construct(
        Configurable $configurableType,
    ) {
        $this->configurableType = $configurableType;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     * @return array
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $ignoredFields = $feedSpecification->getIgnoreFields();

        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }
            if ($productModel->getTypeId() !== Configurable::TYPE_CODE) {
                continue;
            }

            $childProducts = $this->configurableType->getUsedProducts($productModel);

            $configChildData = [
                'child_sku'  => [],
                'child_name' => [],
            ];

            foreach ($childProducts as $child) {
                if (!in_array('child_sku', $ignoredFields) && !empty($child->getSku())) {
                    $configChildData['child_sku'][] = $child->getSku();
                }

                if (!in_array('child_name', $ignoredFields) && !empty($child->getName())) {
                    $configChildData['child_name'][] = $child->getName();
                }
            }

            $product['config_child_data'] = $configChildData;
        }

        return $products;
    }

    /**
     *
     */
    public function reset(): void
    {
        // do nothing
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        // do nothing
    }
}
