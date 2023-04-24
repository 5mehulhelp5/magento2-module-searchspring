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

namespace SearchSpring\Feed\Model\Feed\Storage;

class FilePool
{
    /**
     * @var array
     */
    private $files;

    /**
     * FilePool constructor.
     * @param array $files
     */
    public function __construct(
        array $files = []
    ) {
        $this->files = $files;
    }

    /**
     * @param string $format
     * @return string|null
     */
    public function get(string $format) : ?string
    {
        return $this->files[$format] ?? null;
    }
}
