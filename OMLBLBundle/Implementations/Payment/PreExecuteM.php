<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Payment;

/**
 * BL class for preExecute of Payment.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\BillingBundle\Implementations\CommonFunction\CommonClass;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\Utilities\SessionUtility;

class PreExecuteM implements PreExecuteI {

    const PAYMENT_POSTED_STATUS = 'metaPaymentStatus:posted';
    const PAYMENT_PENDING_STATUS = 'metaPaymentStatus:pending';
    //constants for denied actions
    const IGNORE_POST_PAYMENT = 'metaPaymentDeniedAction:ignorePostPayment';
    const POST_APPEAL = 'metaPaymentDeniedAction:postAndAppeal';
    const REJECT_RESUBMIT = 'metaPaymentDeniedAction:rejectAndResubmit';

    /**
     * This function validates data before execute create
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        if (isset($data->getData('properties')['remittanceId'])) {
            //check if all payments sum linked to remittance against remittance sum
            $paymentDetails = $data->getData('properties');
            $this->chkPaymentsSum($paymentDetails, $serviceQue);
        }

        $status = isset($data->getData('properties')['status']) ? $data->getData('properties')['status'] : '';
        if ($status == self::PAYMENT_POSTED_STATUS) {
            $data->setData(array('properties' => array('isApproved' => TRUE)));
        }

        /*
         * Check if accountingPeriodId is not coming in request and remittance id is coming
         * get remittanceDate from remittance and get accounting period based on that
         */
        if (isset($data->getData('properties')['remittanceId'])) {
            $this->claimErrorHandling($data);
            $remittanceData = $this->fetchObjectData($data->getData('properties')['remittanceId'], $serviceQue);
            if (isset($remittanceData['payerId']) && !empty($remittanceData['payerId'])) {
                $data->setData(array('properties' => array('payerId' => $remittanceData['payerId'])));
            } else {
                //throw new SynapExceptions(SynapExceptionConstants::REMITTACNE_PAYER_REQUIRED, 400);
            }
            if (!isset($data->getData('properties')['accountingPeriodId'])) {
                $taxEntityId = $data->getData('parent');
                $remittanceDate = $remittanceData['remittanceDate'];
                $commonObj = new CommonClass();
                $accountPeriodData = $commonObj->getTaxEntityAccountPeriod($taxEntityId, $remittanceDate, $serviceQue);
                $data->setData(array('properties' => array('effectiveDate' => $accountPeriodData['effectiveDate'], 'accountingPeriodId' => $accountPeriodData['accountingPeriodId'])));
            }
        }

        /*
         * if payment entry contains chargeId but not claimId, we will set encounterId and claim id into payment object
         */
        $this->setFieldsFromCharge($data, $serviceQue);
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
        $paymentData = $this->fetchObjectData($data->getData('conditions')['object'][0], $serviceQue);
        $voidedPayment = FALSE;
        $paymentVoided = isset($data->getData('properties')['isVoided']) ? $data->getData('properties')['isVoided'] : '';
        $remittanceId = isset($data->getData('properties')['remittanceId']) ? $data->getData('properties')['remittanceId'] : (isset($paymentData['remittanceId']) ? $paymentData['remittanceId'] : '');
        /* Restrict user to false isVoided when already true and reverse journal entry. */
        if (empty($remittanceId) && isset($paymentData['isVoided'])) {
            if ($paymentData['isVoided'] && $paymentVoided === FALSE) {
                throw new SynapExceptions(SynapExceptionConstants::PAYMENT_ALREADY_VOIDED, 400);
            }
            if (!$paymentData['isVoided'] && $paymentVoided === TRUE) {
                $this->makeReverseJournalEntry($data, $serviceQue);
                $voidedPayment = TRUE;
            }
        }
        if (!$voidedPayment) {
            if ($paymentData['status'] == self::PAYMENT_POSTED_STATUS && $paymentData['isApproved'] == TRUE) {
                throw new SynapExceptions(SynapExceptionConstants::PAYMENT_ADJUSTMENT_ALREADY_POSTED, 403, array('objectId' => 'Payment'));
            }
            if (isset($paymentData['remittanceId'])) {
                $remittanceData = $this->fetchObjectData($paymentData['remittanceId'], $serviceQue);
                if (isset($remittanceData['payerId']) && !empty($remittanceData['payerId'])) {
                    $data->setData(array('properties' => array('payerId' => $remittanceData['payerId'])));
                } else {
                    throw new SynapExceptions(SynapExceptionConstants::REMITTACNE_PAYER_REQUIRED, 400);
                }
                if (isset($data->getData('properties')['amount'])) {
                    //check if all payments sum linked to remittance against remittance sum
                    $paymentDetails = $paymentData;
                    $paymentDetails['amount'] = $data->getData('properties')['amount'];
                    $this->chkPaymentsSum($paymentDetails, $serviceQue);
                }
            }

            $status = isset($data->getData('properties')['status']) ? $data->getData('properties')['status'] : '';
            if ($status == self::PAYMENT_POSTED_STATUS) {
                $data->setData(array('properties' => array('isApproved' => TRUE)));
            }
        }
        return true;
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
     * Function will set encouterId and claimId if exists from patientCharge object
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function setFieldsFromCharge($data, ServiceQueI $serviceQue) {
        if ((isset($data->getData('properties')['chargeId']) && !empty($data->getData('properties')['chargeId'])) &&
                !(isset($data->getData('properties')['claimId']) && !empty($data->getData('properties')['claimId']))) {
            //fetch patientCharge data
            $chargeData = $this->fetchObjectData($data->getData('properties')['chargeId'], $serviceQue);
            if (isset($chargeData['encounterId'])) {
                $data->setData(array('properties' => array('encounterId' => $chargeData['encounterId'])));
            }
            $data->setData(array('properties' => array('claimId' => $chargeData['patientClaimId'])));
        }
        return;
    }

    /**
     * Function will check payments sum against remittance amount and throw exception, if it exceeds remittnace sum
     * 
     * @param type $data (paymentData)
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function chkPaymentsSum($data, ServiceQueI $serviceQue) {
        //fetch all payments linked to given remittance
        $searchKey = [];
        $searchKey[0]['type'] = 'payment';
        $searchKey[0]['conditions'][] = array(
            'remittanceId' => $data['remittanceId']
        );
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $paymentData = $resp['data']['response'];

        //get posted payments sum and pending payments count
        $paymentSum = 0;
        if (!empty($paymentData)) {
            foreach ($paymentData as $payments) {
                $paymentSum = $paymentSum + $payments['amount'];
            }
        }

        //add current payment amount to total sum
        $paymentSum = $paymentSum + $data['amount'];
        //get remittance data
        $remittanceData = $this->fetchObjectData($data['remittanceId'], $serviceQue);

        //if remittance amount sum is less than linked payments sum, throw exception
        if ($paymentSum > $remittanceData['remittanceAmount']) {
            throw new SynapExceptions(SynapExceptionConstants::PAYMENT_SUM_EXCEED_REMITTANCE, 400);
        }
        return;
    }

    /**
     * Function will validate properties required for 835 handling
     * @param type $data
     * @return
     */
    private function claimErrorHandling($data) {
        if (!isset($data->getData('properties')['patientId']) || empty($data->getData('properties')['patientId'])) {
            throw new SynapExceptions(SynapExceptionConstants::MANDATORY_PROPERTY_NOT_PASSED, 400, array('property' => 'patientId'));
        } else if ((!isset($data->getData('properties')['encounterId']) || empty($data->getData('properties')['encounterId'])) || (!isset($data->getData('properties')['claimId']) || empty($data->getData('properties')['claimId']))) {
            throw new SynapExceptions(SynapExceptionConstants::MANDATORY_PROPERTY_NOT_PASSED, 400, array('property' => 'encounterId'));
        } else if (!isset($data->getData('properties')['chargeId']) || empty($data->getData('properties')['chargeId'])) {
            throw new SynapExceptions(SynapExceptionConstants::MANDATORY_PROPERTY_NOT_PASSED, 400, array('property' => 'chargeId'));
        }
    }

    /**
     * Function will create a reverse journal entry for the given payment object
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function makeReverseJournalEntry($data, ServiceQueI $serviceQue) {
        $paymentId = isset($data->getData('conditions')['object'][0]) ? $data->getData('conditions')['object'][0] : $data->getData('id');
        $searchKey = [];
        $searchKey[0]['type'] = 'journal';
        $searchKey[0]['conditions'][] = array(
            'paymentId' => $paymentId
        );
        $searchKey[0]['requiredAdditionalInfo'] = FALSE;
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $journalData = $resp['data']['response'];
        if (!empty($journalData)) {
            $sessionUtil = SessionUtility::getInstance();
            $getAddionalInfo = $sessionUtil->getAddionalInfo();
            foreach ($journalData as $jrnalData) {
                $taxEntityId = $jrnalData['organizationTaxEntityId'];
                $remove = ['checksum', 'id', 'createdBy', 'createdOnDate', 'createdOnTime', 'objectPath', 'organizationId', 'organizationTaxEntityId', 'isDeleted'];
                $journalArrProp = array_diff_key($jrnalData, array_flip($remove));
                list($journalArrProp['creditAccountId'], $journalArrProp['debitAccountId']) = array($journalArrProp['debitAccountId'], $journalArrProp['creditAccountId']);
                $journalArrProp['note'] = 'Payment voided by user ' . $getAddionalInfo['lastName'] . ' ' . $getAddionalInfo['firstName'];
                $journalArr = array();
                $journalArr['parent'] = $taxEntityId;
                $journalArr['objectType'] = 'journal';
                $journalArr['properties'] = $journalArrProp;
                $serviceQue->executeQue("ws_oml_create", $journalArr);
            }
        }
        return;
    }

}
