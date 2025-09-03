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

namespace SearchSpring\Feed\Model\Feed\DataProvider\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Exception\LocalizedException;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use SearchSpring\Feed\Model\Feed\DataProvider\Price\ProviderResolverInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\PricesProvider;
use SearchSpring\Feed\Service\Normalization\ValueNormalizerInterface;

class BuildChildProductInfo
{
    private const CHILD_KEY_NAME = 'child_info';

    /** price-like codes treated specially (provider/EAV) */
    private $priceAttrCodes = [
        'price',
        'special_price',
        'cost',
        'final_price',
        'minimal_price',
        'maximal_price',
    ];

    /** map attribute code => provider key */
    private $attrToProviderKey = [
        'final_price' => PricesProvider::FINAL_PRICE_KEY,
        'minimal_price' => PricesProvider::FINAL_PRICE_KEY,
        'maximal_price' => PricesProvider::MAX_PRICE_KEY,
        'price' => PricesProvider::REGULAR_PRICE_KEY,
    ];

    /**
     * @var bool
     */
    private $excludeDisabled;
    /**
     * @var array
     */
    private $attributeMap;
    /**
     * @var ValueNormalizerInterface
     */
    private $normalizer;
    /**
     * @var ProviderResolverInterface
     */
    private $priceProviderResolver;
    /**
     * @var ValueProcessor
     */
    private $valueProcessor;

    /**
     * @param ValueProcessor $valueProcessor
     * @param ProviderResolverInterface $priceProviderResolver
     * @param ValueNormalizerInterface $normalizer
     * @param array $attributeMap
     * @param bool $excludeDisabled
     */
    public function __construct(
        ValueProcessor $valueProcessor,
        ProviderResolverInterface $priceProviderResolver,
        ValueNormalizerInterface $normalizer,
        array $attributeMap = [],
        bool $excludeDisabled = true
    ) {
        $this->valueProcessor = $valueProcessor;
        $this->priceProviderResolver = $priceProviderResolver;
        $this->normalizer = $normalizer;
        $this->attributeMap = $attributeMap;
        $this->excludeDisabled = $excludeDisabled;
    }

