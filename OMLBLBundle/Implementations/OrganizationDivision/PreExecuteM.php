<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationDivision;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
/*
 * BL class for preExecute of organizationDivision.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;

class PreExecuteM implements PreExecuteI {

    /**
     * @param type        $data
     * @param ServiceQueI $serviceQue
     *
     * @return bool
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        //AP-211 part1 begins///////
        $isLocation = (isset($data->getData('properties')['isLocation']) && !empty($data->getData('properties')['isLocation']) ) ? filter_var($data->getData('properties')['isLocation'], FILTER_VALIDATE_BOOLEAN) : false;
        if ($isLocation) {
            //taxEntityId is Mandatory for type location
            if (!isset($data->getData('properties')['taxEntityId']) || empty($data->getData('properties')['taxEntityId'])) {
                throw new SynapExceptions(SynapExceptionConstants::TAXENTITY_MANDATORY_LOCATION,400);
            }

            //eqRequest is Mandatory for type location AP-270
            if (!isset($data->getData('properties')['eqRequest']) || empty($data->getData('properties')['eqRequest'])) {
                throw new SynapExceptions(SynapExceptionConstants::EQREQUEST_MANDATORY_LOCATION,400);
            }
        }

        //AP-211  part1 ends///////
        return true;
    }

    /**
     * @param type        $data
     * @param ServiceQueI $serviceQue
     *
     * @return bool
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        // fetching division detail data from db
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData();
        $searchKey[0]['outKey'] = 'response';
        $divisionResp = $serviceQue->executeQue('ws_oml_read', $searchKey);
        $objDataInDB = $divisionResp['data']['response'];

        if (!empty($objDataInDB) && !$objDataInDB['allowDelete']) {
            throw new SynapExceptions(SynapExceptionConstants::CAN_NOT_DELETE_REQ,400, array('object' => 'organizationDivision'));
        }
    }

    /**
     * Function will perform some actions after execute get.
     *
     * @param type        $data
     * @param ServiceQueI $serviceQue
     *
     * @return bool
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will perform some actions after execute view.
     *
     * @param type        $data
     * @param ServiceQueI $serviceQue
     *
     * @return bool
     */
    public function preExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * @param type        $data
     * @param ServiceQueI $serviceQue
     *
     * @return bool
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        // AP-211 part2 begins///////
        //$isLocationPassed = (isset($data->getData('properties')['isLocation']) && !empty($data->getData('properties')['isLocation']) ) ? filter_var($data->getData('properties')['isLocation'], FILTER_VALIDATE_BOOLEAN) : false;
        $isLocationPassed = false;
        $isLocation = null;
        if (isset($data->getData('properties')['isLocation'])) {
            $isLocationPassed = true;
            $isLocation = $data->getData('properties')['isLocation'];
        }
        // Check existing data
        $condition = $data->getData('conditions');
        $divisionId = $condition['object'][0];

        $searchKey = [];
        $searchKey[0]['objectId'] = $divisionId;
        $searchKey[0]['sendNullKey'] = 1;
        $searchKey[0]['outKey'] = 'response';
        $existingData = $serviceQue->executeQue('ws_oml_read', $searchKey);
        if (!$isLocationPassed) {
            $isLocation = $existingData['data']['response']['isLocation'];
        }
        if ((!is_null($isLocation))&&($isLocation == true)) {
            // AP--211 , 
            // taxEntityId is Mandatory for type location
            if (!isset($existingData['data']['response']['taxEntityId']) && (!isset($data->getData('properties')['taxEntityId']) || empty($data->getData('properties')['taxEntityId']))) {
                throw new SynapExceptions(SynapExceptionConstants::TAXENTITY_MANDATORY_LOCATION,400);
            }

            // eqRequest is Mandatory for type location AP-270 for update case
            if (!isset($existingData['data']['response']['eqRequest']) && (!isset($data->getData('properties')['eqRequest']) || empty($data->getData('properties')['eqRequest']))) {
                throw new SynapExceptions(SynapExceptionConstants::EQREQUEST_MANDATORY_LOCATION,400);
            }
        } else {
            // AP-402 Check if is_location was TRUE before and now requested to update to FALSE

            if (($isLocationPassed) && ($isLocation==false) && ($existingData['data']['response']['isLocation']==true)) {

                throw new SynapExceptions(SynapExceptionConstants::LOCATION_TO_DIVISION_NOT_ALLOWED,400);
            }
        }


        return true;
    }

}
