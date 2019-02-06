<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientOrganizationData;

use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    const ORG_SPECIFIC_SERVICE_DATA = 'patientOrganizationData';

    /**
     * @description function will perform some actions after execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        // get patient service data
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData('id');
        $searchKey[0]['outKey'] = 'response';
        $patientServiceResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        if (!empty($patientServiceResp['data']['response']['serviceDateIn'])) {
            // get patient id with patient encounter id
            $searchKey = [];
            $searchKey[0]['objectId'] = $data->getData('parent');
            $searchKey[0]['outKey'] = 'response';
            $patientEncounterResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientId = $patientEncounterResp['data']['response']['patientId'];
            $organizationId = $patientEncounterResp['data']['response']['organizationId'];

            // fetching patient's service data. if already there then we need to update
            $searchKey = [];
            $searchKey[0]['type'] = self::ORG_SPECIFIC_SERVICE_DATA;
            $searchKey[0]['conditions'][] = ['patientId' => $patientId];
            $searchKey[0]['outKey'] = 'response';
            $dataResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

            $lastServiceDate = DateTimeUtility::convertFormat(
                            $data->getData('properties')['serviceDateIn'], DateTimeUtility::DB_DATE_FORMAT, DateTimeUtility::DATE_FORMAT);

            if (!empty($dataResp['data']['response'])) {
                // update patientServiceData last service date
                $updateObjs = [];
                $updateObjs['conditions']['object'][0] = $dataResp['data']['response'][0]['id'];
                $updateObjs['properties']['lastServiceDate'] = $lastServiceDate;
                $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
            } else {
                // creating patientServiceData with last service date
                $objectData['parent'] = $patientId;
                $objectData['properties'] = [
                    'organizationId' => $organizationId,
                    'lastServiceDate' => $lastServiceDate
                ];
                $objectData['objectType'] = self::ORG_SPECIFIC_SERVICE_DATA;
                $resp = $serviceQue->executeQue('ws_oml_create', $objectData);
            }
        }
    }

    /**
     * @description function will perform some actions after execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * @description function will perform some actions after execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will perform some actions after execute get
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteGet($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will perform some actions after execute view
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }

}
