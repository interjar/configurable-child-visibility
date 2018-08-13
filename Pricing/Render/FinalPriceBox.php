<?php
/**
 * @package Interjar_ConfigurableChildVisibility
 * @author Interjar Ltd
 * @author Josh Carter <josh@interjar.com>
 */
declare(strict_types=1);

namespace Interjar\ConfigurableChildVisibility\Pricing\Render;

use Magento\Catalog\Pricing\Price;
use Magento\Framework\Pricing\Render\PriceBox as BasePriceBox;
use Magento\Msrp\Pricing\Price\MsrpPrice;
use Magento\Catalog\Model\Product\Pricing\Renderer\SalableResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Pricing\SaleableInterface;
use Magento\Framework\Pricing\Price\PriceInterface;
use Magento\Framework\Pricing\Render\RendererPool;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Pricing\Price\MinimalPriceCalculatorInterface;
use Magento\Catalog\Pricing\Price\RegularPrice;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\ConfigurableProduct\Pricing\Price\LowestPriceOptionsProviderInterface;
use Magento\Catalog\Pricing\Price\FinalPrice;

/**
 * Class for final_price rendering
 *
 * @method bool getUseLinkForAsLowAs()
 * @method bool getDisplayMinimalPrice()
 */
class FinalPriceBox extends BasePriceBox
{
    /**
     * @var LowestPriceOptionsProviderInterface
     */
    private $lowestPriceOptionsProvider;

    /**
     * @var MinimalPriceCalculatorInterface
     */
    private $minimalPriceCalculator;

    /**
     * @var SalableResolverInterface
     */
    private $salableResolver;

    /**
     * FinalPriceBox constructor
     *
     * @param Context $context
     * @param SaleableInterface $saleableItem
     * @param PriceInterface $price
     * @param RendererPool $rendererPool
     * @param ConfigurableOptionsProviderInterface $configurableOptionsProvider
     * @param array $data
     * @param LowestPriceOptionsProviderInterface|null $lowestPriceOptionsProvider
     * @param SalableResolverInterface|null $salableResolver
     * @param MinimalPriceCalculatorInterface|null $minimalPriceCalculator
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        Context $context,
        SaleableInterface $saleableItem,
        PriceInterface $price,
        RendererPool $rendererPool,
        ConfigurableOptionsProviderInterface $configurableOptionsProvider,
        array $data = [],
        LowestPriceOptionsProviderInterface $lowestPriceOptionsProvider = null,
        SalableResolverInterface $salableResolver = null,
        MinimalPriceCalculatorInterface $minimalPriceCalculator = null
    ) {
        parent::__construct($context, $saleableItem, $price, $rendererPool, $data);
        $this->salableResolver = $salableResolver ?: ObjectManager::getInstance()->get(SalableResolverInterface::class);
        $this->minimalPriceCalculator = $minimalPriceCalculator
            ?: ObjectManager::getInstance()->get(MinimalPriceCalculatorInterface::class);
        $this->lowestPriceOptionsProvider = $lowestPriceOptionsProvider ?:
            ObjectManager::getInstance()->get(LowestPriceOptionsProviderInterface::class);
    }

    /**
     * @return string
     */
    protected function _toHtml()
    {
        $result = parent::_toHtml();
        //Renders MSRP in case it is enabled
        if ($this->isMsrpPriceApplicable()) {
            /** @var BasePriceBox $msrpBlock */
            $msrpBlock = $this->rendererPool->createPriceRender(
                MsrpPrice::PRICE_CODE,
                $this->getSaleableItem(),
                [
                    'real_price_html' => $result,
                    'zone' => $this->getZone(),
                ]
            );
            $result = $msrpBlock->toHtml();
        }

        return $this->wrapResult($result);
    }

    /**
     * Check is MSRP applicable for the current product.
     *
     * @return bool
     */
    protected function isMsrpPriceApplicable()
    {
        try {
            /** @var MsrpPrice $msrpPriceType */
            $msrpPriceType = $this->getSaleableItem()->getPriceInfo()->getPrice('msrp_price');
        } catch (\InvalidArgumentException $e) {
            $this->_logger->critical($e);
            return false;
        }

        $product = $this->getSaleableItem();
        return $msrpPriceType->canApplyMsrp($product) && $msrpPriceType->isMinimalPriceLessMsrp($product);
    }

    /**
     * Wrap with standard required container
     *
     * @param string $html
     * @return string
     */
    protected function wrapResult($html)
    {
        return '<div class="price-box ' . $this->getData('css_classes') . '" ' .
            'data-role="priceBox" ' .
            'data-product-id="' . $this->getSaleableItem()->getId() . '" ' .
            'data-price-box="product-id-' . $this->getSaleableItem()->getId() . '"' .
            '>' . $html . '</div>';
    }

    /**
     * Render minimal amount
     *
     * @return string
     */
    public function renderAmountMinimal()
    {
        $id = $this->getPriceId() ? $this->getPriceId() : 'product-minimal-price-' . $this->getSaleableItem()->getId();

        $amount = $this->minimalPriceCalculator->getAmount($this->getSaleableItem());
        if ($amount === null) {
            return '';
        }

        return $this->renderAmount(
            $amount,
            [
                'display_label'     => __('As low as'),
                'price_id'          => $id,
                'include_container' => false,
                'skip_adjustments' => true
            ]
        );
    }

    /**
     * Define if the special price should be shown
     *
     * @return bool
     */
    public function hasSpecialPrice()
    {
        $product = $this->getSaleableItem();
        foreach ($this->lowestPriceOptionsProvider->getProducts($product) as $subProduct) {
            $regularPrice = $subProduct->getPriceInfo()->getPrice(RegularPrice::PRICE_CODE)->getValue();
            $finalPrice = $subProduct->getPriceInfo()->getPrice(FinalPrice::PRICE_CODE)->getValue();
            if ($finalPrice < $regularPrice) {
                return true;
            }
        }
        return false;
    }

    /**
     * Define if the minimal price should be shown
     *
     * @return bool
     */
    public function showMinimalPrice()
    {
        $minTierPrice = $this->minimalPriceCalculator->getValue($this->getSaleableItem());

        /** @var Price\FinalPrice $finalPrice */
        $finalPrice = $this->getPriceType(Price\FinalPrice::PRICE_CODE);
        $finalPriceValue = $finalPrice->getAmount()->getValue();

        return $this->getDisplayMinimalPrice()
            && $minTierPrice !== null
            && $minTierPrice < $finalPriceValue;
    }

    /**
     * Get Key for caching block content
     *
     * @return string
     */
    public function getCacheKey()
    {
        return parent::getCacheKey() . ($this->getData('list_category_page') ? '-list-category-page': '');
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        $cacheKeys = parent::getCacheKeyInfo();
        $cacheKeys['display_minimal_price'] = $this->getDisplayMinimalPrice();
        $cacheKeys['is_product_list'] = $this->isProductList();
        return $cacheKeys;
    }

    /**
     * Get flag that price rendering should be done for the list of products
     * By default (if flag is not set) is false
     *
     * @return bool
     */
    public function isProductList()
    {
        $isProductList = $this->getData('is_product_list');
        return $isProductList === true;
    }
}
