<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientLabOrder;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of patientLabOrder.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */

use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        // Check if data contains lonic test code
        if (
            isset($data->getData('properties')['testCodeLoinc']) && ($data->getData('properties')['testCodeLoinc'])
                ||
            isset($data->getData('properties')['cptCode']) && ($data->getData('properties')['cptCode'])    
            ) {

            $this->createLabTest($data, $serviceQue);
        }else{
            throw new SynapExceptions(SynapExceptionConstants::INVALID_CPT_LOINC,400);
        }
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
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
    
    /**
     * This function is used to create lab test 
     * @param type $data
     * @param type $serviceQue
     * @return type
     * @throws SynapExceptions
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     */
    private function createLabTest($data, $serviceQue) {
        //get lab order id
        $labOrderId = $data->getData('id'); 
        
        //get lonic/cpt codes
        $loincCptCodes = isset($data->getData('properties')['testCodeLoinc'])?$data->getData('properties')['testCodeLoinc']:$data->getData('properties')['cptCode'];
        $labTestProperty = isset($data->getData('properties')['testCodeLoinc'])?'testCodeLoinc':'cptCode';
        
        foreach($loincCptCodes as $lCodeId){            
            
            $labTestInputData = array(
                'parent' => $labOrderId,
                'objectType' => 'patientLabTest',
                'properties' => array(
                    $labTestProperty => $lCodeId
                )
            ); 
            $labTestResp = $serviceQue->executeQue('ws_oml_create', $labTestInputData);
        }
        return;
    }
    
}
