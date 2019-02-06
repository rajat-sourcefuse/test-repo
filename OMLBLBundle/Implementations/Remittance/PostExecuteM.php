<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Remittance;
use SynapEssentials\BillingBundle\Implementations\CommonFunction\CommonClass;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\Utilities\statementUtility;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\Constants\InternalConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of remittance.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;


class PostExecuteM implements PostExecuteI
{
    
    //system account type constants
    const SYSTEM_ACCOUNT = 'metaAccountType:system';
    const PATIENT_ACCOUNT = 'metaAccountType:patient';
    
    //system account subtype constants  
    const SUBTYPE_ASSET = 'metaAccountSubType:asset';
    const SUBTYPE_PAYMENT = 'metaAccountSubType:payment';
    
    //system account subtype constants
    const SUBTYPE_REMITTANCE = 'metaAccountSubType:remittance';
    const SUBTYPE_WRITEOFF = 'metaAccountSubType:writeoff';
    
    const REMITTANCE_COMPLETE_STATUS = 'metaRemittanceStatus:complete';
    
    //constants for patient claim status
    const READY_TO_INVOICE = 'metaClaimStatus:readyToInvoice';    
    const COMPLETED_CLAIM = 'metaClaimStatus:completed';
    const CLAIM_STATUS_SUSPENSE_PAYER = 'metaClaimStatus:suspensePayer';
    const CLAIM_STATUS_REJECTED = 'metaClaimStatus:rejected';
    const ADD_REMITTANCE = 'add';
    const UPDATE_REMITTANCE = 'update';
    /**
     * Function will execute after the payment
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        //make journal entry for remittance        
        $this->makeJournalEntry(self::ADD_REMITTANCE, $data, $serviceQue);        
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
        //check remittance status and make journal entries for plbAdjustments
        $this->checkRemittanceAndMakeJournal($data, $serviceQue);
        return true;
    }
    
    /**
     * Function will create a journal entry for the given payment
     * @param type $remittanceAction, $data
     * @param ServiceQueI $serviceQue
     * @param array $remittanceData
     * @return
     */
    private function makeJournalEntry($remittanceAction, $data, ServiceQueI $serviceQue, $remittanceData=array()){
        
        //check for payer id and make journal entry
        $payerId = isset($data->getData('properties')['payerId'])?$data->getData('properties')['payerId']:'';
        if($payerId==''){
            return;
        }

        if($remittanceAction==self::ADD_REMITTANCE){
            $taxEntityId = $data->getData('parent');
            $remittanceId = $data->getData('id');
            $remittanceAmount = $data->getData('properties')['remittanceAmount'];
            $remittanceDbDate = $data->getData('properties')['remittanceDate'];
        } else if($remittanceAction==self::UPDATE_REMITTANCE){
            $taxEntityId = $remittanceData['organizationTaxEntityId'];
            $remittanceId = $data->getData('conditions')['object'][0];
            $remittanceAmount = $remittanceData['remittanceAmount'];
            $remittanceDbDate = $remittanceData['remittanceDate'];
        }

        $dateTimeUtility = new DateTimeUtility();
        $remittanceDate = $dateTimeUtility->convertFormat($remittanceDbDate, $dateTimeUtility::DB_DATE_FORMAT, $dateTimeUtility::DATE_FORMAT);
        //check if patient account exists for the given info
        $taxEntityAccountId = $this->getTaxEntityAssetAccount($taxEntityId, $serviceQue);
        $payerAccounts = $this->getPayerAccounts($taxEntityId, $payerId, $serviceQue);
        
        if(empty($payerAccounts)){
            throw new SynapExceptions(SynapExceptionConstants::PAYER_CONFIG_MISSING_FOR_TAX_ENTITY,400);
        }
        
        $payerRemittanceAccount = $payerAccounts[self::SUBTYPE_REMITTANCE];
        
        $commonObj = new CommonClass();
        $accountPeriodData = $commonObj->getTaxEntityAccountPeriod($taxEntityId, $remittanceDate, $serviceQue);
        
        $journalArrProp = [];
        $journalArrProp['entryAmount'] = $remittanceAmount;
        $journalArrProp['effectiveDate'] = $accountPeriodData['effectiveDate'];
        $journalArrProp['accountingPeriodId'] = $accountPeriodData['accountingPeriodId'];
        $journalArrProp['payerId'] = $payerId;
        $journalArrProp['creditAccountId'] = $payerRemittanceAccount;
        $journalArrProp['debitAccountId'] = $taxEntityAccountId;
        $journalArrProp['remittanceId'] = $remittanceId;
        $journalArrProp['note'] = $remittanceId;
        
        $this->saveJournalEntry($taxEntityId,$journalArrProp,$serviceQue);        
        return;
    }
    
