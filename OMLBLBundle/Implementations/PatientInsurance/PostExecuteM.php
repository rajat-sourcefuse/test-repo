<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientInsurance;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of PatientWarning.
 *
 * @author Vinod Vaishnav <vinod.vaishnav@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{
    
    const RANK = 'insuranceCompanyRank';
    const TERTIARY_RANK = 'metaInsuranceCompanyRank:three';
    const SECONDARY_RANK = 'metaInsuranceCompanyRank:two';
    const PRIMARY_RANK = 'metaInsuranceCompanyRank:one';

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $insuranceId = $data->getData('id');
        $props = $data->getData('properties');
        $insuranceRank = $props[self::RANK];
        $claimId = (isset($props['claimId']) ? $props['claimId'] : '');
        if (!empty($claimId)) { // versionedInsuranceClaimTierUpdate
            $this->versionedInsuranceClaimTierUpdate($serviceQue, $insuranceRank, $claimId, $insuranceId);
        }
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
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
     * This function versionedInsuranceClaimTierUpdate($serviceQue, $insuranceRank, $claimId)
     * 
     * @author Kuldeep Singh <kuldeep.singh@sourcefuse.com>
     * @param type $serviceQue, $insuranceRank, $claimId, $insuranceId
     * @throws SynapExceptions
     */
    private function versionedInsuranceClaimTierUpdate($serviceQue, $insuranceRank, $claimId, $insuranceId) {
        $arrTierWiseLevel = array(self::PRIMARY_RANK => 'primaryPayerProfileId', self::SECONDARY_RANK => 'secondaryPayerProfileId', self::TERTIARY_RANK => 'tertiaryPayerProfileId');
        $updateObjs = array();
        $updateObjs['conditions']['object'][0] = $claimId;
        $updateObjs['properties'][$arrTierWiseLevel[$insuranceRank]] = $insuranceId;
        $serviceQue->executeQue("ws_oml_update", $updateObjs);
    }

}