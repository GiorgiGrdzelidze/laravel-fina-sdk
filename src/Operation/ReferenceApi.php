<?php

declare(strict_types=1);

/**
 * Operation API client for reference/lookup data endpoints.
 */

namespace Fina\Sdk\Laravel\Operation;

use DateTimeInterface;
use Fina\Sdk\Laravel\Client\FinaClient;
use Fina\Sdk\Laravel\Contracts\ArrayPayload;
use Fina\Sdk\Laravel\Contracts\ValidatesPayload;
use Fina\Sdk\Laravel\Endpoints\BaseApi;
use Fina\Sdk\Laravel\Operation\Dto\AccountValueDetailDto;
use Fina\Sdk\Laravel\Operation\Dto\AccountValueDto;
use Fina\Sdk\Laravel\Operation\Dto\AdditionalFieldDescriptorDto;
use Fina\Sdk\Laravel\Operation\Dto\BankAccountDto;
use Fina\Sdk\Laravel\Operation\Dto\DiscountTypeDto;
use Fina\Sdk\Laravel\Operation\Dto\DocTypeDto;
use Fina\Sdk\Laravel\Operation\Dto\GiftCardDto;
use Fina\Sdk\Laravel\Operation\Dto\SaveDocResponse;
use Fina\Sdk\Laravel\Operation\Dto\StaffDto;
use Fina\Sdk\Laravel\Operation\Dto\StaffGroupDto;
use Fina\Sdk\Laravel\Operation\Dto\TransportationMeanDto;
use Fina\Sdk\Laravel\Operation\Dto\UnitDto;
use Fina\Sdk\Laravel\Operation\Dto\UserDto;
use Fina\Sdk\Laravel\Operation\Dto\UserPermissionsDto;
use Fina\Sdk\Laravel\Support\FinaDate;
use Fina\Sdk\Laravel\Support\PayloadValidator;
use Illuminate\Support\Facades\Cache;

/**
 * Provides methods for retrieving reference/lookup data (stores, users, staff,
 * doc types, bank accounts, gift cards, etc.) from the FINA Operation API.
 *
 * Includes optional caching for doc types.
 */
final class ReferenceApi extends BaseApi
{
    public function __construct(FinaClient $client)
    {
        parent::__construct($client, 'operation');
    }

    // -----------------------
    // Raw reference endpoints
    // -----------------------

    /** GET api/operation/getStores */
    public function stores(): array
    {
        return $this->get('getStores', [], 'operation.getStores returned ex');
    }

    /** GET api/operation/getProjects */
    public function projects(): array
    {
        return $this->get('getProjects', [], 'operation.getProjects returned ex');
    }

    /** GET api/operation/getCustomers — returns contragents[] */
    public function customers(): array
    {
        $data = $this->get('getCustomers', [], 'operation.getCustomers returned ex');

        // The API returns customers under 'contragents' key
        return (array) ($data['contragents'] ?? []);
    }

    /** GET api/operation/getVendors — returns contragents[] */
    public function vendors(): array
    {
        $data = $this->get('getVendors', [], 'operation.getVendors returned ex');

        // The API returns vendors under 'contragents' key (same as customers)
        return (array) ($data['contragents'] ?? []);
    }

    /** GET api/operation/getCustomerGroups — returns groups[] */
    public function customerGroups(): array
    {
        $data = $this->get('getCustomerGroups', [], 'operation.getCustomerGroups returned ex');

        // The API returns groups under 'groups' key
        return (array) ($data['groups'] ?? []);
    }

    /** GET api/operation/getVendorGroups — returns groups[] */
    public function vendorGroups(): array
    {
        $data = $this->get('getVendorGroups', [], 'operation.getVendorGroups returned ex');

        // The API returns groups under 'groups' key
        return (array) ($data['groups'] ?? []);
    }

