<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationEmployeeDivisionProgram;

/**
 * BL class for preExecute of OrganizationEmployeeDivisionProgram.
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
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
        // Get current object data
        $obj = $data->getData('conditions')['object'][0];
        $this->removeSynapuserGroups($obj, $serviceQue);
        return true;
    }
    
    /**
     * 
     * @param type $obj
     */
    private function removeSynapuserGroups($obj, $serviceQue)
    {
        //line added by sourav Bhupesh said to add it as the following lines are
        //no more valid. should be removed by Bhupesh after scruitinizing
        return true;

      
    }
}
