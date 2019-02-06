<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationStateConfig;

/**
 * BL class for preExecute of OrganizationStateConfig.
 *
 * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{

    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $properties = $data->getData('properties');
        if (isset($properties['formId'])) {
            $condition = array(array('formId' => $properties['formId']));
            $arrData = $this->fetchObjectData($serviceQue, 'organizationStateConfig', $condition);
            if (!empty($arrData)) {
                throw new SynapExceptions(SynapExceptionConstants::FORM_CONFIG_ALREADY_EXISTS, 400);
            }
        }
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return void
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

     /**
     * Function will fetch the object data based on conditions
     * 
     * @param type $serviceQue, $objectType, $conditions
     * @return $objectData
     */
    private function fetchObjectData($serviceQue, $objectType, $conditions) {
        $searchKey = [];
        $searchKey[0]['type'] = $objectType;
        $searchKey[0]['conditions'] = $conditions;
        $searchKey[0]['outKey'] = 'response';
        $objectResp = $serviceQue->executeQue('ws_oml_read', $searchKey);
        return $objectResp['data']['response'];
    }
}
