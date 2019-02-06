<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationEmployeeDivisionProgram;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\Utilities\groupUtility;
use SynapEssentials\OpenamBundle\Openam\OpenamHandler;
/**
 * BL class for postExecute of OrganizationEmployeeDivisionProgramRole.
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    /**
     * It will create groupId and store it against the synapUser of the employee
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
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
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

}
