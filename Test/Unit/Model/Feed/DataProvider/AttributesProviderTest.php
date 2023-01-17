<?php

namespace SearchSpring\Feed\Test\Unit\Model\Feed\DataProvider;

use SearchSpring\Feed\Model\Feed\DataProvider\Attribute\AttributesProviderInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\AttributesProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use SearchSpring\Feed\Api\Data\FeedSpecificationInterface;
use SearchSpring\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;
use SearchSpring\Feed\Model\Feed\SystemFieldsList;

class AttributesProviderTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        $this->systemFieldsListMock = $this->createMock(SystemFieldsList::class);
        $this->valueProcessorMock = $this->createMock(ValueProcessor::class);
        $this->attributesProviderMock = $this->createMock(AttributesProviderInterface::class);
        $this->attributesProvider = new AttributesProvider(
            $this->systemFieldsListMock,
            $this->valueProcessorMock,
            $this->attributesProviderMock
        );
    }

    public function testGetData()
    {
        $feedSpecificationMock = $this->getMockForAbstractClass(FeedSpecificationInterface::class);
        $productAttributeInterfaceMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributes = [
            $productAttributeInterfaceMock
        ];
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $products = [
            [
                'product_model' => $productMock
            ],
            [
                'product_model' => $productMock
            ]
        ];
        $this->attributesProviderMock->expects($this->once())
            ->method('getAttributes')
            ->with($feedSpecificationMock)
            ->willReturn($attributes);

        $productAttributeInterfaceMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('code');
        $productMock->expects($this->any())
            ->method('getData')
            ->willReturn(
                [
                    'code' => 'data',
                    'test1' => 'data2'
                ]
            );
        $this->valueProcessorMock->expects($this->at(0))
            ->method('getValue')
            ->willReturn('code');
        $this->valueProcessorMock->expects($this->at(1))
            ->method('getValue')
            ->willReturn('code1');
        $this->assertSame(
            [
                [
                    'product_model' => $productMock,
                    'code' => 'code'
                ],
                [
                    'product_model' => $productMock,
                    'code' => 'code1'
                ]
            ],
            $this->attributesProvider->getData($products, $feedSpecificationMock)
        );
    }
}