    /** GET api/operation/getProductGroups — returns groups[] */
    public function productGroups(): array
    {
        $data = $this->get('getProductGroups', [], 'operation.getProductGroups returned ex');

        // The API returns groups under 'groups' key
        return (array) ($data['groups'] ?? []);
    }

    /** GET api/operation/getWebProductGroups — returns groups[] */
    public function webProductGroups(): array
    {
        $data = $this->get('getWebProductGroups', [], 'operation.getWebProductGroups returned ex');

        // The API returns groups under 'groups' key
        return (array) ($data['groups'] ?? []);
    }

    /** GET api/operation/getProvidedServiceGroups — returns groups[] */
    public function providedServiceGroups(): array
    {
        $data = $this->get('getProvidedServiceGroups', [], 'operation.getProvidedServiceGroups returned ex');

        // The API returns groups under 'groups' key
        return (array) ($data['groups'] ?? []);
    }

    /** GET api/operation/getReceivedServiceGroups — returns groups[] */
    public function receivedServiceGroups(): array
    {
        $data = $this->get('getReceivedServiceGroups', [], 'operation.getReceivedServiceGroups returned ex');

        // The API returns groups under 'groups' key
        return (array) ($data['groups'] ?? []);
    }

    /** GET api/operation/getInventoryGroups — returns groups[] */
    public function inventoryGroups(): array
    {
        $data = $this->get('getInventoryGroups', [], 'operation.getInventoryGroups returned ex');

        // The API returns groups under 'groups' key
        return (array) ($data['groups'] ?? []);
    }

    /** GET api/operation/getTerminals */
    public function terminals(): array
    {
        return $this->get('getTerminals', [], 'operation.getTerminals returned ex');
    }

    /** GET api/operation/getCashes */
    public function cashes(): array
    {
        return $this->get('getCashes', [], 'operation.getCashes returned ex');
    }

    /** GET api/operation/getCreditBanks */
    public function creditBanks(): array
    {
        return $this->get('getCreditBanks', [], 'operation.getCreditBanks returned ex');
    }

    /** GET api/operation/getPriceTypes */
    public function priceTypes(): array
    {
        return $this->get('getPriceTypes', [], 'operation.getPriceTypes returned ex');
    }

    /** GET api/operation/getStoreGroups — returns groups[] */
    public function storeGroups(): array
    {
        $data = $this->get('getStoreGroups', [], 'operation.getStoreGroups returned ex');

        return (array) ($data['groups'] ?? []);
    }

    /**
     * GET api/operation/getStaffAdditionalFields
     *
     * @return AdditionalFieldDescriptorDto[]
     */
    public function staffAdditionalFields(): array
    {
        $data = $this->get('getStaffAdditionalFields', [], 'operation.getStaffAdditionalFields returned ex');

        return array_map(
            static fn ($f) => AdditionalFieldDescriptorDto::fromArray((array) $f),
            (array) ($data['fields'] ?? [])
        );
    }

    /**
     * GET api/operation/getDocAdditionalFields/{type}
     * Additional fields defined for one document type (see getDocTypes).
     *
     * @return AdditionalFieldDescriptorDto[]
     */
    public function docAdditionalFields(int $docType): array
    {
        $data = $this->get("getDocAdditionalFields/{$docType}", [], 'operation.getDocAdditionalFields returned ex');

        return array_map(
            static fn ($f) => AdditionalFieldDescriptorDto::fromArray((array) $f),
            (array) ($data['fields'] ?? [])
        );
    }

    /**
     * POST api/operation/getAccountValue
     * Turnover totals of one accounting account; `obj` is the id of the object
     * the account is kept for (e.g. the customer id for 1410), `isGel` asks for
     * the GEL equivalent.
     */
    public function accountValue(string $primaryAcc, DateTimeInterface $date, int $obj = 0, string $secondaryAcc = '', bool $isGel = true): AccountValueDto
    {
        $data = $this->post('getAccountValue', [
            'primary_acc' => $primaryAcc,
            'secondary_acc' => $secondaryAcc,
            'date' => FinaDate::toFina($date),
            'obj' => $obj,
            'is_gel' => $isGel,
        ], 'operation.getAccountValue returned ex');

        return AccountValueDto::fromArray($data);
    }

