<?php
/**
 * Copyright (C) 2023 Searchspring <https://searchspring.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace SearchSpring\Feed\Service\Normalization;

use Psr\Log\LoggerInterface;

class ValueNormalizer implements ValueNormalizerInterface
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Normalize a value for feed output.
     *
     * @param $value
     * @param $attributeCode
     *
     * @return array|mixed|string|null
     */
    public function normalize($value, $attributeCode)
    {
        try {
            if ($value === null) {
                return null;
            }

            if (is_string($value)) {
                $value = trim($value);

                return ($value === '')
                    ? null
                    : $value;
            }

            if (is_array($value)) {
                $value = $this->deepTrimStrings($value);

                $value = $this->deepFilterNulls($value);

                if ($this->isEmptyArrayRecursive($value)) {
                    return null;
                }

                if ($this->isFlatScalarList($value)) {
                    $value = array_values(array_unique($value));

                    return ($value === [])
                        ? null
                        : $value;
                }

                return $this->deepReindexNumericArrays($value);
            }

            // numbers/bool/objects -> pass through
            return $value;
        } catch (\Throwable $e) {
            $this->logger->warning(
                //phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
                'Skipping attribute, while performing normalizing value, invalid value type ' . gettype($value),
                [
                    'method' => __METHOD__,
                    'exception' => $e->getMessage(),
                    'attribute' => $attributeCode,
                    'value' => $this->safeContextValue($value),
                ]
            );

            return null;
        }
    }

    /**
     * Recursively trim strings in arrays, converting empty strings to null.
     *
     * @param $value
     *
     * @return mixed
     */
    private function deepTrimStrings($value)
    {
        if (is_string($value)) {
            $value = trim($value);

            return ($value === '')
                ? null
                : $value;
        }
        if (is_array($value)) {
            foreach ($value as $key => $vv) {
                $value[$key] = $this->deepTrimStrings($vv);
            }
        }

        return $value;
    }

    /**
     * Recursively remove null values from arrays.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function deepFilterNulls($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $vv) {
                $vv = $this->deepFilterNulls($vv);
                if ($vv === null) {
                    unset($value[$key]);
                } else {
                    $value[$key] = $vv;
                }
            }
        }

        return $value;
    }

    /**
     * Check if the value is an array that is empty or contains only empty arrays (recursively).
     *
     * @param mixed $value
     *
     * @return bool
     */
    private function isEmptyArrayRecursive($value)
    {
        if (!is_array($value)) {
            return false;
        }
        if ($value === []) {
            return true;
        }
        foreach ($value as $vv) {
            if (is_array($vv)) {
                if (!$this->isEmptyArrayRecursive($vv)) {
                    return false;
                }
            } else {
                // Any scalar/object means not empty
                return false;
            }
        }

        return true;
    }

    /**
     * Check if the array is a flat list of scalar values (no nested arrays/objects).
     *
     * @param array $values
     *
     * @return bool
     */
    private function isFlatScalarList(array $values)
    {
        foreach ($values as $vv) {
            if (is_array($vv) || is_object($vv)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursively reindex numeric arrays to ensure they have sequential integer keys starting from 0.
     *
     * @param mixed $value
     *
     * @return array|mixed
     */
    private function deepReindexNumericArrays($value)
    {
        if (!is_array($value)) {
            return $value;
        }
        foreach ($value as $key => $vv) {
            $value[$key] = $this->deepReindexNumericArrays($vv);
        }
        if ($this->isNumericArray($value)) {
            $value = array_values($value);
        }

        return $value;
    }

    /**
     * Check if all keys in the array are integers (numeric array).
     *
     * @param array $items
     *
     * @return bool
     */
    private function isNumericArray(array $items)
    {
        foreach (array_keys($items) as $key) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper to safely convert a context value to string for logging.
     *
     * @param $value
     *
     * @return string
     */
    private function safeContextValue($value)
    {
        if (is_array($value)) {
            return '[array:' . count($value) . ']';
        }
        if (is_object($value)) {
            return '[object:' . get_class($value) . ']';
        }

        return $value;
    }
}
