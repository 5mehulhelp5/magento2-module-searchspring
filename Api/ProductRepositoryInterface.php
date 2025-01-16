<?php
declare(strict_types=1);

namespace SearchSpring\Feed\Api;
interface ProductRepositoryInterface
{
    /**
     * Return a filtered product.
     *
     * @param int $id
     * @return \SearchSpring\Feed\Api\ResponseItemInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getItem(int $id);

}
