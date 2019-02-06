<?php

namespace SynapEssentials\OMLBLBundle\Implementations\CcdaExport;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use Externals\CCDABundle\Handlers\CCDAExportHandler;
use Externals\CCDABundle\Utilities\CCDACONSTANTS;
use Externals\CCDABundle\Utilities\CommonFunctions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\Utilities\SessionUtility;

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
        $sessionUtil = SessionUtility::getInstance();
        $userType = $sessionUtil->getUserType();
        
        $ccdaExportStatus = "";
        $result = $data->getAll();        
        if(isset($result["properties"]["status"]))
            $ccdaExportStatus = $result["properties"]["status"];

        if($ccdaExportStatus != "metaCcdaExportStatus:pending"){
            $objectType = $result["objectType"];
            $requestId = $result["id"];
            $patientId = $result["parent"];
            $includeSections = [];
            if(isset($result["properties"]["includeSections"]))
                $includeSections = $result["properties"]["includeSections"];

            $recieverEmail ="";
            $ccdaType = "";
            if(isset($result["properties"]["recieverDirectAddress"]))
                $recieverEmail = $result["properties"]["recieverDirectAddress"];
            $encounterId = "";
            if(isset($result["properties"]["patientEncounterId"]))
                $encounterId = $result["properties"]["patientEncounterId"];
            if(isset($result["properties"]["clinicalDocumentType"]))
                $ccdaType = $result["properties"]["clinicalDocumentType"];

            try{
                $ccdaExport = new CCDAExportHandler($objectType);
                $ccdaExport->setDetails($requestId,$patientId,$recieverEmail,$encounterId,$ccdaType);
                $ccdaExport->setIncludedSections($includeSections);
                $ccdaExport->export();
            }catch(\Exception $ex) { 
                $updateResponse = CommonFunctions::runExternalUpdate($requestId,
                            array("status"=>CCDACONSTANTS::CCDA_STATUS_FAILED));
                throw new SynapExceptions($ex->getMessage(),400);
            }
        }
        
        // ccdaExportLog
        $recieverDirectAddress = null;
        if (!empty($result["properties"]["recieverDirectAddress"])) {
             $recieverDirectAddress = $result["properties"]["recieverDirectAddress"];
        }
        
        if ($userType == 'patient' && !is_null($recieverDirectAddress)) {
            $ccdaExportId = $data->getData('id');
            
            $ccdaExportLogObject = array (
                'parent'     => $patientId,
                'objectType' => 'ccdaExportLog',
                'properties' => array(
                    'logType'      => 'metaCcdaExportLogType:transmit',
                    'ccdaExportId' => $ccdaExportId,
                )
            );

            $serviceQue->executeQue('ws_oml_create', $ccdaExportLogObject);
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
        $sessionUtil = SessionUtility::getInstance();
        $userType = $sessionUtil->getUserType();
        
        if ($userType == 'patient') {
            $inputParams = $data->getAll();
            $action  = $inputParams['action'];
            $ccdaExportId = $inputParams['objectId'];

            $logType = 'metaCcdaExportLogType:view';
            if ($action == 'download') {
                $logType = 'metaCcdaExportLogType:download';
            }

            $ccdaExportLogObject = array (
                'parent'     => $inputParams['object']['patientId'],
                'objectType' => 'ccdaExportLog',
                'properties' => array(
                    'logType'       => $logType,
                    'ccdaExportId' => $ccdaExportId,
                )
            );

            $serviceQue->executeQue('ws_oml_create', $ccdaExportLogObject);
        }
        
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
