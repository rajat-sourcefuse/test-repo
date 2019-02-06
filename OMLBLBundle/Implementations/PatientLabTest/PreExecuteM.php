<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientLabTest;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{
    const TEST_TYPE_LAB = 'metaLabTestType:lab';
    const TEST_TYPE_RADIOLOGY = 'metaLabTestType:radiology';
    
    const LAB_ORDER_PENDING_STATUS = 'metaLabOrderStatus:new';
    const LAB_ORDER_COMPLETE_STATUS = 'metaLabOrderStatus:complete';
    const LAB_TEST_PENDING_STATUS = 'metaLabTestStatus:pending';
    const LAB_TEST_COMPLETE_STATUS = 'metaLabTestStatus:complete';

    /**
     * Function will validate data before execute create
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        // fetching order data from db
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData('parent');
        $searchKey[0]['outKey'] = 'response';
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objIdDataInDB = $aResp['data']['response'];
        
        // if order type is lab then loinc code should come
        if ($objIdDataInDB['orderType'] == self::TEST_TYPE_LAB) {
            if (empty($data->getData('properties')['testCodeLoinc'])) {
                throw new SynapExceptions(SynapExceptionConstants::LOINC_SHOULD_COME,400);
            }
            
            // if radiology code come then setting this to null
            $updateArr = array("cptCode" => "");
            $data->setData($updateArr, 'properties');
        }
        
        // if order type is radiology then radiology/cpt code should come
        if ($objIdDataInDB['orderType'] == self::TEST_TYPE_RADIOLOGY) {
            if (empty($data->getData('properties')['cptCode'])) {
                throw new SynapExceptions(SynapExceptionConstants::RADIOLOGY_SHOULD_COME,400);
            }
            
            // if loinc code come then setting this to null
            $updateArr = array("testCodeLoinc" => "");
            $data->setData($updateArr, 'properties');
        }
        
        return true;
    }

    /**
     * Function will validate data before execute delete
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
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
        return true;
    }

    /**
     * Function will validate data before execute update
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData('conditions')['object'][0];
        $searchKey[0]['outKey'] = 'response';
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $testIdDataInDB = $aResp['data']['response'];
        
        //if value property has notnull value, update status to complete
        if(
                isset($data->getData('properties')['observationValue'])
                &&
                !empty($data->getData('properties')['observationValue'])
           ){
            $data->setData(array('status'=>self::LAB_TEST_COMPLETE_STATUS), 'properties');
        }
        
        $searchKey[0]['objectId'] = $testIdDataInDB['patientLabOrderId'];
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objIdDataInDB = $aResp['data']['response'];
        
        // if order type is lab then loinc code should come
        if ($objIdDataInDB['orderType'] == self::TEST_TYPE_LAB) {
            if (isset($data->getData('properties')['testCodeLoinc']) &&
                    empty($data->getData('properties')['testCodeLoinc'])) {
                throw new SynapExceptions(SynapExceptionConstants::LOINC_SHOULD_COME,400);
            }
            
            // if radiology code come then setting this to null
            $updateArr = array("cptCode" => "");
            $data->setData($updateArr, 'properties');
        }
        
        // if order type is radiology then radiology/cpt code should come
        if ($objIdDataInDB['orderType'] == self::TEST_TYPE_RADIOLOGY) {
            if (isset($data->getData('properties')['cptCode']) &&
                    empty($data->getData('properties')['cptCode'])) {
                throw new SynapExceptions(SynapExceptionConstants::RADIOLOGY_SHOULD_COME,400);
            }
            
            // if loinc code come then setting this to null
            $updateArr = array("testCodeLoinc" => "");
            $data->setData($updateArr, 'properties');
        }
        
        return true;
    }

}
