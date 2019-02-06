<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientLabTest;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of patientLabTest.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */

use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{
    const LAB_ORDER_PENDING_STATUS = 'metaLabOrderStatus:new';
    const LAB_ORDER_COMPLETE_STATUS = 'metaLabOrderStatus:complete';
    const LAB_TEST_PENDING_STATUS = 'metaLabTestStatus:pending';
    const LAB_TEST_COMPLETE_STATUS = 'metaLabTestStatus:complete';

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
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
        //check if properties contain status property
        if (
                isset($data->getData('properties')['status'])
                &&
                $data->getData('properties')['status']==self::LAB_TEST_COMPLETE_STATUS
            ) {
            //fetch patient lab test data 
            $searchKey = array(array(
                    'objectId' => $data->getData('conditions')['object'][0],
                    'outKey' => 'response'
            ));
            $respLabTest = $serviceQue->executeQue("ws_oml_read", $searchKey);
            
            
            if (!empty($respLabTest['data']['response']['patientLabOrderId'])) {
                //fetch all patient lab test having parent id as given labOrderId and status as not completed or null
                $searchKey = [];
                $searchKey[0]['conditions'][] = array('patientLabOrderId' => $respLabTest['data']['response']['patientLabOrderId']); 
                
                $searchKey[0]['conditions'][] = array(
                    array('status' => array('ISNULL'=>'')),
                    'OR',
                    array('status' => array('NE'=>self::LAB_TEST_COMPLETE_STATUS))
                    ); 
                $searchKey[0]['type'] = 'patientLabTest';
                $searchKey[0]['outKey'] = 'response';
                //read all claims based on batch id
                $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
                $patientLabTest = $resp['data']['response'];
                
                //if no lab test having status complete, mark lab order as complete
                if (empty($patientLabTest)) {
                    $updateObjs = array();
                    $updateObjs['conditions']['object'][0] = $respLabTest['data']['response']['patientLabOrderId'];
                    $updateObjs['properties']['orderStatus'] = self::LAB_ORDER_COMPLETE_STATUS;        
                    $serviceQue->executeQue("ws_oml_update", $updateObjs);
                }
            }
        }
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
