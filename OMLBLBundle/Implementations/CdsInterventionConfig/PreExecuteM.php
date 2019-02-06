<?php

namespace SynapEssentials\OMLBLBundle\Implementations\CdsInterventionConfig;

use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\Utilities\SessionUtility;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Utilities\OtherUtility;

class PreExecuteM implements PreExecuteI
{

    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        return;
    }

    /**
     * Function will validate data before execute delete
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return;
    }

    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        $sessionUtil = SessionUtility::getInstance();
        $objectId = $data->getData('objectId');
        $inputData = $data->getData('input');

        
        $objectData['objectType'] = 'patientEducationResourceLog';
        //$objectData['properties']['cdsInterventionConfigId'] = $objectId;
        
        // Check if patientId is passed in request or throw exception
        if (isset($inputData['other']) && !empty($inputData['other']['patientId'])) {
            $objectData['parent'] = $inputData['other']['patientId'];
        } else {
            throw new SynapExceptions(SynapExceptionConstants::INVALID_PATIENT,400);
        }
                
        // Check if patient EncounterId exist then pass along with the request
        if (isset($inputData['other']) && !empty($inputData['other']['patientEncounterId'])) {
            $objectData['properties']['patientEncounterId'] = $inputData['other']['patientEncounterId'];
        }
        
        // Get Url being redirected from passed object Id
        $searchKey = [];
        $searchKey[0]['objectId'] = $objectId;
        $searchKey[0]['requiredAdditionalInfo'] = 0;
        $searchKey[0]['requiredAdditionalInfo'] = 0;
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        
        $objectData['properties']['supportUrl'] = $resp['data']['response']['supportUrl'];
        
        // get logged in user Id
        $userType = $sessionUtil->getUserType();
        if ($userType == 'organizationEmployee') {
            $objectData['properties']['organizationEmployeeId'] = $sessionUtil->getOrganizationEmployeeId();
        } else if ($userType == 'patient') {
            $objectData['properties']['patientId'] = $sessionUtil->getPatientId();
        }
        
        $serviceQue->executeQue('ws_oml_create', $objectData);
        
//        echo "<pre>";
//        print_r($_SERVER);
    //    print_r($inputData);
  //      exit;
        return true;
    }

}
