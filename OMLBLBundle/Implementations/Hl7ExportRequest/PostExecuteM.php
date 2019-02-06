<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Hl7ExportRequest;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author nitesh Gupta <nitesh.srivastava@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    /**
     * @description function will perform some actions after execute delete
     * @author nitesh Gupta <nitesh.srivastava@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * @description function will perform some actions after execute delete
     * @author nitesh Gupta <nitesh.srivastava@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
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
     * @description function will perform some actions after execute update
     * @author nitesh Gupta <nitesh.srivastava@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

}
