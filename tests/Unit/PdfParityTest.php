<?php

declare(strict_types=1);

namespace Fina\Sdk\Laravel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Every method listed in the "FINA WEB API 8.0" table of contents
 * (version 8.0.20260303) must be referenced by name somewhere in src/.
 */
final class PdfParityTest extends TestCase
{
    /** @var string[] */
    private const PDF_METHODS = [
        'authenticate',
        'getCustomersByCode', 'getVendorsByCode', 'getCustomers', 'getVendors',
        'getCustomerAdditionalFields', 'getVendorAdditionalFields', 'getCustomerGroups', 'getVendorGroups',
        'getCustomerAddresses', 'getVendorAddresses', 'getCustomerAgreements', 'getContragentSubAccountFields',
        'getCustomerSubAccounts', 'getVendorSubAccounts',
        'getProductGroups', 'getWebProductGroups', 'getProvidedServiceGroups', 'getReceivedServiceGroups', 'getInventoryGroups',
        'getProducts', 'getProductsArray', 'getProductsAfter', 'getProvidedServices', 'getReceivedServices', 'getInventories',
        'getProductAdditionalFields', 'getProvidedServiceAdditionalFields', 'getInventoryAdditionalFields',
        'getCharacteristics', 'getCharacteristicValues', 'getCharacteristicValuesArray', 'getPackedProducts',
        'getSubCodeTypes', 'getProductSubCodes', 'getProductPlaces', 'getProductImages', 'getProductsImageArray',
        'getProductsBarcodeArray', 'getProductsOnWay', 'getProductPrices', 'getProvidedServicePrices', 'getProductUnits',
        'getProductPricesAdvance', 'getProductPricesAfter',
        'getProductsRest', 'getProductsRestAfter', 'getProductsRestArray', 'getProductsRestByStore', 'getProductsRestSummary',
        'getProductsRestByStoreAfter', 'getProductsRestAdvance', 'getSubProductsRest',
        'getInventoriesRest', 'getInventoriesRestArray', 'getInventoriesRestByStore', 'getInventoriesRestAdvance',
        'getProductsSelfCost', 'getPriceTypes', 'getDiscountTypes', 'getUnits', 'getStoreGroups', 'getStores', 'getProjects',
        'getTerminals', 'getCashes', 'getUsers', 'getUserPermissions', 'getBankAccounts', 'getCreditBanks', 'getStaffGroups',
        'getStaffs', 'getTransportationMeans', 'getStaffAdditionalFields', 'getGiftCards', 'getGiftCardInfoByCode',
        'getBonusCoeff', 'getLoyaltyCardsByHolder', 'getBonusCardRestByCode', 'getAccountValue', 'getAccountValueDetails',
        'saveCustomer', 'saveVendor', 'saveStaff', 'saveProduct', 'saveProvidedService',
        'getDocTypes', 'getDocAdditionalFields', 'getDocDiscountCard', 'getDocCustomerOrder', 'getDocProductOut',
        'getDocProductOutSingle', 'getDocInventoryOut', 'getDocProductMove', 'getDocInventoryMove',
        'getDocCustomerInventoryReturn', 'getDocProvidedService', 'getDocReceivedService', 'getDocCustomerReturn',
        'getDocProduction', 'getDocAutoService', 'updateRsStatus',
        'saveDocDiscountCard', 'saveDocBonusCard', 'saveDocGiftCard', 'saveDocBonusOperation', 'saveDocCustomerOrder',
        'saveDocProductOut', 'saveDocProductMove', 'saveDocProvidedService', 'saveDocReceivedService',
        'saveDocCustomerReturn', 'saveDocProductIn', 'saveDocProductCancel', 'saveDocCafeOrder', 'saveDocCustomerMoneyIn',
        'saveDocCustomerAdvanceIn', 'saveDocCustomerMoneyReturn', 'saveDocBonusPayment', 'saveDocGiftPayment', 'saveDocProduction',
        'getRealizesJournal', 'getMovesJournal', 'getDocProvidedServicesJournal', 'getDocReceivedServicesJournal',
        'getCustomersOrderJournal', 'getCustomersReturnJournal', 'getCustomersMoneyJournal', 'getVendorsMoneyJournal',
        'getProductionsJournal', 'getEntriesJournal', 'getDiscountCardsJournal', 'getAutoServicesOutJournal',
        'getCustomersCycleReport', 'getVendorsCycleReport', 'getProductsLastInReport', 'getProductsInReturnReport',
        'getCafeOrderDetailedReport',
    ];

    public function test_the_pdf_lists_138_methods(): void
    {
        $this->assertCount(138, array_unique(self::PDF_METHODS));
    }

    public function test_every_pdf_method_is_referenced_in_src(): void
    {
        $source = $this->sourceText();
        $missing = [];

        foreach (self::PDF_METHODS as $method) {
            // the name delimited by a quote or a path separator on both sides,
            // so getProducts does not satisfy getProductsRest and vice versa
            if (! preg_match('/[\'"\/]'.preg_quote($method, '/').'(?:[\'"\/])/', $source)) {
                $missing[] = $method;
            }
        }

        $this->assertSame([], $missing, 'PDF methods without an SDK reference');
    }

    public function test_methods_outside_the_pdf_are_the_known_undocumented_money_endpoints(): void
    {
        preg_match_all('/[\'"\/]((?:get|save|update)[A-Z][A-Za-z]+)[\'"\/]/', $this->sourceText(), $m);
        $referenced = array_unique($m[1]);

        $unknown = array_values(array_diff($referenced, self::PDF_METHODS));
        sort($unknown);

        $this->assertSame(
            ['saveDocCustomerMoneyOut', 'saveDocVendorMoneyIn', 'saveDocVendorMoneyOut', 'saveDocVendorMoneyReturn'],
            $unknown,
            'endpoints the SDK references that the 8.0 PDF does not document'
        );
    }

    private function sourceText(): string
    {
        $text = '';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/src'));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $text .= (string) file_get_contents($file->getPathname());
            }
        }

        return $text;
    }
}
