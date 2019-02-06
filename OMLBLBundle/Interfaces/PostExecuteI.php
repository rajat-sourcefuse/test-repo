<?php

namespace SynapEssentials\OMLBLBundle\Interfaces;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
interface PostExecuteI
{

    /**
     * function will validate data after execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue);

    /**
     * function will validate data after execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue);

    /**
     * function will validate data after execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue);
    
    public function postExecuteGet($data, ServiceQueI $serviceQue);
    
    public function postExecuteView($data, ServiceQueI $serviceQue);
}
