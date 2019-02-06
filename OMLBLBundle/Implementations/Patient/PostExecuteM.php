<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Patient;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\Utilities\SessionUtility;
use SynapEssentials\AccessControlBundle\Interfaces\SessionConstants;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use Externals\DrFirstBundle\Managers\UploadDataManager;
use SynapEssentials\OMLBLBundle\Implementations\Utility\Utility;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI {

    /**
     * @description function will perform some actions after execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {

        $configurator = Configurator::getInstance();
        $patientId = $data->getData('id');

        $utilityInstance = new Utility();

        // If pateint is eligible to be created on dr first
        if ($utilityInstance->checkPatientEligibiltyForDrFirst($patientId)) {
            $uploadDataToDrFirst = new UploadDataManager($configurator->getServiceContainer());
            $uploadDataToDrFirst->submitPatientDemographic($patientId);
        }
        return true;
    }

    /**
     * @description function will perform some actions after execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will perform some actions after execute get
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteGet($data, ServiceQueI $serviceQue) {
       
        
         
        return true;
    }

    /**
     * Function will perform some actions after execute view
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * @author Sourav Bhargava
     * @description this function updates the firstName & lastName in accounts 
     * Object if any change to the same has been made
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    private function updateinfoAccounts($data, ServiceQueI $serviceQue) {
        $dataResp = $data->getAll();
        $patientId = $dataResp['conditions']['object'][0];

        $accountId = array();
        $update = array();
        if (isset($dataResp['properties']['firstName']) ||
                isset($dataResp['properties']['lastName'])) {
            //step1: get all the records from Account table where this patientId has been used
            $searchObj = array();
            $searchObjProp['type'] = 'account';
            $searchObjProp['outKey'] = 'PA';

            $searchObjProp['conditions'][]['patientId'] = $patientId;
            $searchObj[0] = $searchObjProp;
           
            
            $resp = $serviceQue->executeQue("ws_oml_read", $searchObj);
            $accountData = $resp['data']['PA'];
            //step2 loop through them and collect the idz in array
            if (!empty($accountData) || $accountData != null) {

                foreach ($accountData as $ad) {
                    $accountId[] = $ad['id'];
                }
            }
            //step3 use update statement to update the changed value
            if (!empty($accountId)) {
                if (isset($dataResp['properties']['firstName'])) {
                    $update['properties']['firstName'] = $dataResp['properties']['firstName'];
                }
                if (isset($dataResp['properties']['lastName'])) {
                    $update['properties']['lastName'] = $dataResp['properties']['lastName'];
                }
                $update['conditions']['object'] = $accountId;
                $serviceQue->executeQue("ws_oml_update", $update);
            }
        }
        return true;
        // data=[{ "type":"account", "outKey": "PA", "conditions": [             {"patientId":"patient:hc7tryu" } ]  }]
    }

    /**
     * @description function will perform some actions after execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {

        $dataResp = $data->getAll();
        $patientId = $dataResp['conditions']['object'][0];
        $utilityInstance = new Utility();
        $this->updateinfoAccounts($data, $serviceQue);
        // If pateint is eligible to be created on dr first
        if ($utilityInstance->checkPatientEligibiltyForDrFirst($patientId)) {
            if (!(isset($dataResp['properties']['drFirstLastSyncTime'])) && !(isset($dataResp['properties']['drFirstLastSyncDate']))) {
                $configurator = Configurator::getInstance();
                $uploadDataToDrFirst = new UploadDataManager($configurator->getServiceContainer());
                $uploadDataToDrFirst->submitPatientDemographic($dataResp['conditions']['object'][0]);
            }
        }
        return true;
    }

}
