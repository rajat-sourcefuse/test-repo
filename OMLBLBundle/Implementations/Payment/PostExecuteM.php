<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Payment;

use SynapEssentials\BillingBundle\Implementations\CommonFunction\CommonClass;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\Utilities\statementUtility;
/**
 * BL class for postExecute of payment.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\OMLBundle\Utilities\PostRemittanceUtility;

class PostExecuteM implements PostExecuteI {

    private $objPostRemittanceUtils;

    // payment status
    const PAYMENT_POSTED_STATUS = 'metaPaymentStatus:posted';

    public function __construct() {
        $this->objPostRemittanceUtils = new PostRemittanceUtility();
    }

    /**
     * Function will execute after the payment
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {
        // update journal for payments
        $status = isset($data->getData('properties')['status']) ? $data->getData('properties')['status'] : '';
        if ($status == self::PAYMENT_POSTED_STATUS) {
            $this->objPostRemittanceUtils->makeJournalEntry('payment', $data, $serviceQue);

            if (isset($data->getData('properties')['claimId']) && !empty($data->getData('properties')['claimId'])) {
                // function will check payment against claim and update status to closed, if payment sum linked to encounter is greater than or equal to patient claim balance
                $this->objPostRemittanceUtils->checkAndUpdateClaim($data->getData('properties'), $serviceQue);
            }
        }
        $remittanceId = isset($data->getData('properties')['remittanceId']) ? $data->getData('properties')['remittanceId'] : '';
        if (!empty($remittanceId)) {
            $this->objPostRemittanceUtils->checkAndUpdateRemittance($remittanceId, $serviceQue);
        }
        return true;
    }

    /**
     * Function will perform some actions after execute delete
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
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
     * Function will perform some actions after execute update
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
        //update journal for payments
        $paymentId = $data->getData('conditions')['object'][0];
        $paymentData = $this->fetchObjectData($paymentId, $serviceQue);
        $status = isset($data->getData('properties')['status']) ? $data->getData('properties')['status'] : '';
        $paymentVoided = isset($data->getData('properties')['isVoided']) ? $data->getData('properties')['isVoided'] : FALSE;
        if (!$paymentVoided && $status == self::PAYMENT_POSTED_STATUS) {
            $this->objPostRemittanceUtils->makeJournalEntry('payment', $data, $serviceQue);
            if (isset($paymentData['claimId']) && !empty($paymentData['claimId'])) {
                // function will check payment against claim and update status to closed, if payment sum linked to encounter is greater than or equal to patient claim balance
                $this->objPostRemittanceUtils->checkAndUpdateClaim($paymentData, $serviceQue);
            }
        }
        $remittanceId = isset($data->getData('properties')['remittanceId']) ? $data->getData('properties')['remittanceId'] : '';
        if (empty($remittanceId)) {
            $remittanceId = isset($paymentData['remittanceId']) ? $paymentData['remittanceId'] : '';
        }
        if (!empty($remittanceId)) {
            $this->objPostRemittanceUtils->checkAndUpdateRemittance($remittanceId, $serviceQue);
        }
        return true;
    }

    /**
     * Function will fetch the object data based on objectId
     * 
     * @param type $objectId
     * @param ServiceQueI $serviceQue
     * @return $objectData
     */
    private function fetchObjectData($objectId, ServiceQueI $serviceQue) {
        // fetch object date
        $searchKey = [];
        $searchKey[0]['objectId'] = $objectId;
        $searchKey[0]['outKey'] = 'response';

        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectData = $objectResp['data']['response'];
        return $objectData;
    }

}
