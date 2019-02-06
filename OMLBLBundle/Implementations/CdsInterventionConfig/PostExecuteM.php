<?php

namespace SynapEssentials\OMLBLBundle\Implementations\CdsInterventionConfig;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    /**
     * This function insert record for patient roi after assigning division to patient.
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return type
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $properties = $data->getData('properties');
        $parentId = $data->getData('id');
        $this->createCdsInterventionLog($parentId, $properties, $serviceQue);
        return true;
    }
    
    /**
     * This will create CDS Intervention Log
     * @param string $parentId
     * @param array $properties
     * @param object $serviceQue
     */
    private function createCdsInterventionLog($parentId, $properties, $serviceQue)
    {
        $notReqProperties = array('id', 'isDeleted', 'createdBy', 'createdOnDate', 'createdOnTime', 'objectPath', 'checksum', 'organizationId','cdsInterventionCondition');
        foreach ($notReqProperties as $key => $property) {
            if (isset($properties[$property])) {
                unset($properties[$property]);
            }
        }
        $objData['parent'] = $parentId;
        $objData['objectType'] = "cdsInterventionConfigLog";
        $objData["properties"] = $properties;
        $serviceQue->executeQue('ws_oml_create', $objData);
    }

    public function postExecuteDelete($data, ServiceQueI $serviceQue)
    {
        return;
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

    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $parentId = $data->getData('conditions')['object'][0];
        $properties = $data->getData('properties');
        $this->createCdsInterventionLog($parentId, $properties, $serviceQue);
        return true;
    }

}
