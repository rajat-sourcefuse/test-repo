<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Adjustment;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
/**
 * BL class for postExecute of adjustment.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\OMLBundle\Utilities\PostRemittanceUtility;

class PostExecuteM implements PostExecuteI {

    private $objPostRemittanceUtils;

    const PAYMENT_POSTED_STATUS = 'metaPaymentStatus:posted';
    const PROCESSED_AS_PRIMARY = 'P';

    public function __construct() {
        $this->objPostRemittanceUtils = new PostRemittanceUtility();
    }

    /**
     * Function will perform the post execute operation of adjustment
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {
        // update journal for adjustment
        $status = isset($data->getData('properties')['status']) ? $data->getData('properties')['status'] : '';
        $processedAs = isset($data->getData('properties')['processedAs']) ? $data->getData('properties')['processedAs'] : '';
        if ($status == self::PAYMENT_POSTED_STATUS) {
            if ($processedAs == self::PROCESSED_AS_PRIMARY) {
                $this->objPostRemittanceUtils->makeJournalEntry('adjustment', $data, $serviceQue);
            }
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
        // update journal for adjustment
        $adjustmentId = $data->getData('conditions')['object'][0];
        $adjustmentData = $this->fetchObjectData($adjustmentId, $serviceQue);
        $status = isset($data->getData('properties')['status']) ? $data->getData('properties')['status'] : '';
        $processedAs = isset($adjustmentData['processedAs']) ? $adjustmentData['processedAs'] : '';
        if ($status == self::PAYMENT_POSTED_STATUS) {
            if ($processedAs == self::PROCESSED_AS_PRIMARY) {
                $this->objPostRemittanceUtils->makeJournalEntry('adjustment', $data, $serviceQue);
            }
            if (isset($adjustmentData['claimId']) && !empty($adjustmentData['claimId'])) {
                // function will check payment against claim and update status to closed, if payment sum linked to encounter is greater than or equal to patient claim balance
                $this->objPostRemittanceUtils->checkAndUpdateClaim($adjustmentData, $serviceQue);
            }
        }
        $remittanceId = isset($data->getData('properties')['remittanceId']) ? $data->getData('properties')['remittanceId'] : '';
        if (empty($remittanceId)) {
            $remittanceId = isset($adjustmentData['remittanceId']) ? $adjustmentData['remittanceId'] : '';
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
    private function fetchObjectData($objectId, $serviceQue) {
        //fetch object date
        $searchKey = [];
        $searchKey[0]['objectId'] = $objectId;
        $searchKey[0]['outKey'] = 'response';
        $objectResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        return $objectResp['data']['response'];
    }

}
