<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationDivision;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of organizationDivision.
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
    
    
    /**
     * Function will execute after creation of organization division
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {   
        //create location account if isLocation is marked as true for given object
        $isLocation = isset($data->getData('properties')['isLocation'])?$data->getData('properties')['isLocation']:'';
        if($isLocation){            
            $locationId = $data->getData('id');
            $taxEntityId = isset($data->getData('properties')['taxEntityId'])?$data->getData('properties')['taxEntityId']:'';
            if(!empty($taxEntityId)){
                $locationName = $data->getData('properties')['name'];
                //create a location account assigned to a given location and tax entity
                $this->processLocationAccount($locationId,$locationName,$taxEntityId, $serviceQue);
            }
        }
        
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
        $isLocation = isset($data->getData('properties')['isLocation'])?$data->getData('properties')['isLocation']:'';
        
        $objectId = $data->getData('conditions')['object'][0];
        $objectData = $this->fetchObjectData($objectId,$serviceQue);
        $objectIsLocation = isset($objectData['isLocation'])?$objectData['isLocation']:'';
        
        if($isLocation || $objectIsLocation){                        
            //check if taxEntityId is in request itself
            $taxEntityId = isset($data->getData('properties')['taxEntityId'])?$data->getData('properties')['taxEntityId']:'';
            
            //if taxEntityId is empty, get it from location data
            if(empty($taxEntityId)){
                $taxEntityId = isset($objectData['taxEntityId'])?$objectData['taxEntityId']:'';
            }
            if(!empty($taxEntityId)){
                $locationName = isset($data->getData('properties')['name'])?$data->getData('properties')['name']:$objectData['name'];
                //create a location account assigned for a given location and tax entity
                $this->processLocationAccount($objectId,$locationName,$taxEntityId, $serviceQue);
            }
        }
        return true;
    }
    
    /**
     * Function will check for location account and if not exist will create based on tax entity
     * @param type $locationId
     * @param type $locationName
     * @param type $taxEntityId
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function processLocationAccount($locationId,$locationName,$taxEntityId,ServiceQueI $serviceQue){
        $locationAccount = $this->chkLocationAccount($locationId,$taxEntityId, $serviceQue);
        if(empty($locationAccount)){
            //if location account does not exist, create one
            $this->createLocationAccount($locationId,$locationName,$taxEntityId, $serviceQue);
        }
        return;
    }
    
    /**
     * Function will check for the location account
     * @param type $locationId
     * @param type $taxEntityId
     * @param ServiceQueI $serviceQue
     * @return $objData
     */
    private function chkLocationAccount($locationId,$taxEntityId,ServiceQueI $serviceQue){
        $searchKey = [];
        $searchKey[0]['type'] = 'account';
        $searchKey[0]['conditions'][] = array('divisionId'=>$locationId);
        $searchKey[0]['conditions'][] = array('organizationTaxEntityId'=>$taxEntityId);
        $searchKey[0]['conditions'][] = array('accountType'=>self::SYSTEM_ACCOUNT);
        $searchKey[0]['conditions'][] = array('subType'=>self::SUBTYPE_REVENUE);
        $searchKey[0]['requiredAdditionalInfo'] = "0";
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objData = $resp['data']['response'];
        return $objData;
    }
    
    /**
     * Function will create location account based on $taxEntityId and $locationId
     * @param type $locationId
     * @param type $locationName
     * @param type $taxEntityId
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function createLocationAccount($locationId,$locationName,$taxEntityId,ServiceQueI $serviceQue){
        $accountArr = array();
        $accountArr['objectType'] = 'account';
        $accountArr['parent'] = $taxEntityId;
        $accountArrProp['accountType'] = self::SYSTEM_ACCOUNT;
        $accountArrProp['name'] = $locationName;
        $accountArrProp['subType'] = self::SUBTYPE_REVENUE;
        $accountArrProp['divisionId'] = $locationId;
        $accountArr['properties'] = $accountArrProp;
        //set all account properties in an array        
        $serviceQue->executeQue("ws_oml_create", $accountArr);
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
}