    /**
     * POST api/operation/saveStaff — insert (id = 0) or update a staff member.
     * Response: { "id", "ex" }.
     */
    public function saveStaff(ArrayPayload|array $payload): SaveDocResponse
    {
        if ($payload instanceof ValidatesPayload) {
            PayloadValidator::validate($payload);
        }

        $body = $payload instanceof ArrayPayload ? $payload->toArray() : $payload;

        return SaveDocResponse::fromArray(
            $this->post('saveStaff', $body, 'operation.saveStaff returned ex')
        );
    }

    // -----------------------
    // v8.0 reference endpoints
    // -----------------------

    /**
     * GET api/operation/getTransportationMeans
     *
     * @return TransportationMeanDto[]
     */
    public function transportationMeans(): array
    {
        $data = $this->get('getTransportationMeans', [], 'operation.getTransportationMeans returned ex');

        return array_map(
            fn ($t) => TransportationMeanDto::fromArray((array) $t),
            (array) ($data['transportation_means'] ?? [])
        );
    }

    /**
     * POST api/operation/getAccountValueDetails
     * Returns detailed account values broken down by entity.
     *
     * @return AccountValueDetailDto[]
     */
    public function accountValueDetails(string $account, DateTimeInterface $date, ?string $currency = null): array
    {
        $body = [
            'account' => $account,
            'date' => FinaDate::toFina($date),
        ];

        if ($currency !== null) {
            $body['currency'] = $currency;
        }

        $data = $this->post('getAccountValueDetails', $body, 'operation.getAccountValueDetails returned ex');

        return array_map(
            fn ($v) => AccountValueDetailDto::fromArray((array) $v),
            (array) ($data['values'] ?? [])
        );
    }

    // -----------------------
    // Typed reference endpoints
    // -----------------------

    /**
     * GET api/operation/getUsers
     * Response: {users: [{id,name,type}, ...], ex:null}
     *
     * @return UserDto[]
     */
    public function users(): array
    {
        $data = $this->get('getUsers', [], 'operation.getUsers returned ex');

        return array_map(
            fn ($u) => UserDto::fromArray((array) $u),
            (array) ($data['users'] ?? [])
        );
    }

    /**
     * GET api/operation/getUserPermissions/{user}
     * Response: {permissions: {...}, ex:null}
     */
    public function userPermissions(int $userId): UserPermissionsDto
    {
        $data = $this->get('getUserPermissions/'.$userId, [], 'operation.getUserPermissions returned ex');

        return UserPermissionsDto::fromArray($data);
    }

    /**
     * GET api/operation/getBankAccounts
     * Response: {accounts: [...], ex:null}
     *
     * @return BankAccountDto[]
     */
    public function bankAccounts(): array
    {
        $data = $this->get('getBankAccounts', [], 'operation.getBankAccounts returned ex');

        return array_map(
            fn ($a) => BankAccountDto::fromArray((array) $a),
            (array) ($data['accounts'] ?? [])
        );
    }

    /**
     * GET api/operation/getStaffGroups
     * Response: {groups: [...], ex:null}
     *
     * @return StaffGroupDto[]
     */
    public function staffGroups(): array
    {
        $data = $this->get('getStaffGroups', [], 'operation.getStaffGroups returned ex');

        return array_map(
            fn ($g) => StaffGroupDto::fromArray((array) $g),
            (array) ($data['groups'] ?? [])
        );
    }

