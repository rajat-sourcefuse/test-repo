<?php

namespace SynapEssentials\OMLBLBundle\Implementations\DataImportRequest;

use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Services\AllowImportService;
use SynapEssentials\OMLBundle\Utilities\ExcludeDependencyCheck;
use SynapEssentials\CdnBundle\Cdn\CdnHandlerFactory;
use SynapEssentials\CdnBundle\Cdn\CdnHostType;
use SynapEssentials\WorkFlowBundle\utilities\WorkFlowUtility;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use SynapEssentials\Utilities\InProcessSessionData;
use SynapEssentials\Utilities\SessionUtility;

/**
 * Description of PostExecuteM
 *
 * @author vinod
 */
class PostExecuteM implements PostExecuteI
{
    /**
     * This function use to import exported data (organization data).
     * Perform as bulk service, as well as create workflow if data include workflow informations.
     * 
     * @param array $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    {
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", -1);
        $excludeDependency = new ExcludeDependencyCheck();
        $importService     = new AllowImportService($serviceQue);
//        $serviceQue->setImportRequestStatus(true);
        
        // get content from uploaded file.
        $importRequestParam = $data->getAll();
//        $importRequestId = $importRequestParam['id'];
        $properties = $importRequestParam['properties'];
        $fileKey = $properties['importData'];
        $content = $this->getContent($fileKey);
        
        $jobList = json_decode($content, true);
        
        if (!empty($jobList)) {
            $createdWorkflows  = array();
            $setSession = false;
            // Execute JobList
            foreach ($jobList as $requestParam) {
                
                if (strtolower($requestParam['type']) == "create") {
                    $objectType = $requestParam['data']['objectType'];
                    // get organizationId which is processing in import request
                    if ($objectType == 'organization') {
                        $processingOrgId = $requestParam['data']['properties']['id'];
                    } else {
                        $processingOrgId = $requestParam['data']['properties']['organizationId'];
                    }
                    
                    // If executed service is for create workFlow.
                    // collect workFlow name which are created during import request.
                    if ($objectType == 'synapWorkflow') {
                        $createdWorkflows[] = $requestParam['data']['properties']['name'];
                    }
                }
                
                if (!empty($processingOrgId) && !$setSession) {
                    $inProcessSessionObj = new InProcessSessionData($processingOrgId);
                    $setSession = TRUE;
                }
                
                $response = $this->executeService($serviceQue, $requestParam);
                
                if ($response['status']['success'] != true) {
                    throw new SynapExceptions(SynapExceptionConstants::UNABLE_TO_IMPORT_DATA,500);
                }
            }
            
            // deploy created workflows
//            $this->workFlowProcess($createdWorkflows, $processingOrgId);
        }
        
        return;
    }
    
    /**
     * This function returns file content of given fileKey.
     * 
     * @param string $fileKey
     * @return string
     */
    private function getContent($fileKey)
    {
        $cdnHandlerFactory = CdnHandlerFactory::getInstance();
        $this->cdnHandler  = $cdnHandlerFactory->generate(CdnHostType::AWS);
        
        $content = $this->cdnHandler->downloadFile($fileKey);
        
        return $content;
    }
    
    /**
     * This function execute service with given parameters.
     * 
     * @param ServiceQue $serviceQue
     * @param array $requestParam
     * @return response
     * @throws SynapExceptions
     */
    private function executeService($serviceQue, $requestParam)
    {
        switch (strtolower($requestParam['type']))
        {
            case "create":
                $serviceName = "ws_oml_create";
                break;
            case "update":
                $serviceName = "ws_oml_update";
                break;
            case "delete":
                $serviceName = "ws_oml_delete";
                break;
            case "upload":
                $serviceName = "ws_oml_file_upload";
                $requestParam['data']['fileContent'] = unserialize(gzuncompress(base64_decode($requestParam['data']['fileContent'])));
                break;
            default:
                throw new SynapExceptions(SynapExceptionConstants::WRONG_DATA,400);
                break;
        }

        $response = $serviceQue->executeQue($serviceName, $requestParam['data']);
        
        return $response;
    }
    
    /**
     * This function use to deploy created workflow.
     * 
     * @param array $workflowNames Name workflow created.
     * @return void
     */
    private function workFlowProcess($workflowNames, $processingOrgId)
    {
        $inProcessSessionObj = new InProcessSessionData($processingOrgId);
        
        $configrator = Configurator::getInstance();
        $serviceContainer = $configrator->getServiceContainer();

        $content = array();
        foreach ($workflowNames as $workflowName) {
            $content[] = array (
                'workflowName' => $workflowName,
                'deploy'       => true,
            );
        }

        $contentJson = json_encode($content);
        WorkFlowUtility::createWorkFlow($serviceContainer, $contentJson);
        
        return;
    }
    
    /**
     * Update PostExecute.
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * Delete postExecute
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
     * Get post execute
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
     * View post execute
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }
    
}