    /**
     * @param Product[] $childProducts
     * @param Attribute[] $attributes
     *
     * @throws LocalizedException
     */
    public function buildChildren(
        array $childProducts,
        array $attributes,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $ignoredFields = $feedSpecification->getIgnoreFields()
            ?: [];
        $includeChildPrices = (bool)$feedSpecification->getIncludeChildPrices();
        $includeOutOfStock = (bool)$feedSpecification->getIncludeOutOfStock();
        $includeTierPricing = (bool)$feedSpecification->getIncludeTierPricing();

        $seenIds = [];
        $seenSkus = [];
        $children = [];

        foreach ($childProducts as $child) {
            // status
            if ($this->excludeDisabled && (int)$child->getStatus() !== Status::STATUS_ENABLED) {
                continue;
            }
            // respect includeOutOfStock:
            // exclude non-salable ONLY when includeOutOfStock === false
            if (!$includeOutOfStock && method_exists($child, 'isSalable') && !$child->isSalable()) {
                continue;
            }

            $variantId = (int)$child->getId();
            $variantSku = (string)$child->getSku();

            if ($variantId && isset($seenIds[$variantId])) {
                continue;
            }
            if ($variantSku !== '' && isset($seenSkus[$variantSku])) {
                continue;
            }
            if ($variantId) {
                $seenIds[$variantId] = true;
            }
            if ($variantSku !== '') {
                $seenSkus[$variantSku] = true;
            }

            $childRow = [
                'variant_id' => $variantId,
                'variant_sku' => $variantSku,
                'attributes' => [],
            ];

            if ($includeChildPrices) {
                $provider = $this->priceProviderResolver->resolve($child);
                $provided = $provider->getPrices($child, $ignoredFields);

                if (isset($provided[PricesProvider::FINAL_PRICE_KEY])) {
                    $minVal = (float)$provided[PricesProvider::FINAL_PRICE_KEY];
                    if (!in_array('child_final_price', $ignoredFields, true)) {
                        $childRow['final_price'] = $minVal;
                    }
                    if (!in_array('child_minimal_price', $ignoredFields, true)) {
                        $childRow['minimal_price'] = $minVal;
                    }
                }
                if (isset($provided[PricesProvider::MAX_PRICE_KEY])
                    && !in_array('child_maximal_price', $ignoredFields, true)
                ) {
                    $childRow['maximal_price'] = (float)$provided[PricesProvider::MAX_PRICE_KEY];
                }
            }

            $attrPayload = [];
            $provider = $includeChildPrices
                ? $this->priceProviderResolver->resolve($child)
                : null;
            $providerPrices = ($includeChildPrices && $provider)
                ? $provider->getPrices($child, [])
                : [];

            foreach ($attributes as $attribute) {
                $origCode = $attribute->getAttributeCode();

                if (in_array($origCode, $ignoredFields, true)) {
                    continue;
                }
                if ($origCode === 'tier_price' && !$includeTierPricing) {
                    continue;
                }
                if (in_array($origCode, $this->priceAttrCodes, true) && !$includeChildPrices) {
                    continue;
                }

                $origLabel = (string)($attribute->getStoreLabel()
                    ?: $attribute->getFrontendLabel()
                        ?: ucfirst($origCode));
                [$outCode, $outLabel] = $this->mapAttributeMeta($origCode, $origLabel);

                // provider-backed price codes
                if (isset($this->attrToProviderKey[$origCode])) {
                    $key = $this->attrToProviderKey[$origCode];
                    if (!isset($providerPrices[$key])) {
                        continue;
                    }
                    $value = (float)$providerPrices[$key];
                } elseif (in_array($origCode, ['special_price', 'cost'], true)) {
                    $raw = $child->getData($origCode);
                    $value = $this->normalizer->normalize($raw, $origCode);
                    if ($value === null) {
                        continue;
                    }
                    if (is_string($value) && is_numeric($value)) {
                        $value = (float)$value; // numeric strings -> float
                    }
                } else {
                    // non-price attributes -> ValueProcessor + normalize
                    $raw = $child->getData($origCode);
                    $value = $this->valueProcessor->getValue($attribute, $raw, $child);
                    $value = $this->normalizer->normalize($value, $origCode);
                    if ($value === null) {
                        continue;
                    }
                }

                $attrPayload[] = [
                    'code' => $outCode,
                    'label' => $outLabel,
                    'value' => $value,
                ];
            }

            $childRow['attributes'] = $attrPayload;
            $children[] = $childRow;
        }

        return [self::CHILD_KEY_NAME => $children];
    }

    /**
     * @param array $productData
     * @param array $childProducts
     * @param array $attributes
     * @param FeedSpecificationInterface $feedSpecification
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(
        array $productData,
        array $childProducts,
        array $attributes,
        FeedSpecificationInterface $feedSpecification
    ): array {
        $built = $this->buildChildren($childProducts, $attributes, $feedSpecification);

        if (!isset($productData[self::CHILD_KEY_NAME]) || !is_array($productData[self::CHILD_KEY_NAME])) {
            $productData[self::CHILD_KEY_NAME] = [];
        }
        $productData[self::CHILD_KEY_NAME] = array_values(
            array_merge(
                $productData[self::CHILD_KEY_NAME],
                $built[self::CHILD_KEY_NAME]
            )
        );

        return $productData;
    }

    /**
     * @param string $origCode
     * @param string $origLabel
     *
     * @return array|string[]
     */
    private function mapAttributeMeta(string $origCode, string $origLabel): array
    {
        $map = $this->attributeMap[$origCode] ?? null;

        if ($map === null) {
            return [$origCode, $origLabel];
        }
        if (is_string($map)) {
            return [$map, $origLabel];
        }

        $outCode = isset($map['code']) && is_string($map['code']) && $map['code'] !== ''
            ? $map['code']
            : $origCode;
        $outLabel = isset($map['label']) && is_string($map['label']) && $map['label'] !== ''
            ? $map['label']
            : $origLabel;

        return [$outCode, $outLabel];
    }
}
