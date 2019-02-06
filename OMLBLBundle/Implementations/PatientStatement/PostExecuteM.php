<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientStatement;

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
     * @description function will perform some actions after execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
        $patientStatementId = $data->getData('conditions')['object'][0]; 

         //search data from "patient_statement" table
        $searchKey = [];
        $searchKey[0]['objectId'] = $patientStatementId;
        $searchKey[0]['outKey'] = 'response';
        $searchKey[0]['requiredAdditionalInfo'] = false;
        
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        
        // Set required variables
        $patientId = $objectData['patientId'];
        $createdOnDate = $objectData['createdOnDate'];
        $createdOnTime = $objectData['createdOnTime'];
        $statementCdnUrl = $objectData['statementCdnUrl'];
        $taxEntityId = $objectData['taxEntityId'];
        $amount = $objectData['amount'];

        // Fetch "account ID" to update
        $accountSearchKey = [];
        $accountSearchKey[0]['type'] = 'account';
        $accountSearchKey[0]['conditions'][] = ['patientId' => $patientId];
        $accountSearchKey[0]['conditions'][] = ['organizationTaxEntityId'=> $taxEntityId ];
        $accountSearchKey[0]['outKey'] = 'response';

        $resp = $serviceQue->executeQue('ws_oml_read', $accountSearchKey);
        $accountResponse = $resp['data']['response'];
        $accountId = $accountResponse[0]['id'];
   
        // Update data in "account" table
        $updateObjs['conditions']['object'][0] = $accountId;
        $updateObjs['properties']['lastStatementDate'] = $createdOnDate;
        $updateObjs['properties']['lastStatementTime'] = $createdOnTime;
        $updateObjs['properties']['lastStatementBalance'] = $amount;
       
        $updateResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
        return true;
    }

}
