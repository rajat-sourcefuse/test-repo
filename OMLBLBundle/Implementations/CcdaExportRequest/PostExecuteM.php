<?php

namespace SynapEssentials\OMLBLBundle\Implementations\CcdaExportRequest;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use Externals\CCDABundle\Handlers\CCDAExportHandler;
use Externals\CCDABundle\Utilities\CCDACONSTANTS;
/**
 * PostExecuteM is for handling the business logic in post execution of services
 *
 * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{

    /**
     * @description this function will start process of creating ccda document and 
     * sending it via EMR direct mail
     * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        $result = $data->getAll();
        $objectType = $result["objectType"];
        $requestId = $result["id"];
        $recieverEmail = $result["properties"]["recieverDirectAddress"];
        $patientIds = [];
        if(isset($result["properties"]["allPatient"]) && $result["properties"]["allPatient"]=="1"){
            $patientRequest = [];
            $patientRequest[] = array("type"=>"patient","outKey"=>"info","requiredAdditionalInfo"=>"0");
            $patients = $serviceQue->executeQue(CCDACONSTANTS::DB_READ, $patientRequest);
            if(count($patients["data"]["info"])>0){
                foreach($patients["data"]["info"] as $patient){
                    $patientIds[] = $patient["id"];
                }
            }
            
        }elseif(isset($result["properties"]["patientIds"])){
            $patientIds = array_unique($result["properties"]["patientIds"]);
        }

        if(count($patientIds)>0){
            foreach($patientIds as $patientId){
                $requestData = [];
                $requestData["recieverDirectAddress"] = $result["properties"]["recieverDirectAddress"];
                $requestData["clinicalDocumentType"] = $result["properties"]["clinicalDocumentType"];
                $requestData["careContinuityDocumentType"] = "metaCareContinuityDocumentType:CCDA";
                $requestData["status"] = "metaCcdaExportStatus:pending";
                $requestData["ccdaExportRequestId"] = $requestId;

                $request = array();
                $request["objectType"] = "ccdaExport";
                $request["properties"] = $requestData;
                $request["parent"] = $patientId;
                $serviceQue->executeQue(CCDACONSTANTS::DB_CREATE, $request);
            }
        }
        return true;
    }

    /**
     * @description function will perform some actions after execute delete
     * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
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
     * @author Vishal Gupta <vishal.gupta@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

}
