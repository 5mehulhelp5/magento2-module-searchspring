<?php

declare(strict_types=1);

namespace SearchSpring\Feed\Model\Feed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\Product\GetChildProductsData;
use SearchSpring\Feed\Model\Feed\DataProviderInterface;
use SearchSpring\Feed\Model\Product\Configurable\Provider;

class ConfigurableProductsProvider implements DataProviderInterface
{
    /**
     * @var GetChildProductsData
     */
    private $getChildProductsData;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * @param GetChildProductsData $getChildProductsData
     * @param Provider $provider
     */
    public function __construct(
        GetChildProductsData $getChildProductsData,
        Provider $provider
    ) {
        $this->getChildProductsData = $getChildProductsData;
        $this->provider = $provider;
    }

    /**
     * @param array $products
     * @param FeedSpecificationInterface $feedSpecification
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
        }

        return $products;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->provider->resetStorages();
    }
}
