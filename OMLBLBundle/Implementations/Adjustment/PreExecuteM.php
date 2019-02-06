<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Adjustment;

/**
 * BL class for preExecute of Adjustment.
 *
 * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\BillingBundle\Implementations\CommonFunction\CommonClass;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\Utilities\SessionUtility;

class PreExecuteM implements PreExecuteI {

    const PAYMENT_POSTED_STATUS = 'metaPaymentStatus:posted';

    /**
     * This function validates data before execute create
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        $status = isset($data->getData('properties')['status']) ? $data->getData('properties')['status'] : '';
        if ($status == self::PAYMENT_POSTED_STATUS) {
            if (isset($data->getData('properties')['remittanceId'])) {
                $remittanceData = $this->fetchObjectData($data->getData('properties')['remittanceId'], $serviceQue);
                if (empty($remittanceData['payerId'])) {
                    throw new SynapExceptions(SynapExceptionConstants::REMITTACNE_PAYER_REQUIRED, 400);
                }
            }
            $data->setData(array('properties' => array('isApproved' => TRUE)));
        }
        /*
         * if adjustment entry contains chargeId but not claimId, we will set encounterId and claim id into adjustment object
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
        $adjustmentId = $data->getData('conditions')['object'][0];
        $adjustmentData = $this->fetchObjectData($adjustmentId, $serviceQue);
        if ($adjustmentData['status'] == self::PAYMENT_POSTED_STATUS && $adjustmentData['isApproved'] == TRUE) {
            throw new SynapExceptions(SynapExceptionConstants::PAYMENT_ADJUSTMENT_ALREADY_POSTED, 403, array('objectId' => 'Adjustment'));
        }
        $status = isset($data->getData('properties')['status']) ? $data->getData('properties')['status'] : '';
        if ($status == self::PAYMENT_POSTED_STATUS) {
            if (isset($adjustmentData['remittanceId'])) {
                $remittanceData = $this->fetchObjectData($adjustmentData['remittanceId'], $serviceQue);
                if (empty($remittanceData['payerId'])) {
                    throw new SynapExceptions(SynapExceptionConstants::REMITTACNE_PAYER_REQUIRED, 400);
                }
            }
            $data->setData(array('properties' => array('isApproved' => TRUE)));
        }
        return true;
    }

    /**
     * Function will set encouterId and claimId if exists from patientCharge object
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function setFieldsFromCharge($data, ServiceQueI $serviceQue) {
        $chargeId = $data->getData('parent');
        if (!(isset($data->getData('properties')['claimId']) && !empty($data->getData('properties')['claimId']))) {
//fetch patientCharge data
            $chargeData = $this->fetchObjectData($chargeId, $serviceQue);
            if (isset($chargeData['encounterId'])) {
                $data->setData(array('properties' => array('encounterId' => $chargeData['encounterId'])));
            }
            $data->setData(array('properties' => array('claimId' => $chargeData['patientClaimId'])));
        }
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
