<?php
namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationTaxEntityPayerConfig;
/**
 * BL class for preExecute of organizationTaxEntityPayerConfig.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
class PreExecuteM implements PreExecuteI {
    /**
     * This function will execute just before the creation of organization tax entity payer config 
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        $this->chkPayerTaxEntityAssignment($data, $serviceQue);
        return true;
    }
    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
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
     * This function will execute just before the updation of tax entity payer config
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        if (
                isset($data->getData('properties')['organizationPayerId'])
                ||
                isset($data->getData('properties')['locationId'])
        ) {
            $this->chkPayerTaxEntityAssignment($data, $serviceQue);
        }
        return true;
    }
    /**
     * This function will make sure only one tax entity/payer combination exists
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return
     */
    private function chkPayerTaxEntityAssignment($data, ServiceQueI $serviceQue) {
        $payerTaxEntityData = $this->getPayerTaxEntityData($data, $serviceQue);
        if (!empty($payerTaxEntityData)) {
            throw new SynapExceptions(SynapExceptionConstants::TAX_ENTITY_PAYER_CONFIG_EXISTS,400);
        }
        return;
    }
    /**
     * This function get data based on taxEntity and payer Id
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return $payerTaxEntityData
     */
    private function getPayerTaxEntityData($data, ServiceQueI $serviceQue) {
        $searchKey = [];
        $searchKey[0]['type'] = 'organizationTaxEntityPayerConfig';
        /*
         * Check organizationPayerId & locationId isset before adding in $searchKey.
         * Author Kuldeep Singh: Date: 25-07-2017
         */
        if (isset($data->getData('conditions')['object'])) {
            $updateCondition = $data->getData('conditions');
            $orgTaxConfigId = $updateCondition['object'][0];
            $searchExistKey = [];
            $searchExistKey[0]['objectId'] = $orgTaxConfigId;
            $searchExistKey[0]['sendNullKey'] = 1;
            $searchExistKey[0]['outKey'] = 'response';
            $existingData = $serviceQue->executeQue('ws_oml_read', $searchExistKey);
        }
        if (isset($data->getData('properties')['organizationPayerId'])) {
            $searchKey[0]['conditions'][] = array('organizationPayerId' => $data->getData('properties')['organizationPayerId']);
        } elseif (isset($existingData) && isset($existingData['data']['response']['organizationPayerId'])) {
            $searchKey[0]['conditions'][] = array('organizationPayerId' => $existingData['data']['response']['organizationPayerId']);
        }
        // $searchKey[0]['conditions'][] = array('taxEntityId'=>$data->getData('properties')['taxEntityId']);
        if (isset($data->getData('properties')['locationId'])) {
            $searchKey[0]['conditions'][] = array('locationId' => $data->getData('properties')['locationId']);
        } elseif (isset($existingData) && isset($existingData['data']['response']['locationId'])) {
            $searchKey[0]['conditions'][] = array('locationId' => $existingData['data']['response']['locationId']);
        }
        //for pre update case
        if (isset($data->getData('conditions')['object'][0])) {
            $searchKey[0]['conditions'][] = array('id' => array('NE' => $data->getData('conditions')['object'][0]));
        }
        $searchKey[0]['requiredAdditionalInfo'] = "0";
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $payerTaxEntityData = $resp['data']['response'];
        return $payerTaxEntityData;
    }
}