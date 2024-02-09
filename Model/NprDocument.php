<?php

namespace IllinoisPublicMedia\NprCds\Model;

use ExpressionEngine\Service\Model\Model;

class NprCdsDocument extends Model
{
    // Documentation: https://docs.expressionengine.com/latest/development/services/model/building-your-own.html
    // You can get this all instances of this model by using:
    // ee('Model')->get('npr_cds:NprCdsDocument')->all();

    protected static $_primary_key = 'ee_id';

    protected static $_table_name = 'npr_cds_documents';

    /**
     * Core Document
     */
    protected $title;

    /**
     * required id
     *
     * id - Each document in CDS must have an ID. IDs must be compliant with the regex specified in the documentId schema.
     */
    protected $npr_id;

    /**
     * corresponds to { required: [ profiles: [ 1: "profiles" ] ] }
     * Each document in CDS must specify a list of profiles that it is compliant with. Profiles are represented by links to CDS profile documents. For more informaiton, see the “Profiles” section below.
     */
    protected $profile_compliance;

    /**
     * optional: Documents, by default, can be modified by any client that shares a prefix with its ID. In addition, it can be modified by any client that shares an authorizedOrgServiceIds entry with it. This allows documents to be modified by clients that they were not originally created by.
     */
    protected $authorized_org_service_ids;

    /**
     * optional: This field is for CDS’ internal use only. On creation, this field should be absent from documents; if present, it will be ignored. For updates, if meta is present on retrieval, it should be returned as-is to CDS.
     */
    protected $meta;

    /**
     * getter for profile_compliance
     */
    protected function get__profile_compliance(): array
    {
        return explode(',', $this->profile_compliance);
    }

    /**
     * setter for profile_compliance
     */
    protected function set__profile_compliance(array $value)
    {
        $profiles = implode(',', $value);
        $this->setRawProperty('profile_compliance', $profiles);
    }

    /**
     * getter for authorized_service_org_ids
     */
    protected function get__authorized_service_org_ids(): array
    {
        return explode(',', $this->authorized_org_service_ids);
    }

    /**
     * setter for authorized_service_org_ids
     */
    protected function set__authorized_service_org_ids(array $value)
    {
        $orgs = implode(',', $value);
        $this->setRawProperty('authorized_service_org_ids', $orgs);
    }
}