    /**
     * Function will get convert remittance date format(yyyymmdd) to api format (mm/dd/yyyy)
     * @param $remittanceDate
     * @return $formattedDate
     */
    private function convertRemittanceDateFormatToAPIFormat($remittanceDate){
        
        $year = substr($remittanceDate, 0, 4);
        $month = substr($remittanceDate, 4, 2);
        $date = substr($remittanceDate, 6, 2);
        
        $formattedDate = $month.'/'.$date.'/'.$year;
        return $formattedDate;
    }
    
    
    
    /**
     * Function will search for a patient account based on taxEntity and patient
     * @param type $taxEntityId
     * @param ServiceQueI $serviceQue
     * @return $accountId
     */
    private function getTaxEntityAssetAccount($taxEntityId, ServiceQueI $serviceQue){
        
        //create serach key to find account with given info
        $searchKey = [];
        $searchKey[0]['type'] = 'account';
        $searchKey[0]['conditions'][] = array(
            'organizationTaxEntityId' => $taxEntityId
        );
        $searchKey[0]['conditions'][] = array(
            'subType' => self::SUBTYPE_ASSET
        );
        $searchKey[0]['conditions'][] = array(
            'accountType' => self::SYSTEM_ACCOUNT
        );
        $searchKey[0]['outKey'] = 'response';
        $accountResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $accountData = $accountResp['data']['response'];
        //check if record exist or not
        $accountId = !empty($accountData[0]['id'])?$accountData[0]['id']:'';
        return $accountId;
    }
    
    /**
     * Function will search for a patient account based on patientId and taxEntityId
     * @param type $patientId
     * @param type $taxEntityId
     * @param ServiceQueI $serviceQue
     * @return $accountId
     */
    private function getPatientAccount($patientId,$taxEntityId, ServiceQueI $serviceQue){
        
        //create serach key to find account with given info
        $searchKey = [];
        $searchKey[0]['type'] = 'account';
        $searchKey[0]['conditions'][] = array(
            'organizationTaxEntityId' => $taxEntityId
        );
        $searchKey[0]['conditions'][] = array(
            'patientId' => $patientId
        );
        $searchKey[0]['conditions'][] = array(
            'subType' => self::SUBTYPE_PAYMENT
        );
        $searchKey[0]['conditions'][] = array(
            'accountType' => self::PATIENT_ACCOUNT
        );
        $searchKey[0]['outKey'] = 'response';
        $accountResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $accountData = $accountResp['data']['response'];
        //check if record exist or not
        $accountId = !empty($accountData[0]['id'])?$accountData[0]['id']:'';
        return $accountId;
    }
    
    /**
     * Function will get payer accounts based on tax entity and payer id
     * @param type $taxEntityId
     * @param type $payerId
     * @param ServiceQueI $serviceQue
     * @return $payerAccounts
     */
    private function getPayerAccounts($taxEntityId, $payerId, ServiceQueI $serviceQue){
        $payerAccounts = [];
        $searchKey = [];
        $searchKey[0]['type'] = 'account';
        $searchKey[0]['conditions'][] = array(
            'payerId' => $payerId
        );
        $searchKey[0]['conditions'][] = array(
            'organizationTaxEntityId' => $taxEntityId
        );
        $searchKey[0]['outKey'] = 'response';
        $accountResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $accountData = $accountResp['data']['response'];
        
        foreach($accountData as $accounts){
            $payerAccounts[$accounts['subType']] = $accounts['id'];
        }
        return $payerAccounts;
    }
    
    /**
     * Function will save the journal entry based on parameters passed
     * @param type $taxEntityId
     * @param $jounalArrProperties
     * @param ServiceQueI $serviceQue
     * @return $journalId
     */
    private function saveJournalEntry($taxEntityId,$jounalArrProperties,ServiceQueI $serviceQue){
        //create an array to make journal entry
        $journalArr = $journalAdjArr = array();
        $journalArr['parent'] = $taxEntityId;
        $journalArr['objectType'] = 'journal';
        $journalArr['properties'] = $jounalArrProperties;
        //set all journal properties in an array        
        $resp = $serviceQue->executeQue("ws_oml_create", $journalArr);        
        $journalId = $resp['data']['id'];
        return $journalId;
    }
    
