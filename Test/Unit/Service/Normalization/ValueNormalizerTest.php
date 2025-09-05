<?php
declare(strict_types=1);

namespace SearchSpring\Feed\Test\Unit\Service\Normalization;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SearchSpring\Feed\Service\Normalization\ValueNormalizer;

/**
 * @covers \SearchSpring\Feed\Service\Normalization\ValueNormalizer
 */
class ValueNormalizerTest extends TestCase
{
    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var ValueNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->normalizer = new ValueNormalizer($this->logger);
    }

    public function testNullReturnsNull(): void
    {
        $this->logger->expects($this->never())->method('warning');
        $this->assertNull($this->normalizer->normalize(null, 'code'));
    }

    public function testStringTrimsAndEmptyBecomesNull(): void
    {
        $this->assertNull($this->normalizer->normalize('   ', 'code'));
        $this->assertSame('abc', $this->normalizer->normalize('  abc  ', 'code'));
    }

    public function testFlatScalarListTrimDropEmptyDedupeReindex(): void
    {
        $in  = ['  a ', '', 'b', 'a', ' ', null, "b", "a"];
        $exp = ['a', 'b'];
        $this->assertSame($exp, $this->normalizer->normalize($in, 'tags'));
    }

    public function testNestedArraysNoDedupePreserveStructure(): void
    {
        $in = [
            ['  a  ', ' ', "\n"],   // -> ['a']
            ['b', 'b'],             // stays ['b','b']
            'c' => [' d ', ''],     // assoc key preserved
        ];
        $exp = [
            ['a'],
            ['b', 'b'],
            'c' => ['d'],
        ];
        $this->assertSame($exp, $this->normalizer->normalize($in, 'nested'));
    }

    public function testNestedArraysAllEmptyBecomesNull(): void
    {
        $in = [
            ['   '],
            [null],
            [' ', "\n"]
        ];
        $this->assertNull($this->normalizer->normalize($in, 'empty_nested'));
    }

    public function testTierPriceLikeStructureHandled(): void
    {
        $in = [
            [
                'price_id' => '12',
                'price' => 4950.0,
                'price_qty' => '1.0000',
                'note' => '  x  ',
            ],
            [
                'price_id' => '13',
                'price' => 4900.0,
                'price_qty' => '1.0000',
                'note' => '',
            ],
            [
                'price_id' => '13',
                'price' => 4900.0,
                'price_qty' => '1.0000',
                'note' => " \n ",
            ],
        ];

        $exp = [
            [
                'price_id' => '12',
                'price' => 4950.0,
                'price_qty' => '1.0000',
                'note' => 'x',
            ],
            [
                'price_id' => '13',
                'price' => 4900.0,
                'price_qty' => '1.0000',
            ],
            [
                'price_id' => '13',
                'price' => 4900.0,
                'price_qty' => '1.0000',
            ],
        ];

        $this->assertSame($exp, $this->normalizer->normalize($in, 'tier_price'));
    }

    public function testNumbersBooleansObjectsPassThrough(): void
    {
        $obj = (object)['k' => 'v'];
        $this->assertSame(0, $this->normalizer->normalize(0, 'qty'));
        $this->assertSame(123, $this->normalizer->normalize(123, 'qty'));
        $this->assertSame(12.5, $this->normalizer->normalize(12.5, 'price'));
        $this->assertTrue($this->normalizer->normalize(true, 'flag'));
        $this->assertFalse($this->normalizer->normalize(false, 'flag'));
        $this->assertSame($obj, $this->normalizer->normalize($obj, 'payload'));
    }
}