    /**
     * GET api/operation/getStaffs
     * Response: {staffs: [...], ex:null}
     *
     * @return StaffDto[]
     */
    public function staffs(): array
    {
        $data = $this->get('getStaffs', [], 'operation.getStaffs returned ex');

        return array_map(
            fn ($s) => StaffDto::fromArray((array) $s),
            (array) ($data['staffs'] ?? [])
        );
    }

    /**
     * GET api/operation/getGiftCards
     * Response: {gifts: [...], ex:null}
     *
     * @return GiftCardDto[]
     */
    public function giftCards(): array
    {
        $data = $this->get('getGiftCards', [], 'operation.getGiftCards returned ex');

        return array_map(
            fn ($g) => GiftCardDto::fromArray((array) $g),
            (array) ($data['gifts'] ?? [])
        );
    }

    /**
     * GET api/operation/getDiscountTypes
     * Response: {types: [...], ex:null}
     *
     * @return DiscountTypeDto[]
     */
    public function discountTypes(): array
    {
        $data = $this->get('getDiscountTypes', [], 'operation.getDiscountTypes returned ex');

        return array_map(
            fn ($t) => DiscountTypeDto::fromArray((array) $t),
            (array) ($data['types'] ?? [])
        );
    }

    /**
     * GET api/operation/getUnits
     * Response: {units: [...], ex:null}
     *
     * @return UnitDto[]
     */
    public function units(): array
    {
        $data = $this->get('getUnits', [], 'operation.getUnits returned ex');

        return array_map(
            fn ($u) => UnitDto::fromArray((array) $u),
            (array) ($data['units'] ?? [])
        );
    }

    // -----------------------
    // Doc Types (typed + helpers + cache)
    // -----------------------

    /**
     * GET api/operation/getDocTypes
     * Response: {types: [...], ex:null}
     *
     * @return DocTypeDto[]
     */
    public function docTypes(): array
    {
        $data = $this->get('getDocTypes', [], 'operation.getDocTypes returned ex');

        // The API returns data under 'doc_types' key, not 'types'
        $rawTypes = (array) ($data['doc_types'] ?? []);

        // Convert the API response to DocTypeDto objects
        return array_map(
            fn ($type) => DocTypeDto::fromArray((array) $type),
            $rawTypes
        );
    }

    /**
     * Cached doc types.
     *
     * @return DocTypeDto[]
     */
    public function docTypesCached(?int $ttlSeconds = null): array
    {
        $ttl = $ttlSeconds ?? (int) config('fina.cache.doc_types_ttl', 3600);

        return Cache::remember(
            $this->docTypesCacheKey(),
            $ttl,
            fn () => $this->docTypes()
        );
    }

    /**
     * @return DocTypeDto[]
     */
    public function supportedDocTypes(): array
    {
        return array_values(array_filter(
            $this->docTypes(),
            static fn (DocTypeDto $t) => $t->apiSupported === true
        ));
    }

    /**
     * @return DocTypeDto[]
     */
    public function supportedDocTypesCached(?int $ttlSeconds = null): array
    {
        return array_values(array_filter(
            $this->docTypesCached($ttlSeconds),
            static fn (DocTypeDto $t) => $t->apiSupported === true
        ));
    }

    /** Find a single doc type by its numeric type ID (uncached). */
    public function findDocType(int $id): ?DocTypeDto
    {
        foreach ($this->docTypes() as $t) {
            if ($t->type === $id) {
                return $t;
            }
        }

        return null;
    }

    /** Check whether a doc type is API-supported (uncached). */
    public function isDocTypeSupported(int $id): bool
    {
        $t = $this->findDocType($id);

        return $t?->apiSupported === true;
    }

    private function docTypesCacheKey(): string
    {
        $prefix = (string) config('fina.cache.prefix', 'fina-sdk');

        // avoid collisions across envs/accounts
        $baseUrl = (string) config('fina.base_url', '');
        $login = (string) config('fina.login', '');

        return $prefix.':doc-types:'.sha1($baseUrl.'|'.$login);
    }
}
