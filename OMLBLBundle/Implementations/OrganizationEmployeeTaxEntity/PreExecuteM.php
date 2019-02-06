<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationEmployeeTaxEntity;

use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI {

    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        //following three parameters are mandatory so not putting a check here
        $taxEntityId = $data->getData('properties')['taxEntityId'];

        $employeeId = $data->getData('properties')['employeeId'];
        $searchKey = [];
        $searchKey[0]['type'] = 'organizationEmployeeTaxEntity';
        $searchKey[0]['conditions'][] = array('employeeId' => $employeeId);
        $searchKey[0]['conditions'][] = array('taxEntityId' => $taxEntityId);
        $searchKey[0]['outKey'] = 'response';
        $searchKey[0]['sendNullKey'] = 1;
        $searchKey[0]['requiredAdditionalInfo'] = 0;
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        if (!empty($aResp['data']['response'])) {
            throw new SynapExceptions(SynapExceptionConstants::EMPLOYEE_TE_EXISTS,400);
        }
        return true;
    }

    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        
    }

    public function preExecuteGet($data, ServiceQueI $serviceQue) {
        
    }

    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        $object = $data->getData('conditions')['object'][0];

        $searchExistKey = [];
        $searchExistKey[0]['objectId'] = $object;
        $searchExistKey[0]['sendNullKey'] = 1;
        $searchExistKey[0]['outKey'] = 'response';
        $existingData = $serviceQue->executeQue('ws_oml_read', $searchExistKey);


        $taxEntityId = isset($data->getData('properties')['taxEntityId']) ? $data->getData('properties')['taxEntityId'] : $existingData['data']['response']['taxEntityId'];

        $employeeId = isset($data->getData('properties')['employeeId']) ? $data->getData('properties')['employeeId'] : $existingData['data']['response']['employeeId'];

        $searchKey = [];
        $searchKey[0]['type'] = 'organizationEmployeeTaxEntity';
        $searchKey[0]['conditions'][] =  array('employeeId' => $employeeId);
         $searchKey[0]['conditions'][] = array('taxEntityId' => $taxEntityId);
        $searchKey[0]['outKey'] = 'response';
        $searchKey[0]['sendNullKey'] = 1;
        $searchKey[0]['requiredAdditionalInfo'] = 0;
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        if (!empty($aResp['data']['response'])) {
            throw new SynapExceptions(SynapExceptionConstants::EMPLOYEE_TE_EXISTS,400);
            //"Given  taxEntity already exists for the provided employee."
        }
        return true;
    }

    public function preExecuteView($data, ServiceQueI $serviceQue) {
        
    }

}
