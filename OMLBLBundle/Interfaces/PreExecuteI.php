<?php

namespace SynapEssentials\OMLBLBundle\Interfaces;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
interface PreExecuteI
{

    /**
     * function will validate data before execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue);

    /**
     * function will validate data before execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue);

    /**
     * function will validate data before execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue);
    
    public function preExecuteGet($data, ServiceQueI $serviceQue);
    
    public function preExecuteView($data, ServiceQueI $serviceQue);
}