    /**
     * Function will check for remittance status as complete and then make journal entries for plbAjustments
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function checkRemittanceAndMakeJournal($data, ServiceQueI $serviceQue){
        
        $remittanceId = $data->getData('conditions')['object'][0];
        $remittanceData = $this->fetchObjectData($remittanceId, $serviceQue);

        $remittanceStatus = isset($data->getData('properties')['status'])?$data->getData('properties')['status']:'';
        
        if(!empty($remittanceStatus) && $remittanceStatus==self::REMITTANCE_COMPLETE_STATUS){
            
            
            //fetch all plb adjustments for this remittance
            $plbAdjustments = $this->fetchPlbAdjustments($remittanceId, $serviceQue);
            
            if(!empty($plbAdjustments)){
                
                $commonObj = new CommonClass();
                
                $receivedDate = isset($remittanceData['receivedDateTime'])?$this->convertRemittanceDateFormatToAPIFormat($remittanceData['receivedDateTime']):$commonObj->getCurrentDate();
                //get tax entity and payer details
                $taxEntityId = $remittanceData['organizationTaxEntityId'];
                $payerId = $remittanceData['payerId'];                
                $payerAccounts = $this->getPayerAccounts($taxEntityId, $payerId, $serviceQue);
                $payerWriteOffAccount = $payerAccounts[self::SUBTYPE_WRITEOFF];
                
                $accountPeriodData = $commonObj->getTaxEntityAccountPeriod($taxEntityId,$receivedDate, $serviceQue);
                
                //prepare journal array properties
                $journalArrProp = [];                                
                $journalArrProp['payerId'] = $payerId;
                $journalArrProp['effectiveDate'] = $accountPeriodData['effectiveDate'];
                $journalArrProp['accountingPeriodId'] = $accountPeriodData['accountingPeriodId'];
                $journalArrProp['note'] = $remittanceId;
                
                //loop through all the adjustments and make journal entries
                foreach($plbAdjustments as $adjustment){
                    $adjustmentAmount = $adjustment['adjustmentAmount'];
                    $journalArrProp['entryAmount'] = $adjustmentAmount;
                    $journalArrProp['divisionId'] = $adjustment['divisionId'];
                    //get division account and set that as credit or debit
                    $divisionAccount = $this->getDivisionAccount($adjustment['divisionId'], $serviceQue);
                    if($adjustmentAmount<0){
                        $journalArrProp['creditAccountId'] = $payerWriteOffAccount;
                        $journalArrProp['debitAccountId'] = $divisionAccount;                        
                    }else{
                        $journalArrProp['creditAccountId'] = $divisionAccount;
                        $journalArrProp['debitAccountId'] = $payerWriteOffAccount;                        
                    }
                    $this->saveJournalEntry($taxEntityId,$journalArrProp,$serviceQue); 
                }
            }
            
            //update claim data to move it from primary to secondary and so on
            $this->updateClaimForPayers($remittanceId, $serviceQue);
        }

        //make journal entry for remittnace if payer id is passed in update request
        $this->makeJournalEntry(self::UPDATE_REMITTANCE, $data, $serviceQue, $remittanceData);
        return;
    }
    
    /**
     * Function update the claim to be batched for secondary or tertiary
     * @param type $remittanceId
     * @param type $serviceQue
     * @return
     */
    private function updateClaimForPayers($remittanceId, ServiceQueI $serviceQue){
        
        $claimIds = $this->fetchClaimIdsFromPayment($remittanceId, $serviceQue);
        
        if(!empty($claimIds)){
            foreach($claimIds as $claimId){
                $claimData = $this->fetchObjectData($claimId, $serviceQue); 
                if(in_array($claimData['status'],array(self::CLAIM_STATUS_REJECTED, self::CLAIM_STATUS_SUSPENSE_PAYER))){
                    //do not to process claims info, if status is already set as Rejected or suspene payer
                    continue;
                }
                //$paymentAdjustmentAmount = $this->fetchPaymentAdjustmentAmount($claimId, $serviceQue);
                
                //fetch claim balance from journal based on encounterId
                $patientAccountId = $this->getPatientAccount($claimData['patientId'],$claimData['taxEntityId'], $serviceQue);
                $claimBalanceData = $this->fetchClaimBalance($claimData['patientEncounterId'], $patientAccountId, $serviceQue);
                $claimBalance = $claimBalanceData['claimBalance'];
                $lastPaymentDate = $claimBalanceData['lastPaymentDate'];
                
                //initialize array to update cliam properties
                $updateArr = array();
                if(
                    $claimBalance < 0
                        &&
                    $claimData['maxLevel']<$claimData['currentLevel']    
                ){                    
                    $updateArr['status'] = self::READY_TO_INVOICE;
                    $updateArr['currentLevel'] = $claimData['currentLevel']++;
                    $updateArr['batchId'] = '';
                    $updateArr['currentPayer'] = $this->fetchPayerName($claimData, $serviceQue);                    
                }else if(
                    $claimBalance < 0
                        &&
                    $claimData['maxLevel']==$claimData['currentLevel'])
                {
                    //mark claim status as patient due                    
                    $updateArr['status'] = self::COMPLETED_CLAIM;                    
                }else if($claimBalance >= 0){
                    //mark claim status as closed                    
                    $updateArr['status'] = self::COMPLETED_CLAIM;                    
                    $updateArr['dateClosed'] = $lastPaymentDate;                    
                }
                
                if(!empty($updateArr)){
                    $this->updateObjectData($claimId,$updateArr,$serviceQue);
                }
            }
        }
        return;        
    }
    
