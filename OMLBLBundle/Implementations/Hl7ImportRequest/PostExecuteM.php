<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Hl7ImportRequest;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use Externals\Hl7Bundle\Handler\MessageParser;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    /**
     * @description function will perform some actions after execute create
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        //get service container
        $configurator = Configurator::getInstance();
        //get class object
        $messageParser = new MessageParser($configurator->getServiceContainer());

        //get patientId and messageType
        $reqData = $data->getAll();
        $hl7messageId = $reqData['id'];
        //call method to extract and store data at synap
        $resp = $messageParser->extractData($hl7messageId);
        if (!empty($resp)) {
            $updatedData['status']['warning'] = $resp;
            $data->setData($updatedData, 'resp');
        }
        return $resp;
    }

    /**
     * @description function will perform some actions after execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
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
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

}
