<?php
/**
 * @package Interjar_ConfigurableChildVisibility
 * @author Interjar Ltd
 * @author Josh Carter <josh@interjar.com>
 */
declare(strict_types=1);

namespace Interjar\ConfigurableChildVisibility\Model\Product\Type\Configurable\Collection;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\CatalogInventory\Model\ResourceModel\Stock\StatusFactory;
use Magento\CatalogInventory\Model\Configuration;
use Magento\ConfigurableProduct\Model\Product\Type\Collection\SalableProcessor as ParentSalableProcessor;

class SalableProcessor extends ParentSalableProcessor
{
    /**
     * @var StatusFactory
     */
    private $stockStatusFactory;

    /**
     * @var Configuration
     */
    private $stockConfiguration;

    /**
     * SalableProcessor constructor
     *
     * @param StatusFactory $stockStatusFactory
     * @param Configuration $stockConfiguration
     */
    public function __construct(
        StatusFactory $stockStatusFactory,
        Configuration $stockConfiguration
    ) {
        parent::__construct($stockStatusFactory);
        $this->stockStatusFactory = $stockStatusFactory;
        $this->stockConfiguration = $stockConfiguration;
    }

    /**
     * Rewritten so that if show out of stock products is set to yes
     * it wont remove out of stock products
     *
     * @param Collection $collection
     * @return Collection
     */
    public function process(Collection $collection): Collection
    {
        $collection->addAttributeToFilter(
            ProductInterface::STATUS,
            Status::STATUS_ENABLED
        );

        $stockFlag = 'has_stock_status_filter';
        if (!$collection->hasFlag($stockFlag)) {
            $stockStatusResource = $this->stockStatusFactory->create();
            $isFilterInStock = $this->getIsFilterInStock();
            $stockStatusResource->addStockDataToCollection(
                $collection,
                $isFilterInStock
            );
            $collection->setFlag($stockFlag, true);
        }

        return $collection;
    }

    /**
     * Return Configuration value for showing Out Of Stock Products
     *
     * @return bool
     */
    private function getIsFilterInStock(): bool
    {
        $showOutOfStock = $this->stockConfiguration->isShowOutOfStock();
        return $showOutOfStock ? false : true;
    }
}
