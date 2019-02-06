<?php
namespace SynapEssentials\OMLBLBundle\Implementations\Remittance;

/**
 * BL class for preExecute of Remittance.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;

class PreExecuteM implements PreExecuteI {

    /**
     * This function validates data before execute create
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {        
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
        if (isset($data->getData('properties')['payerId'])) {
            $remittnacePreviousData = $this->fetchObjectData($data->getData('conditions')['object'][0], $serviceQue);
            $previousPayerId = isset($remittnacePreviousData['payerId']) ? $remittnacePreviousData['payerId'] : '';
            if ($previousPayerId && $previousPayerId != $data->getData('properties')['payerId']) {
                //payer cannot be edited for remittance
                throw new SynapExceptions(SynapExceptionConstants::REMITTANCE_PAYER_CANNOT_BE_EDITED, 400);
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

}
