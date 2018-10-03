<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\InventorySales\Test\Integration\IsProductSalable;

use Magento\InventoryConfigurationApi\Api\GetStockConfigurationInterface;
use Magento\InventoryConfigurationApi\Api\SaveStockConfigurationInterface;
use Magento\InventorySalesApi\Api\IsProductSalableInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

class MinQtyConditionTest extends TestCase
{
    /**
     * @var IsProductSalableInterface
     */
    private $isProductSalable;

    /**
     * @var GetStockConfigurationInterface
     */
    private $getStockConfiguration;

    /**
     * @var SaveStockConfigurationInterface
     */
    private $saveStockConfiguration;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->isProductSalable = Bootstrap::getObjectManager()->get(IsProductSalableInterface::class);
        $this->getStockConfiguration = Bootstrap::getObjectManager()->get(GetStockConfigurationInterface::class);
        $this->saveStockConfiguration = Bootstrap::getObjectManager()->get(SaveStockConfigurationInterface::class);
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param bool $expectedResult
     * @return void
     *
     * @dataProvider executeWithMinQtyDataProvider
     *
     * @magentoDbIsolation disabled
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function testExecuteWithMinQty(string $sku, int $stockId, bool $expectedResult)
    {
        $minQty = !$expectedResult ? 5 : null;

        $oldStockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $oldStockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);

        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $stockConfiguration->setMinQty($minQty);
        $stockConfiguration->setIsDecimalDivided(false);
        $stockConfiguration->setIsQtyDecimal(false);
        $this->saveStockConfiguration->forStock($stockId, $stockConfiguration);

        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setMinQty(null);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $isSalable = $this->isProductSalable->execute($sku, $stockId);

        // Clean up
        $this->saveStockConfiguration->forStock($stockId, $oldStockConfiguration);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $oldStockItemConfiguration);

        self::assertEquals($expectedResult, $isSalable);
    }

    /**
     * @return array
     */
    public function executeWithMinQtyDataProvider(): array
    {
        return [
            ['SKU-1', 10, true],
            ['SKU-1', 20, false],
            ['SKU-1', 30, true],
            ['SKU-2', 10, false],
            ['SKU-2', 20, false],
            ['SKU-2', 30, false],
            ['SKU-3', 10, false],
            ['SKU-3', 20, false],
            ['SKU-3', 30, false],
        ];
    }

    /**
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/products.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/sources.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stocks.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/source_items.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryApi/Test/_files/stock_source_links.php
     * @magentoDataFixture ../../../../app/code/Magento/InventoryIndexer/Test/_files/reindex_inventory.php
     *
     * @param string $sku
     * @param int $stockId
     * @param bool $expectedResult
     * @return void
     *
     * @dataProvider executeWithManageStockFalseAndMinQty
     *
     * @magentoDbIsolation disabled
     * @SuppressWarnings(PHPMD.LongVariable)
     */
    public function testExecuteWithManageStockFalseAndMinQty(string $sku, int $stockId, bool $expectedResult)
    {
        $oldStockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $oldStockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);

        $minQty = !$expectedResult ? 5 : null;

        $stockConfiguration = $this->getStockConfiguration->forStock($stockId);
        $stockConfiguration->setMinQty($minQty);
        $stockConfiguration->setManageStock(!$expectedResult);
        $stockConfiguration->setIsDecimalDivided(false);
        $stockConfiguration->setIsQtyDecimal(false);
        $this->saveStockConfiguration->forStock($stockId, $stockConfiguration);

        $stockItemConfiguration = $this->getStockConfiguration->forStockItem($sku, $stockId);
        $stockItemConfiguration->setMinQty(null);
        $stockItemConfiguration->setIsQtyDecimal(false);
        $stockItemConfiguration->setIsDecimalDivided(false);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $stockItemConfiguration);

        $isSalable = $this->isProductSalable->execute($sku, $stockId);

        // Clean up
        $this->saveStockConfiguration->forStock($stockId, $oldStockConfiguration);
        $this->saveStockConfiguration->forStockItem($sku, $stockId, $oldStockItemConfiguration);

        self::assertEquals($expectedResult, $isSalable);
    }

    /**
     * @return array
     */
    public function executeWithManageStockFalseAndMinQty(): array
    {
        return [
            ['SKU-1', 10, true],
            ['SKU-1', 20, false],
            ['SKU-1', 30, true],
            ['SKU-2', 10, false],
            ['SKU-2', 20, true],
            ['SKU-2', 30, true],
            ['SKU-3', 10, true],
            ['SKU-3', 20, false],
            ['SKU-3', 30, true],
        ];
    }
}
