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

namespace SearchSpring\Feed\Model\Feed\DataProvider\Stock;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;

class MsiStockResolver implements StockResolverInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var MsiStockProvider
     */
    protected $stockProvider;

    private $moduleList = [
        'Magento_InventoryReservationsApi',
        'Magento_InventorySalesApi',
        'Magento_InventoryCatalogApi'
    ];

    /**
     * MsiStockResolver constructor.
     * @param Manager $moduleManager
     * @param array $moduleList
     * @param MsiStockProvider $stockProvider
     */
    public function __construct(
        Manager $moduleManager,
        MsiStockProvider $stockProvider,
        array $moduleList = [],

    ) {
        $this->moduleManager = $moduleManager;
        $this->moduleList = array_merge($this->moduleList, $moduleList);
        $this->stockProvider = $stockProvider;
    }

    /**
     * @return StockProviderInterface
     * @throws NoSuchEntityException
     */
    public function resolve(): StockProviderInterface
    {
        if (!$this->isMsiEnabled()) {
            throw new NoSuchEntityException(__('MSI is not installed'));
        }
        return $this->stockProvider;
    }

    /**
     * @return bool
     */
    private function isMsiEnabled() : bool
    {
        $moduleExists = true;
        foreach ($this->moduleList as $moduleName) {
            if (!$this->moduleManager->isEnabled($moduleName)) {
                $moduleExists = false;
                break;
            }
        }

        if (!$moduleExists) {
            return false;
        }

        return true;
    }
}
