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
use Magento\Framework\Exception\LocalizedException;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\Product\BuildChildProductInfo;
use SearchSpring\Feed\Model\Feed\DataProvider\Product\GetChildProductsData;
use SearchSpring\Feed\Model\Feed\DataProviderInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\Configurable\DataProvider;

class ConfigurableProductsProvider implements DataProviderInterface
{
    /**
     * @var GetChildProductsData
     */
    private $getChildProductsData;

    /**
     * @var DataProvider
     */
    private $provider;

    /**
     * @var BuildChildProductInfo
     */
    private $buildChildProductInfo;

    /**
     * @param GetChildProductsData $getChildProductsData
     * @param DataProvider $provider
     * @param BuildChildProductInfo $buildChildProductInfo
     */
    public function __construct(
        GetChildProductsData $getChildProductsData,
        DataProvider $provider,
        BuildChildProductInfo $buildChildProductInfo
    ) {
        $this->getChildProductsData = $getChildProductsData;
        $this->provider = $provider;
        $this->buildChildProductInfo = $buildChildProductInfo;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws LocalizedException
     */
    public function getData(array $products, FeedSpecificationInterface $feedSpecification): array
    {
        $configurableProducts = $this->provider->getConfigurableProducts($products);

        if (empty($configurableProducts)) {
            return $products;
        }

        $childProducts = $this->provider->getAllChildProducts($products, $feedSpecification);
        $configurableAttributes =
            $this->provider->getConfigurableAttributes($configurableProducts, $feedSpecification);

        if (empty($configurableAttributes)) {
            return $products;
        }

        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            $id = $productModel->getData($this->provider->getLinkField());
            if (!isset($childProducts[$id]) || !isset($configurableAttributes[$id])) {
                continue;
            }

            $product = array_merge(
                $product,
                $this->getChildProductsData->getProductData(
                    $product,
                    $childProducts[$id],
                    $configurableAttributes[$id],
                    $feedSpecification
                )
            );
            $product = array_merge(
                $product,
                $this->buildChildProductInfo->execute(
                    $product,
                    $childProducts[$id],
                    $configurableAttributes[$id],
                    $feedSpecification
                )
            );
        }

        return $products;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->provider->reset();
    }

    /**
     *
     */
    public function resetAfterFetchItems(): void
    {
        $this->provider->resetAfterFetchItems();
    }
}
