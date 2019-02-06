<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Journal;

/**
 * BL class for preExecute of Journal.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;

class PreExecuteM implements PreExecuteI {

    const OPEN_ACCOUNTING_PERIOD_STATUS = 'metaAccountingPeriodStatus:open';

    /**
     * This function validates data before execute create
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        //check and set accountingPeriodId for manual entries only
        /* here we are assuming that UI will never send accountingPeriodId adn accountingPeriodId will be calculated based on effectiveDate and if accountingPeriosId is coming in request, so we will consider it as system request and will not check for valid accountingPeriodId */

        $receivedData = $data->getData('properties');

        /* AP-1543 credit and debit account should not same */

        if (isset($receivedData['creditAccountId']) && isset($receivedData['debitAccountId'])) {
            if ($receivedData['creditAccountId'] == $receivedData['debitAccountId']) {
                throw new SynapExceptions(SynapExceptionConstants::CREDIT_AND_DEBIT_ACCOUNT_CAN_NOT_SAME, 400);
            }
        }


        if (!(isset($receivedData['accountingPeriodId']) && !empty($receivedData['accountingPeriodId']))) {
            //check for valid accounting period and set it in data
            $this->checkAndSetAccountingPeriod($data, $serviceQue);
        }

        /*
         * if journal entry is coming from payment object, we will check for encounterId and claim id from payment object and set it into journal object
         */
        $this->setFieldsFromPayment($data, $serviceQue);
        /*
         * if journal entry contains chargeId but not paymentId, we will check for encounterId and claim id from charge object and set it into journal object
         */
        $this->setFieldsFromCharge($data, $serviceQue);
        $this->updateAccountTxnDate($data->getData('properties'), $serviceQue);
        return true;
    }

    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * This function will mark user inactive in OpenAM when deleted from Synap
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * This function validates data before execute update
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return void
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * This function will check for any open accounting period for given tax entity and cover effective date
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return  void
     * @throws SynapExceptions
     */
    private function checkAndSetAccountingPeriod($data, ServiceQueI $serviceQue) {

        $orgTaxEntityId = $data->getData('parent');
        $effectiveDate = $data->getData('properties')['effectiveDate'];

        $searchKey = [];
        $searchKey[0]['type'] = 'organizationTaxEntityAccountPeriod';
        $searchKey[0]['conditions'][] = ['startDate' => ["LE" => $effectiveDate]];
        $searchKey[0]['conditions'][] = ['endDate' => ["GE" => $effectiveDate]];
        $searchKey[0]['conditions'][] = ['organizationTaxEntityId' => $orgTaxEntityId];
        $searchKey[0]['conditions'][] = ['status' => self::OPEN_ACCOUNTING_PERIOD_STATUS];
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $respData = $resp['data']['response'];

        if(empty($respData) || empty($respData[0])){
            throw new SynapExceptions(SynapExceptionConstants::ACCOUNTING_PERIOD_OPEN_NOT_EXISTS,400);
        }

        $data->setData(array('properties' => array('accountingPeriodId' => $respData[0]['id'])));
        return;
    }

    /**
     * This function will update account`s transaction date based on latest effective date
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return  void
     */
    private function updateAccountTxnDate($properties, ServiceQueI $serviceQue) {
        $creditAccountId = $properties['creditAccountId'];
        $debitAccountId = $properties['debitAccountId'];
        $effectiveDate = $properties['effectiveDate'];
        $searchKey = [];
        $searchKey[0]['type'] = 'account';
        $searchKey[0]['conditions'][] = ['id' => ["IN" => array($creditAccountId, $debitAccountId)]];
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $respData = $resp['data']['response'];
        if (!empty($respData)) {
            foreach ($respData as $accountData) {
                if (!isset($accountData['lastTransactionDate']) || $this->isEffectiveDateGreater($accountData, $effectiveDate)) {
                    $updateArr = [];
                    $updateArr['lastTransactionDate'] = $effectiveDate;
                    $this->updateobject($serviceQue, $accountData['id'], $updateArr);
                }
            }
        }
        return;
    }

    /**
     * Function will update object properties
     * 
     * @param type $statementId, $objectId, $arrProps
     * @return
     */
    private function updateobject($serviceQue, $objectId, $arrProps) {
        $updateObjs['conditions']['object'][0] = $objectId;
        $updateObjs['properties'] = $arrProps;
        $serviceQue->executeQue("ws_oml_update", $updateObjs);
        return;
    }

    /**
     * Function check does journal effective date is grater from last account`s txn date
     * 
     * @param type $accountData, $effectiveDate
     * @return boolean
     */
    private function isEffectiveDateGreater($accountData, $effectiveDate) {
        $effectiveDateGreater = FALSE;
        if (isset($accountData['lastTransactionDate']) && !empty($accountData['lastTransactionDate']) && !empty($effectiveDate)) {
            $dateTimeUtility = new DateTimeUtility();
            $dateDifference = $dateTimeUtility->getDateDiffObject($accountData['lastTransactionDate'], $effectiveDate);
            if ($dateDifference->format("%R%a") > 0) {
                $effectiveDateGreater = TRUE;
            }
        }
        return $effectiveDateGreater;
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
     * Function will set encounterId and claimId if exists from payment object
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function setFieldsFromPayment($data, ServiceQueI $serviceQue) {
        if ((isset($data->getData('properties')['paymentId']) && !empty($data->getData('properties')['paymentId']))) {
            //fetch payment data
            $paymentData = $this->fetchObjectData($data->getData('properties')['paymentId'], $serviceQue);
            if (isset($paymentData['encounterId'])) {
                $data->setData(array('properties' => array('encounterId' => $paymentData['encounterId'])));
            }
            if (isset($paymentData['claimId'])) {
                $data->setData(array('properties' => array('claimId' => $paymentData['claimId'])));
            }
        }
        return;
    }

    /**
     * Function will set encounterId and claimId if exists from patientCharge object
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function setFieldsFromCharge($data, ServiceQueI $serviceQue) {
        if ((isset($data->getData('properties')['chargeId']) && !empty($data->getData('properties')['chargeId'])) &&
                !(isset($data->getData('properties')['paymentId']) && !empty($data->getData('properties')['paymentId']))) {
            //fetch patientCharge data
            $chargeData = $this->fetchObjectData($data->getData('properties')['chargeId'], $serviceQue);
            if (isset($chargeData['encounterId'])) {
                $data->setData(array('properties' => array('encounterId' => $chargeData['encounterId'])));
            }
            $data->setData(array('properties' => array('claimId' => $chargeData['patientClaimId'], 'payerId' => isset($chargeData['patientClaimIdCurrentPayer']) ? $chargeData['patientClaimIdCurrentPayer'] : '')));
        }
        return;
    }

}
