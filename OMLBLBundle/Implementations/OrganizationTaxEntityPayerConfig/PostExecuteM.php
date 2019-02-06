<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationTaxEntityPayerConfig;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of organizationTaxEntityPayerConfig.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{
    //payer account type constants
    const PAYER_ACCOUNT = 'metaAccountType:payer';
    
    //payer account subtype constants
    const SUBTYPE_REMITTANCE = 'metaAccountSubType:remittance';
    const SUBTYPE_WRITEOFF = 'metaAccountSubType:writeoff';
    
    //constants for account name
    const REMITTANCE_ACCOUNT = 'Remittance';
    const WRITEOFF_ACCOUNT = 'Adjustment';
    /**
     * Function will create two new payer accounts for given tax entity 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {   
        //create different payer account
        $this->createPayerTaxEntityAccounts($data, $serviceQue);
        return true;
    }

    /**
     * Function will perform some actions after execute delete
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue)
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
     * Function will perform some actions after execute update
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }
    
    /**
     * Function will make call to create payer accounts for given tax entity
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function createPayerTaxEntityAccounts($data, ServiceQueI $serviceQue){
        $payerConfigId = $data->getData('id');
        $taxEntityId = $data->getData('properties')['taxEntityId'];
        $payerId = $data->getData('properties')['organizationPayerId'];
        //fetch payer data
        $payerData = $this->fetchObjectData($payerId,$serviceQue);
        
        //create payer remittance account
        $remittanceId = $this->createPayerAccount($taxEntityId, $payerData['name'].'-'.self::REMITTANCE_ACCOUNT, self::SUBTYPE_REMITTANCE, $payerId, $serviceQue);
        //create payer writeoff account
        $writeOffId = $this->createPayerAccount($taxEntityId, $payerData['name'].'-'.self::WRITEOFF_ACCOUNT, self::SUBTYPE_WRITEOFF, $payerId, $serviceQue);
        
        $updateArr = array();
        $updateArr['accountId'] = $remittanceId;
        $updateArr['writeOffAccountId'] = $writeOffId;
        $this->updateObjectData($payerConfigId,$updateArr,$serviceQue);
        return;
    }
    
    /**
     * Function will fetch data based on objectId
     * 
     * @param type $objectId
     * @param ServiceQueI $serviceQue
     * @return $serviceData
     */
    private function fetchObjectData($objectId, ServiceQueI $serviceQue) {
        //search key to get the objectData
        $searchKey = [];
        $searchKey[0]['objectId'] = $objectId;
        $searchKey[0]['outKey'] = 'response';

        //fetch objectData
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        return $objectData;
    }
    
    /**
     * Function will create accounts based on $taxEntityId, $subType and $payerId
     * @param type $taxEntityId
     * @param type $subType
     * @param type $payerId
     * @param ServiceQueI $serviceQue
     * @return $accountId
     */
    private function createPayerAccount($taxEntityId,$accountName, $subType, $payerId, ServiceQueI $serviceQue){
        $accountArr = array();
        $accountArr['parent'] = $taxEntityId;
        $accountArr['objectType'] = 'account';
        $accountArrProp['accountType'] = self::PAYER_ACCOUNT;
        $accountArrProp['name'] = $accountName;
        $accountArrProp['subType'] = $subType;
        $accountArrProp['payerId'] = $payerId;
        $accountArr['properties'] = $accountArrProp;
        //set all account properties in an array        
        $accountResp = $serviceQue->executeQue("ws_oml_create", $accountArr);
        $accountId = $accountResp['data']['id'];
        return $accountId;
    }
    
    /**
     * Function will update the data based on given params
     * 
     * @param type $objectId, $objectData
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function updateObjectData($objectId,$objectData,ServiceQueI $serviceQue) {
        $updateObjs['conditions']['object'][0] = $objectId;
        $updateObjs['properties'] = $objectData;
        $serviceQue->executeQue("ws_oml_update", $updateObjs);
        return;
    }
}
