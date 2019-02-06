<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientPrintView;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Piyush Arora <piyush.arora@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PostExecuteM implements PostExecuteI {

    /**
     * function will validate data before execute create
     * 
     * @author Piyush Arora <piyush.arora@sourcefuse.com>
     * @param SynapEssentials\OMLBundle\Notifier\OMLServiceData $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {
        $report = $data->getData('report');
        if(isset($report) && is_array($report)) {
            $updatedData['data']['reportUrl'] = $report['cdnUrl'];
            $data->setData($updatedData, 'resp');
            return true;
        }
    }

    /**
     * @description function will validate data before execute delete
     * @author Piyush Arora <piyush.arora@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * @description function will validate data before execute update
     * @author Piyush Arora <piyush.arora@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
      
        return true;
    }
}
