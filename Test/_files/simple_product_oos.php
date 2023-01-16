<?php

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;

$storeManager = Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(StoreManagerInterface::class);
$product = Bootstrap::getObjectManager()->create(Product::class);
$productRepository = Bootstrap::getObjectManager()->get(ProductRepositoryInterface::class);
$product->setTypeId('simple')
    ->setAttributeSetId(4)
    ->setName('Simple OOS')
    ->setSku('searchspring_simple_oos')
    ->setPrice(10)
    ->setTaxClassId(0)
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(Visibility::VISIBILITY_BOTH)
    ->setStatus(Status::STATUS_ENABLED)
    ->setStockData(
        [
            'qty' => 100,
            'is_in_stock' => 0,
            'manage_stock' => 1,
        ]
    )
    ->setWebsiteIds([$storeManager->getStore()->getWebsiteId()])
    ->setData('boolean_attribute', true)
    ->setData('decimal_attribute', 50);
$productRepository->save($product);
