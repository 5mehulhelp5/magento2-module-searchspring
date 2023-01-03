<?php

declare(strict_types=1);

namespace SearchSpring\Feed\Model\Feed\DataProvider;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\LayoutInterface;
use Magento\Swatches\Block\Product\Renderer\Configurable as SwatchesConfigurable;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Model\Feed\DataProviderInterface;
use SearchSpring\Feed\Model\Product\Configurable\Provider;

class JsonConfigProvider implements DataProviderInterface
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var Configurable
     */
    private $configurableBlock = null;

    /**
     * @var SwatchesConfigurable
     */
    private $swatchesBlock = null;

    /**
     * @var Provider
     */
    private $provider;

    /**
     * @param LayoutInterface $layout
     * @param Provider $provider
     */
    public function __construct(
        LayoutInterface $layout,
        Provider $provider
    ) {
        $this->layout = $layout;
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
        if (!$feedSpecification->getIncludeJSONConfig()) {
            return $products;
        }

        $childProducts = $this->provider->getAllChildProducts($products, $feedSpecification);
        $ignoredFields = $feedSpecification->getIgnoreFields();

        foreach ($products as &$product) {
            /** @var Product $productModel */
            $productModel = $product['product_model'] ?? null;
            if (!$productModel) {
                continue;
            }

            if (ConfigurableType::TYPE_CODE === $productModel->getTypeId()) {
                if (!in_array('json_config', $ignoredFields)) {
                    $configurableBlock = $this->getConfigurableBlock();
                    $configurableBlock->unsetData();
                    $configurableBlock->setProduct($productModel)
                        ->setAllowProducts($childProducts[$productModel->getId()] ?? []);
                    $product['json_config'] = $configurableBlock->getJsonConfig();
                }

                if (!in_array('swatch_json_config', $ignoredFields)) {
                    $swatchesBlock = $this->getSwatchesBlock();
                    $swatchesBlock->unsetData();
                    $swatchesBlock->setProduct($productModel)
                        ->setAllowProducts($childProducts[$productModel->getId()] ?? []);
                    $product['swatch_json_config'] = $swatchesBlock->getJsonSwatchConfig();
                }
            }
        }

        return $products;
    }

    /**
     * @return Configurable
     */
    private function getConfigurableBlock() : Configurable
    {
        if (!$this->configurableBlock) {
            $this->configurableBlock = $this->layout->createBlock(Configurable::class);
        }

        return $this->configurableBlock;
    }

    /**
     * @return SwatchesConfigurable
     */
    private function getSwatchesBlock() : SwatchesConfigurable
    {
        if (!$this->swatchesBlock) {
            $this->swatchesBlock = $this->layout->createBlock(SwatchesConfigurable::class);
        }

        return $this->swatchesBlock;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->configurableBlock = null;
        $this->swatchesBlock = null;
    }
}
