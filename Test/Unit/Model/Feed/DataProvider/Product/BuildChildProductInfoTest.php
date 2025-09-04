<?php
declare(strict_types=1);

namespace SearchSpring\Feed\Test\Unit\Model\Feed\DataProvider\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use SearchSpring\Feed\Model\Feed\DataProvider\Price\PriceProviderInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\Price\ProviderResolverInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\PricesProvider;
use SearchSpring\Feed\Model\Feed\DataProvider\Product\BuildChildProductInfo;
use SearchSpring\Feed\Service\Normalization\ValueNormalizerInterface;

class BuildChildProductInfoTest extends TestCase
{
    /** @var ValueProcessor|MockObject */
    private $valueProcessor;

    /** @var ProviderResolverInterface|MockObject */
    private $resolver;

    /** @var ValueNormalizerInterface|MockObject */
    private $normalizer;

    /** @var FeedSpecificationInterface|MockObject */
    private $spec;

    protected function setUp(): void
    {
        $this->valueProcessor = $this->getMockBuilder(ValueProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resolver = $this->getMockBuilder(ProviderResolverInterface::class)->getMock();
        $this->normalizer = $this->getMockBuilder(ValueNormalizerInterface::class)->getMock();
        $this->spec = $this->getMockBuilder(FeedSpecificationInterface::class)->getMock();
    }

    private function makeProduct(int $id, string $sku, int $status = 1, bool $salable = true, array $data = []): Product
    {
        $product = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getSku', 'getStatus', 'isSalable', 'getData'])
            ->getMock();

        $product->method('getId')->willReturn($id);
        $product->method('getSku')->willReturn($sku);
        $product->method('getStatus')->willReturn($status);
        $product->method('isSalable')->willReturn($salable);
        $product->method('getData')->willReturnCallback(function ($key) use ($data) {
            return array_key_exists($key, $data)
                ? $data[$key]
                : null;
        });

        return $product;
    }

    private function makeAttribute(string $code, string $storeLabel = '', string $frontendLabel = ''): Attribute
    {
        $attr = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getAttributeCode', 'getStoreLabel', 'getFrontendLabel'])
            ->getMock();
        $attr->method('getAttributeCode')->willReturn($code);
        $attr->method('getStoreLabel')->willReturn($storeLabel
            ?: null);
        $attr->method('getFrontendLabel')->willReturn($frontendLabel
            ?: null);

        return $attr;
    }

    public function testIncludesTopLevelPricesWhenEnabled(): void
    {
        $child = $this->makeProduct(10, 'sku-10');

        $provider = $this->getMockBuilder(PriceProviderInterface::class)->getMock();
        $provider->method('getPrices')->willReturn([
            PricesProvider::FINAL_PRICE_KEY => 4000.0,
            PricesProvider::MAX_PRICE_KEY => 4000.0,
            PricesProvider::REGULAR_PRICE_KEY => 5000.0,
        ]);
        $this->resolver->method('resolve')->willReturn($provider);

        $this->spec->method('getIgnoreFields')->willReturn([]);
        $this->spec->method('getIncludeChildPrices')->willReturn(true);
        $this->spec->method('getIncludeOutOfStock')->willReturn(true);
        $this->spec->method('getIncludeTierPricing')->willReturn(false);

        // non-price attribute "color"
        $attrColor = $this->makeAttribute('color', 'Color');
        $this->valueProcessor->method('getValue')->willReturn('Black');
        $this->normalizer->method('normalize')->willReturnCallback(function ($v) {
            return $v;
        });

        $sut = new BuildChildProductInfo(
            $this->valueProcessor,
            $this->resolver,
            $this->normalizer,
            [],
            true
        );

        $out = $sut->buildChildren([$child], [$attrColor], $this->spec);

        $this->assertArrayHasKey('child_info', $out);
        $this->assertCount(1, $out['child_info']);
        $row = $out['child_info'][0];

        $this->assertSame(10, $row['variant_id']);
        $this->assertSame('sku-10', $row['variant_sku']);

        // top-level prices
        $this->assertSame(4000.0, $row['final_price']);
        $this->assertSame(4000.0, $row['minimal_price']);
        $this->assertSame(4000.0, $row['maximal_price']);

        // attribute payload contains color
        $this->assertEquals([
            ['code' => 'color', 'label' => 'Color', 'value' => 'Black'],
        ], $row['attributes']);
    }

    public function testRespectsIgnoreFieldsForTopLevelPriceKeys(): void
    {
        $child = $this->makeProduct(10, 'sku-10');
        $provider = $this->getMockBuilder(PriceProviderInterface::class)->getMock();
        $provider->method('getPrices')->willReturn([
            PricesProvider::FINAL_PRICE_KEY => 111.0,
            PricesProvider::MAX_PRICE_KEY => 222.0,
        ]);
        $this->resolver->method('resolve')->willReturn($provider);

        $this->spec->method('getIgnoreFields')->willReturn(['child_maximal_price']);
        $this->spec->method('getIncludeChildPrices')->willReturn(true);
        $this->spec->method('getIncludeOutOfStock')->willReturn(true);
        $this->spec->method('getIncludeTierPricing')->willReturn(false);

        $this->valueProcessor->method('getValue')->willReturn(null);
        $this->normalizer->method('normalize')->willReturn(null);

        $sut = new BuildChildProductInfo(
            $this->valueProcessor,
            $this->resolver,
            $this->normalizer
        );

        $out = $sut->buildChildren([$child], [], $this->spec);
        $row = $out['child_info'][0];

        $this->assertSame(111.0, $row['final_price']);
        $this->assertSame(111.0, $row['minimal_price']);
        $this->assertArrayNotHasKey('maximal_price', $row);
    }

    public function testSkipsTopLevelPricesWhenDisabled(): void
    {
        $child = $this->makeProduct(10, 'sku-10');

        $this->resolver->expects($this->never())->method('resolve');

        $this->spec->method('getIgnoreFields')->willReturn([]);
        $this->spec->method('getIncludeChildPrices')->willReturn(false);
        $this->spec->method('getIncludeOutOfStock')->willReturn(true);
        $this->spec->method('getIncludeTierPricing')->willReturn(false);

        $this->valueProcessor->method('getValue')->willReturn(null);
        $this->normalizer->method('normalize')->willReturn(null);

        $sut = new BuildChildProductInfo(
            $this->valueProcessor,
            $this->resolver,
            $this->normalizer
        );

        $out = $sut->buildChildren([$child], [], $this->spec);
        $row = $out['child_info'][0];

        $this->assertArrayNotHasKey('final_price', $row);
        $this->assertArrayNotHasKey('minimal_price', $row);
        $this->assertArrayNotHasKey('maximal_price', $row);
    }

    public function testFiltersByStatusAndSalable(): void
    {
        // disabled child should be skipped (excludeDisabled default = true)
        $disabled = $this->makeProduct(11, 'sku-11', /*status*/ 0, /*salable*/ true);

        // non-salable child should be skipped when includeOutOfStock=false
        $nonsalable = $this->makeProduct(12, 'sku-12', /*status*/ 1, /*salable*/ false);

        $this->spec->method('getIgnoreFields')->willReturn([]);
        $this->spec->method('getIncludeChildPrices')->willReturn(false);
        $this->spec->method('getIncludeOutOfStock')->willReturn(false); // exclude non-salable
        $this->spec->method('getIncludeTierPricing')->willReturn(false);

        $sut = new BuildChildProductInfo(
            $this->valueProcessor,
            $this->resolver,
            $this->normalizer
        );

        $out = $sut->buildChildren([$disabled, $nonsalable], [], $this->spec);
        $this->assertSame(['child_info' => []], $out);
    }

    public function testSkipsTierPriceWhenIncludeTierPricingFalse(): void
    {
        $child = $this->makeProduct(10, 'sku-10', 1, true, [
            'tier_price' => [
                ['price_id' => '12', 'price' => 10],
                ['price_id' => '13', 'price' => 9],
            ],
        ]);

        $attrTier = $this->makeAttribute('tier_price', 'Tier Price');

        $this->spec->method('getIgnoreFields')->willReturn([]);
        $this->spec->method('getIncludeChildPrices')->willReturn(false);
        $this->spec->method('getIncludeOutOfStock')->willReturn(true);
        $this->spec->method('getIncludeTierPricing')->willReturn(false);

        // normalizer would have returned normalized arrays if called,
        // but since includeTierPricing=false, it should never be used for tier_price
        $this->normalizer->expects($this->never())->method('normalize');

        $sut = new BuildChildProductInfo(
            $this->valueProcessor,
            $this->resolver,
            $this->normalizer
        );

        $out = $sut->buildChildren([$child], [$attrTier], $this->spec);
        $row = $out['child_info'][0];

        $this->assertSame(10, $row['variant_id']);
        $this->assertSame('sku-10', $row['variant_sku']);
        $this->assertSame([], $row['attributes']); // tier_price excluded
    }

    public function testPriceAttributesInsideAttributesFromProviderAndEav(): void
    {
        $child = $this->makeProduct(10, 'sku-10', 1, true, [
            'special_price' => ' 4000 ', // EAV-backed
            'cost' => 3000,     // EAV-backed
        ]);

        $attrPrice = $this->makeAttribute('price', 'Price');
        $attrFinal = $this->makeAttribute('final_price', 'Final Price');
        $attrMinimal = $this->makeAttribute('minimal_price', 'Minimal Price');
        $attrMaximal = $this->makeAttribute('maximal_price', 'Maximal Price');
        $attrSpecialPrice = $this->makeAttribute('special_price', 'Special Price');
        $attrCost = $this->makeAttribute('cost', 'Cost');

        $attrs = [$attrPrice, $attrFinal, $attrMinimal, $attrMaximal, $attrSpecialPrice, $attrCost];

        $provider = $this->getMockBuilder(PriceProviderInterface::class)->getMock();
        $provider->method('getPrices')->willReturn([
            PricesProvider::FINAL_PRICE_KEY => 4000.0,
            PricesProvider::MAX_PRICE_KEY => 4000.0,
            PricesProvider::REGULAR_PRICE_KEY => 5000.0,
        ]);
        $this->resolver->method('resolve')->willReturn($provider);

        $this->spec->method('getIgnoreFields')->willReturn([]);
        $this->spec->method('getIncludeChildPrices')->willReturn(true);
        $this->spec->method('getIncludeOutOfStock')->willReturn(true);
        $this->spec->method('getIncludeTierPricing')->willReturn(false);

        // Non-price attributes won't be used here; normalizer only for special_price/cost
        $this->normalizer->method('normalize')->willReturnCallback(function ($v) {
            return is_string($v)
                ? trim($v)
                : $v;
        });

        $sut = new BuildChildProductInfo(
            $this->valueProcessor,
            $this->resolver,
            $this->normalizer
        );

        $out = $sut->buildChildren([$child], $attrs, $this->spec);
        $row = $out['child_info'][0];

        // top-level prices present
        $this->assertSame(4000.0, $row['final_price']);
        $this->assertSame(4000.0, $row['minimal_price']);
        $this->assertSame(4000.0, $row['maximal_price']);

        // attributes[] should include provider-backed + EAV-backed
        $this->assertEquals([
            ['code' => 'price', 'label' => 'Price', 'value' => 5000.0],
            ['code' => 'final_price', 'label' => 'Final Price', 'value' => 4000.0],
            ['code' => 'minimal_price', 'label' => 'Minimal Price', 'value' => 4000.0],
            ['code' => 'maximal_price', 'label' => 'Maximal Price', 'value' => 4000.0],
            ['code' => 'special_price', 'label' => 'Special Price', 'value' => '4000'],
            ['code' => 'cost', 'label' => 'Cost', 'value' => 3000],
        ], $row['attributes']);
    }

    public function testAttributeMappingAndNormalizationForNonPrice(): void
    {
        $child = $this->makeProduct(10, 'sku-10', 1, true, [
            'color' => '  Black ',
        ]);

        $attr = $this->makeAttribute('color', '', 'Color');

        $this->spec->method('getIgnoreFields')->willReturn([]);
        $this->spec->method('getIncludeChildPrices')->willReturn(false);
        $this->spec->method('getIncludeOutOfStock')->willReturn(true);
        $this->spec->method('getIncludeTierPricing')->willReturn(false);

        $this->valueProcessor->method('getValue')
            ->willReturnCallback(function ($attribute, $raw) {
                return $raw;
            });

        $this->normalizer->method('normalize')
            ->willReturnCallback(function ($v) {
                return is_string($v)
                    ? trim($v)
                    : $v;
            });

        $sut = new BuildChildProductInfo(
            $this->valueProcessor,
            $this->resolver,
            $this->normalizer,
            /* attributeMap: map color->colour with label */
            ['color' => ['code' => 'colour', 'label' => 'Colour']]
        );

        $out = $sut->buildChildren([$child], [$attr], $this->spec);
        $row = $out['child_info'][0];

        $this->assertEquals([
            ['code' => 'colour', 'label' => 'Colour', 'value' => 'Black'],
        ], $row['attributes']);
    }
}
