<?php

namespace SynapEssentials\OMLBLBundle\Implementations\BillingDiagnosis;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for preExecute of billingDiagnosis.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;

class PreExecuteM implements PreExecuteI
{
    const CLAIM_NEW_STATUS = 'metaClaimStatus:new';

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    { 
        $claimId = $data->getData('parent');
        //charges will be created if claim status is new and current level is 1
        $claimData = $this->fetchObjectData($claimId, $serviceQue);
        if(!($claimData['status']==self::CLAIM_NEW_STATUS && $claimData['currentLevel']==1)){
            throw new SynapExceptions(SynapExceptionConstants::BILLING_DIAGNOSIS_CREATION_CRITERIA_FAIL, 406);
        }
        
        $billingDiagnosis = $this->getBillingDiagnosis($claimId, $serviceQue);
        if(empty($billingDiagnosis)){
            $data->setData(array('isPrimary' => 1), 'properties');
        }
        
        //update chargeCount billing diagnosis flag charge creation
        $this->setChargeCount($data, $serviceQue);
        
        
        $this->markPrimaryDiagnosis($data, $serviceQue);
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
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
    public function preExecuteGet($data, ServiceQueI $serviceQue)
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
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $billingDiagnosisId = $data->getData('conditions')['object'][0];
        
        $isPrimary = isset($data->getData('properties')['isPrimary'])?$data->getData('properties')['isPrimary']:'';
        if($isPrimary==1){
            $getDiagnosisData = $this->fetchObjectData($billingDiagnosisId, $serviceQue);
        
            $claimId = $getDiagnosisData['patientClaimId'];
            $billingDiagnosis = $this->getBillingDiagnosis($claimId, $serviceQue, "1");
            
            if(!empty($billingDiagnosis) && $billingDiagnosis[0]['id']!=$billingDiagnosisId){
                //set false existing primary diagnosis
                $this->setPrimaryFalse($billingDiagnosis[0]['id'], $serviceQue);
            }
        }
        return true;
    }
    
    /**
     * Function will unmark primary diagnosis as non primary and mark the current diagnosis as primary
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    public function markPrimaryDiagnosis($data, ServiceQueI $serviceQue)
    {        
        $isPrimary = isset($data->getData('properties')['isPrimary'])?$data->getData('properties')['isPrimary']:'';
        if($isPrimary==1){
        
            $claimId = $data->getData('parent');
            $billingDiagnosis = $this->getBillingDiagnosis($claimId, $serviceQue, "1");
            
            if(!empty($billingDiagnosis)){
                //set false existing primary diagnosis
                $this->setPrimaryFalse($billingDiagnosis[0]['id'], $serviceQue);
            }
        }
        return;
    }
    
    /**
     * this function will get patient billing diagnosis based on claim id
     * @param  $claimId
     * @return $billingDiagnosis
     */
    private function getBillingDiagnosis($claimId, ServiceQueI $serviceQue, $isPrimary = "") {
        $searchKey = [];
        $searchKey[0]['conditions'][] = array('patientClaimId' => $claimId);
        if(!empty($isPrimary)){
            $searchKey[0]['conditions'][] = array('isPrimary' => $isPrimary);
        }
        $searchKey[0]['type'] = 'billingDiagnosis';
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $billingDiagnosisData = $resp['data']['response'];
        return $billingDiagnosisData;
    }
    
    /**
     * Function will fetch the object data based on objectId
     * 
     * @param type $objectId
     * @param ServiceQueI $serviceQue
     * @return $objectData
     */
    private function fetchObjectData($objectId, ServiceQueI $serviceQue){
        
        //fetch object date
        $searchKey = [];
        $searchKey[0]['objectId'] = $objectId;
        $searchKey[0]['requiredAdditionalInfo'] = "0";
        $searchKey[0]['outKey'] = 'response';

        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        return $objectData;
    }
    
    /**
     * Function will set the existing primary diagnosis to nonprimary
     * 
     * @param type $billingDiagnosisId
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function setPrimaryFalse($billingDiagnosisId, ServiceQueI $serviceQue){        
        
        //set primary flag as false
        $updateObjs = array();
        $updateObjs['conditions']['object'][0] = $billingDiagnosisId;
        $updateObjs['properties']['isPrimary'] = 0;
        $serviceQue->executeQue("ws_oml_update", $updateObjs);
        return;
    }
    
    /**
     * Function will process the flag chargeCount for billing diagnosis object
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function setChargeCount($data, ServiceQueI $serviceQue){
        
        //check if same billing diagnosis exists for same claim
        $claimId = $data->getData('parent');
        $diagnosisCode = $data->getData('properties')['diagnosisCode'];
        $billingDiagnosisChargeCount = $this->getChargeCount($claimId, $diagnosisCode, $serviceQue);
        
        //update charge count in billing diagnosis
        $data->setData(array('chargeCount' => $billingDiagnosisChargeCount+1), 'properties');
        return;
    }
    
    /**
     * this function will get chargeCount based on claim id and diagnosis
     * @param  $claimId
     * @return $billingDiagnosis
     */
    private function getChargeCount($claimId, $diagnosisCode, ServiceQueI $serviceQue) {
        $searchKey = [];
        $searchKey[0]['conditions'][] = array('patientClaimId' => $claimId);
        $searchKey[0]['conditions'][] = array('diagnosisCode' => $diagnosisCode);
        $searchKey[0]['type'] = 'billingDiagnosis';
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $billingDiagnosisData = $resp['data']['response'];

        $chargeCount = 0;
        if (!empty($billingDiagnosisData)) {
            $chargeCount = $billingDiagnosisData[0]['chargeCount'];
        }
        return $chargeCount;
    }

}