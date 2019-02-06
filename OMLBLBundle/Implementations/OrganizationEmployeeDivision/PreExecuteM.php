<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationEmployeeDivision;

/**
 * BL class for preExecute of OrganizationEmployeeDivision.
 *
 * @author Sourav Bhargava <sourav.bhargava@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{
    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        //ensure the division id being passed is a location
        
         if (isset($data->getData('properties')['organizationDivisionId'])) {
               $searchDivisionKey[0]['objectId'] = $data->getData('properties')['organizationDivisionId'];
                $searchDivisionKey[0]['outKey'] = 'response';
                $divisionResp = $serviceQue->executeQue("ws_oml_read", $searchDivisionKey);
                if (empty($divisionResp['data']['response'])||($divisionResp['data']['response']['isLocation']==false)) {
                    throw new \SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions("Employee can only be assigned to a location.");
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
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
         if (isset($data->getData('properties')['organizationDivisionId'])) {
               $searchDivisionKey[0]['objectId'] = $data->getData('properties')['organizationDivisionId'];
                $searchDivisionKey[0]['outKey'] = 'response';
                $divisionResp = $serviceQue->executeQue("ws_oml_read", $searchDivisionKey);
                if (empty($divisionResp['data']['response'])||($divisionResp['data']['response']['isLocation']==false)) {
                    throw new \SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions("Employee can only be assigned to a location.");
                }
                    
         }
       
        return true;
    }
    
   
}
