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

namespace SearchSpring\Feed\Test\Unit\Model\Feed\DataProvider\Attribute;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\Framework\Exception\LocalizedException;
use SearchSpring\Feed\Model\Feed\DataProvider\Attribute\ValueProcessor;

class ValueProcessorTest extends \PHPUnit\Framework\TestCase
{
    private $valueProcessor;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->valueProcessor = new ValueProcessor();
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testGetValue()
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->expects($this->once())
            ->method('usesSource')
            ->willReturn(false);
        $attributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('test');

        $this->assertSame(
            'test',
            $this->valueProcessor->getValue($attributeMock, 'test', $productMock)
        );
    }

    public function testGetValueOnCache()
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $abstractSourceMock = $this->createMock(AbstractSource::class);
        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->expects($this->once())
            ->method('usesSource')
            ->willReturn(true);
        $attributeMock->expects($this->any())
            ->method('getAttributeCode')
            ->willReturn('test');
        $attributeMock->expects($this->once())
            ->method('getSource')
            ->willReturn($abstractSourceMock);
        $abstractSourceMock->expects($this->once())
            ->method('getOptionText')
            ->willReturn('test_option_text');

        $this->valueProcessor->getValue($attributeMock, 'test', $productMock);
        $this->valueProcessor->getValue($attributeMock, 'test', $productMock);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function testGetValueException()
    {
        $productMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock = $this->getMockBuilder(Attribute::class)
            ->disableOriginalConstructor()
            ->getMock();
        $attributeMock->expects($this->once())
            ->method('usesSource')
            ->willReturn(false);
        $attributeMock->expects($this->at(0))
            ->method('getAttributeCode')
            ->willReturn($attributeMock);
        $attributeMock->expects($this->at(1))
            ->method('getAttributeCode')
            ->willReturn('test');
        $attributeMock->expects($this->at(2))
            ->method('getAttributeCode')
            ->willReturn('test');

        $this->expectException(\Exception::class);

        $this->valueProcessor->getValue($attributeMock, $attributeMock, $productMock);
    }

    /**
     * @dataProvider multiValueProvider
     */
    public function testToMultiValueString(
        array $input,
        string $expected,
        string $separator = '|'
    ): void {
        $valueProcessor = new ValueProcessor();
        $result = $valueProcessor->toMultiValueString($input, $separator);

        $this->assertSame(
            $expected,
            $result,
            "Failed asserting that the multi-value string conversion of " . json_encode($input) .
            " with separator '$separator' equals '$expected'. Got '$result' instead."
        );
    }

    /**
     * @return array[]
     */
    public function multiValueProvider(): array
    {
        return [
            'simple_strings_with_pipe_separator' => [
                ['Red', 'Yellow'],
                'Red|Yellow',
            ],
            'simple_duplicated_strings_with_pipe_separator' => [
                ['Red', 'Red', 'Yellow'],
                'Red|Red|Yellow',
            ],
            'simple_strings_with_comma_separator' => [
                ['Red', 'Purple', 'Yellow'],
                'Red,Purple,Yellow',
                ","
            ],
            'simple_strings_with_semi_colon_separator' => [
                ['Red', 'White'],
                'Red;White',
                ";"
            ],
            'simple_strings_with_hash_separator' => [
                ['Black', 'White','white'],
                'Black#White#white',
                "#"
            ],
            'simple_strings_with_dollar_sign_separator' => [
                ['Green', 'Magenta', 'Cosmic Latte'],
                'Green$Magenta$Cosmic Latte',
                "$"
            ],
            'simple_mixed_strings' => [
                [false, 'Yellow','false',"false",'FALSE'],
                'false|Yellow|false|false|FALSE',
            ],
            'special_chars' => [
                ['Żubrówka', 'Crème brûlée', 'Éclair', 'Soufflé', 'Schön', 'Fußball', 'Über'],
                'Żubrówka|Crème brûlée|Éclair|Soufflé|Schön|Fußball|Über',
            ],
            'price_related_numbers_with_pipe' => [
                [1200.000000, 1500.000000],
                '1200|1500',
            ],
            'price_related_numbers_dollar_sign' => [
                [1200.000000, 1500.000000],
                '1200$1500',
                '$'
            ],
            'price_related_numbers_with_comma_separator' => [
                [12345.0000, 2345.000000],
                '12345,2345',
                ","
            ],
            'nested_arrays_multi_select' => [
                [['Climbing', 'Rafting'], ['Swimming', 'Cycling']],
                'Climbing|Rafting|Swimming|Cycling',
            ],
            'nested_arrays_multi_select_comma_separator' => [
                [[''],['Climbing', 'Rafting'], ['Swimming', 'Cycling']],
                'Climbing,Rafting,Swimming,Cycling',
                ","
            ],
            'nested_arrays_multi_select_hash_separator' => [
                [['Scuba'],['Climbing', 'Rafting'], ['Swimming', 'Cycling']],
                'Scuba#Climbing#Rafting#Swimming#Cycling',
                "#"
            ],
            'nested_arrays_multi_select_dollar_separator' => [
                [['Kayaking','Scuba','Paragliding'],['Climbing', 'Rafting'], ['Swimming', 'Cycling']],
                'Kayaking$Scuba$Paragliding$Climbing$Rafting$Swimming$Cycling',
                "$"
            ],
            'empty'=> [
                [],
                '',
            ],
            'booleans' => [
                [true, false, 1, 0, 'true', 'false'],
                'true|false|1|0|true|false',
            ],
            'nulls_and_empties_filtered' => [
                ['', null, 'Chocolate', 'Butter Scotch'],
                'Chocolate|Butter Scotch',
            ],
            'leading_empty_removed' => [
                ['', 'XXL', 'L'],
                'XXL|L',
            ],
            'single_value_pipe_separator' => [
                ['Whey Protein'],
                'Whey Protein',
                '|'
            ],
            'single_value_comma_separator' => [
                ['Whey Protein'],
                'Whey Protein',
                ','
            ],
            'html_content' => [
                ['<p>Short</p>', '<p>Long</p>'],
                '<p>Short</p>|<p>Long</p>',
            ],
        ];
    }
}
