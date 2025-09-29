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

namespace SearchSpring\Feed\Model\Feed\DataProvider\Attribute;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Attribute\Source\SpecificSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class ValueProcessor
{
    private $cache = [];

    private $sourceAttributes = [];

    /**
     * Get the display value for an attribute value
     *
     * @param Attribute $attribute
     * @param $value
     * @param Product $product
     *
     * @return bool|string|null
     * @throws LocalizedException
     */
    public function getValue(Attribute $attribute, $value, Product $product)
    {
        $key = null;
        $code = $attribute->getAttributeCode();
        if (!is_object($value) && !is_array($value) && $this->isSourceAttribute($attribute)) {
            $key = $code . '_' . $value;
            if (isset($this->cache[$key])) {
                return $this->cache[$key];
            }
        }

        $result = null;
        if ($this->isSourceAttribute($attribute)) {
            $source = $attribute->getSource();
            if ($source instanceof SpecificSourceInterface) {
                $sourceClone = clone $source;
                $sourceClone->getOptionsFor($product);
                $result = $sourceClone->getOptionText($value);
            } else {
                $result = $source->getOptionText($value);
            }
        } else {
            $result = $value;
        }

        if (is_object($result)) {
            if ($result instanceof Phrase) {
                $result = $result->getText();
            } else {
                $debugType = function_exists('get_debug_type')
                    ? get_debug_type($result)
                    : gettype($result);
                throw new Exception("Unknown value object type " . $debugType);
            }
        }

        if ($key) {
            $this->cache[$key] = $result;
        }

        return $result;
    }

    /**
     *
     */
    public function reset(): void
    {
        $this->cache = [];
        $this->sourceAttributes = [];
    }

    /**
     * Convert an array of values into a multi-value string
     *
     * @param array $values
     * @param string $separator
     *
     * @return string
     */
    public function toMultiValueString(
        array $values,
        string $separator = '|'
    ): string {
        $normalized = array_map(function ($value) use ($separator) {
            if (is_array($value)) {
                // Handle nested arrays (e.g. multi-select)
                return implode($separator, $value);
            }

            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }

            if ($value === null || $value === '') {
                return '';
            }

            return trim((string)$value);
        }, $values);

        // Filter out empty strings so we don’t end up with "||"
        $normalized = array_filter($normalized, function ($val) {
            return $val !== '';
        });

        return implode($separator, array_values($normalized));
    }

    /**
     * @param Attribute $attribute
     *
     * @return bool
     */
    private function isSourceAttribute(Attribute $attribute): bool
    {
        $code = $attribute->getAttributeCode();
        if (!array_key_exists($code, $this->sourceAttributes)) {
            $this->sourceAttributes[$code] = $attribute->usesSource();
        }

        return $this->sourceAttributes[$code];
    }
}