    /**
     * Function will fetch all payments and adjustment amount for a given claim
     * @param type $claimId
     * @param type $serviceQue
     * @return $amountTotal
     */
    private function fetchPaymentAdjustmentAmount($claimId, ServiceQueI $serviceQue){
        
        $amountTotal = 0;

        //create search key
        $searchKey = [];
        $searchKey[0]['type'] = 'payment';
        $searchKey[0]['conditions'][] = array('claimId'=>$claimId);
        $searchKey[0]['outKey'] = 'response';
        
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        
        foreach($objectData as $payment){            
            $adjustmentAmt = isset($payment['adjustmentAmount'])?$payment['adjustmentAmount']:0;
            $amountTotal = $amountTotal + $payment['amount'] + $adjustmentAmt;
        }
        
        return $amountTotal;
    }
    
    /**
     * Function will fetch claim balance and last payment date based on encounterId
     * @param type $encounterId
     * @param type $accountId
     * @param type $serviceQue
     * @return $claimBalanceAndLastPayment
     */
    private function fetchClaimBalance($encounterId, $accountId, ServiceQueI $serviceQue){
        
        $lastPaymentDate = '';
        $statementUtility = new statementUtility();
        $journalArr = $statementUtility->getJournalTrxn($serviceQue, null, $encounterId, $accountId);
        
        $claimBalance = $statementUtility->getJournalTrxnSum($journalArr,$accountId, $lastPaymentDate);
        $claimBalanceAndLastPayment = array('claimBalance'=>$claimBalance,'lastPaymentDate'=>$lastPaymentDate);
        return $claimBalanceAndLastPayment;
    }
    
    /**
     * Function update the claim to be batched for secondary or tertiary
     * @param type $remittanceId
     * @param type $serviceQue
     * @return $claimds
     */
    private function fetchClaimIdsFromPayment($remittanceId, ServiceQueI $serviceQue){
        
        $claimIds = [];

        //create search key
        $searchKey = [];
        $searchKey[0]['type'] = 'payment';
        $searchKey[0]['conditions'][] = array('remittanceId'=>$remittanceId);
        $searchKey[0]['outKey'] = 'response';
        
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        
        foreach($objectData as $payment){
            if(isset($payment['claimId']) && !in_array($payment['claimId'],$claimIds)){
                $claimIds[] = $payment['claimId'];
            }
        }
        
        return $claimIds;
    }
    
    /**
     * Function will fetch all plb adjustment records for given remittance id
     * @param type $remittanceId
     * @param type $serviceQue
     * @return $objectData
     */
    private function fetchPlbAdjustments($remittanceId, ServiceQueI $serviceQue){
        //create search key
        $searchKey = [];
        $searchKey[0]['type'] = 'plbAdjustment';
        $searchKey[0]['conditions'][] = array('remittanceId'=>$remittanceId);
        $searchKey[0]['outKey'] = 'response';
        
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        return $objectData;
    }
    
    /**
     * Function will fetch current payer name based on $claimData
     * @param type $claimData
     * @param type $serviceQue
     * @return $payerName
     */
    private function fetchPayerName($claimData, ServiceQueI $serviceQue){
        $payerName = '';
        
        switch ($claimData['currentLevel']){
            case 1:
                $insurance = $claimData['secondaryPayerProfileId'];
                break;
            case 2:
                $insurance = $claimData['tertiaryPayerProfileId'];
                break;
        }
        
        if(!empty($insurance)){
            $insuranceData = $this->fetchObjectData($insurance, $serviceQue);
            $payerName = $insuranceData['insuranceCompanyNameName'];
        }        
        return $payerName;
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
     * Function will get division and its account
     * @param type $division
     * @param ServiceQueI $serviceQue
     * @return $divisionAccountId
     */
    private function getDivisionAccount($division, ServiceQueI $serviceQue){
        $divisionAccountId = '';
        if(!empty($division)){
            $searchKey = [];
            $searchKey[0]['type'] = 'account';
            $searchKey[0]['conditions'][] = array(
                'divisionId' => $division
            );
            $searchKey[0]['outKey'] = 'response';
            $accoutResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $accountData = $accoutResp['data']['response'];
            $divisionAccountId = isset($accountData[0]['id'])?$accountData[0]['id']:'';
        }
        return $divisionAccountId;
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
        $serviceQue->executeQue("ws_oml_update", $updateObjs, InternalConstants::INTERNAL_CALLER);
        return;
    }
}
