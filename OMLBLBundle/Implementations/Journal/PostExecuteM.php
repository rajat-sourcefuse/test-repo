<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Journal;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of journal.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{
    const ACCOUNT_TYPE_PATIENT = 'metaAccountType:patient';
    
    /**
     * Function will perform the post execute opertaion of journal
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        //update credit and debit amount fo balance using journal data
        $this->updateAccountsForJournal($data, $serviceQue);
        
        //update isStatementGenerated flag to false to generate the statement
        $this->updateStatementFlag($data, $serviceQue);
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
     * Function will update the accounts related to journal entry
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function updateAccountsForJournal($data, ServiceQueI $serviceQue){
        $creditAccountId = $data->getData('properties')['creditAccountId'];
        $debitAccountId = $data->getData('properties')['debitAccountId'];
        $amount = $data->getData('properties')['entryAmount'];

        //update credit account data
        $this->creditAccountData($creditAccountId, $amount, $serviceQue);
        //update debit accout data
        $this->debitAccountData($debitAccountId, $amount, $serviceQue);        
        return;
    }
    
    /**
     * Function will update the statementGenerated flag to false
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function updateStatementFlag($data, ServiceQueI $serviceQue){
        $creditAccountId = $data->getData('properties')['creditAccountId'];
        $debitAccountId = $data->getData('properties')['debitAccountId'];

        //fetch credit account data
        $creditAccountData = $this->fetchObjectData($creditAccountId, $serviceQue);
        $creditAccountType = $creditAccountData['accountType'];
        if($creditAccountType==self::ACCOUNT_TYPE_PATIENT){
            $updateArr = array();
            $updateArr['isStatementGenerated'] = false;
            $this->updateObjectData($creditAccountId,$updateArr,$serviceQue);
        }
        
        //fetch debit accout data
        $debitAccountData = $this->fetchObjectData($debitAccountId, $serviceQue);
        $deditAccountType = $debitAccountData['accountType'];
        if($deditAccountType==self::ACCOUNT_TYPE_PATIENT){
            $updateArr = array();
            $updateArr['isStatementGenerated'] = false;
            $this->updateObjectData($debitAccountId,$updateArr,$serviceQue);
        }
        return;
    }
    
    /**
     * Function will credit the amount in existig amount for given accountId
     * @param type $accountId
     * @param type $amount
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function creditAccountData($accountId,$amount,ServiceQueI $serviceQue){
        
        $accountData = $this->fetchObjectData($accountId, $serviceQue);
        $prevAmount = isset($accountData['accountBalance'])?$accountData['accountBalance']:0;
        $accountBalance = $prevAmount + $amount;
        
        $updateArr = array();
        $updateArr['accountBalance'] = $accountBalance;
        $this->updateObjectData($accountId,$updateArr,$serviceQue);
        return;
    }
    
    /**
     * Function will debit the amount from existig amount for given accountId
     * @param type $accountId
     * @param type $amount
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function debitAccountData($accountId,$amount,ServiceQueI $serviceQue){
        
        $accountData = $this->fetchObjectData($accountId, $serviceQue);
        $prevAmount = isset($accountData['accountBalance'])?$accountData['accountBalance']:0;
        $accountBalance = $prevAmount - $amount;
        
        $updateArr = array();
        $updateArr['accountBalance'] = $accountBalance;
        $this->updateObjectData($accountId,$updateArr,$serviceQue);
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
