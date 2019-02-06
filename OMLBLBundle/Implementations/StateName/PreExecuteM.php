<?php

namespace SynapEssentials\OMLBLBundle\Implementations\StateName;

/**
 * BL class for preExecute of stateName.
 *
 * @author Sourabh Grover <sourabh.grover@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI
{

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
     * @return void
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

}
