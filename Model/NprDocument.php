<?php

namespace IllinoisPublicMedia\NprCds\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * see:
 *   - https://npr.github.io/content-distribution-service/profiles/document.html
 *   - https://content.api.npr.org/v1/profiles/document
 */
class NprCdsDocument extends Model
{
    protected static $_primary_key = 'ee_id';

    protected static $_table_name = 'npr_cds_documents';

    protected static $_relationships = array(
        'Profiles' => array(
            'type' => 'hasMany',
            'model' => 'npr_cds:NprCdsProfile',
        ),
    );

    protected int $ee_id;

    /**
     * required id
     *
     * id - Each document in CDS must have an ID. IDs must be compliant with the regex specified in the documentId schema.
     */
    protected string $npr_id;

    /**
     * Core Document
     */
    protected string $title;

    /**
     * corresponds to { required: [ profiles: [ 1: "profiles" ] ] }
     * Each document in CDS must specify a list of profiles that it is compliant with. Profiles are represented by links to CDS profile documents. For more informaiton, see the “Profiles” section below.
     */
    protected array $profiles;

    /**
     * optional: Documents, by default, can be modified by any client that shares a prefix with its ID. In addition, it can be modified by any client that shares an authorizedOrgServiceIds entry with it. This allows documents to be modified by clients that they were not originally created by.
     */
    protected string $authorized_org_service_ids;

    /**
     * optional: This field is for CDS’ internal use only. On creation, this field should be absent from documents; if present, it will be ignored. For updates, if meta is present on retrieval, it should be returned as-is to CDS.
     */
    protected $meta;

    // begin aggregation profile
    /**
     * profile: aggregation
     * required: no
     *
     * NPR-specific string array of newsletter abbreviations associated with this aggregation.
     */
    protected array $related_newsletter_ids;

    /**
     * profile: aggregation
     * required: yes
     *
     * Some notes about the items array:
     *   - The items array can be empty
     *   - There can be no more than 100 entries in the items array
     *   - The items array should be considered already sorted by publishers
     *   - items links cannot have rels
     */
    protected array $items;
    // end aggregation profile

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
