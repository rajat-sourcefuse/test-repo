<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationTaxEntity;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of organizationTaxEntity.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{
    //system account type constants
    const SYSTEM_ACCOUNT = 'metaAccountType:system';
    
    //system account subtype constants
    const SUBTYPE_REVENUE = 'metaAccountSubType:revenue';
    const SUBTYPE_ASSET = 'metaAccountSubType:asset';
    
    //constants for account name
    const CASH_ACCOUNT = 'Cash';
    const ACH_ACCOUNT = 'ACH';
    const CHECK_ACCOUNT = 'Check';
    const CC_ACCOUNT = 'CC';
    const REVENUE_ACCOUNT = 'Revenue';
    /**
     * Function will create four new system accounts for tax entity 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $taxEntityId = $data->getData('id');
        
        //create different tax entity account
        $this->createSystemTaxEntityAccounts($taxEntityId, $serviceQue);
        
        //create a location account assigned for a given location and tax entity
        //$this->processLocationAccount($data, $serviceQue);
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
        //create a location account assigned for a given location and tax entity
        //$this->processLocationAccount($data, $serviceQue);
        return true;
    }
    
    /**
     * Function will make call to create system accounts for given tax entity
     * @param type $taxEntityId
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function createSystemTaxEntityAccounts($taxEntityId, ServiceQueI $serviceQue){
        //create asset cash account
        $this->createSystemAccount($taxEntityId, self::CASH_ACCOUNT, self::SUBTYPE_ASSET, $serviceQue);
        //create revenue account
        $this->createSystemAccount($taxEntityId, self::REVENUE_ACCOUNT, self::SUBTYPE_REVENUE, $serviceQue);
        //create asset credit card account
        $this->createSystemAccount($taxEntityId, self::CC_ACCOUNT, self::SUBTYPE_ASSET, $serviceQue);
        //create asset cheque account
        $this->createSystemAccount($taxEntityId, self::CHECK_ACCOUNT, self::SUBTYPE_ASSET, $serviceQue);
        //create ach account
        $this->createSystemAccount($taxEntityId, self::ACH_ACCOUNT, self::SUBTYPE_ASSET, $serviceQue);
        return;
    }
    
    /**
     * Function will create accounts based on $taxEntityId and $subType
     * @param type $taxEntityId
     * @param type $subType
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function createSystemAccount($taxEntityId, $accountName, $subType,ServiceQueI $serviceQue){
        $accountArr = array();
        $accountArr['objectType'] = 'account';
        $accountArr['parent'] = $taxEntityId;
        $accountArrProp['accountType'] = self::SYSTEM_ACCOUNT;
        $accountArrProp['name'] = $accountName;
        $accountArrProp['subType'] = $subType;
        $accountArr['properties'] = $accountArrProp;
        //set all account properties in an array        
        $serviceQue->executeQue("ws_oml_create", $accountArr);
        return;
    }
}
