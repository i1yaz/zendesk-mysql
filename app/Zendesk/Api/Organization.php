<?php


namespace App\Zendesk\Api;

use App\Zendesk\Client;
use Illuminate\Support\Facades\DB;


class Organization implements ApiInterface
{
    private $http;
    private $organizationIds = [];

    public function __construct(Client $http)
    {
        $this->http = $http;
    }

    public function organizations(string $nextPage = null): array
    {
        if (isset($nextPage) && !empty($nextPage)) {
            $nextPageUrl = str_replace(config('zendesk.url'), '', $nextPage);
            $organizations = $this->http->get($nextPageUrl);
        } else {
            $organizations = $this->http->get('/organizations');
        }
        $this->processData($organizations);
        $this->nextPage($organizations);
        return $this->organizationIds;
    }
    /**
     * @param $data
     * @return mixed
     */
    public function processData($organizations)
    {
        foreach ($organizations['organizations'] as $organization) {
            $organizationId = $organization['id'];
            array_push($this->organizationIds, $organizationId);
            //Store tags of organizations
            $tags = $organization['tags'];
            unset($organization['tags']);
            $this->storeTags($tags, $organizationId);
            //Store organization custom fields
            $organizationFields = $organization['organization_fields'];
            unset($organization['organization_fields']);
            $this->storeOrganizationFields($organizationFields, $organizationId);
            // store organization domain names
            $organizationDomainNames = $organization['domain_names'];
            unset($organization['domain_names']);
            $this->storeOrganizationDomainNames($organizationDomainNames, $organizationId);
            //Store organization
            $this->storeOrganization($organization);
        }
        return true;
    }

    /**
     * @param $page
     * @return mixed
     */
    public function nextPage($page)
    {
        if ($page['next_page'] !== null) {
            $this->organizations($page['next_page']);
        }
    }

    private function storeTags($tags, $organizationId)
    {
        if (is_array($tags) && !empty($tags)) {
            foreach ($tags as  $tag) {
                DB::table('organization_tags')->insert(['organization_id' => $organizationId, 'tag' => $tag]);
            }
        }
    }

    private function storeOrganizationFields($organizationFields, $organizationId)
    {
        if (is_array($organizationFields) && !empty($organizationFields)) {
            DB::table('organization_fields')->insert([
                'organization_id' => $organizationId,
                '1password_vault_id' => $organizationFields['1password_vault_id'],
                'asset_database_company_id' => $organizationFields['asset_database_company_id'],
                'gandi_tag' => $organizationFields['gandi_tag'],
                'irregular_prepaid_hours' => $organizationFields['irregular_prepaid_hours'],
                'metis_customer_id' => $organizationFields['metis_customer_id'],
                'monthly_billing_plan' => $organizationFields['monthly_billing_plan'],
                'monthly_hours_warning' => $organizationFields['monthly_hours_warning'],
                'monthly_prepaid_hours' => $organizationFields['monthly_prepaid_hours'],
                'process_st_tag' => $organizationFields['process_st_tag'],
                'SN_Org_Data' => $organizationFields['SN_Org_Data'],
                'time_report_link' => $organizationFields['time_report_link'],
                'xero_contact_id' => $organizationFields['xero_contact_id'],
            ]);
        }
    }

    private function storeOrganizationDomainNames($organizationDomainNames, $organizationId)
    {
        if (is_array($organizationDomainNames) && !empty($organizationDomainNames)) {
            foreach ($organizationDomainNames as $domain) {
                DB::table('organization_domain_names')->insert(['organization_id' => $organizationId, 'domain' => $domain]);
            }
        }
    }
    private function storeOrganization($organization)
    {
        DB::table('organizations')->insert($organization);
    }
}
