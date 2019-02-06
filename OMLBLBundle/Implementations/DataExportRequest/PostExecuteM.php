<?php

namespace SynapEssentials\OMLBLBundle\Implementations\DataExportRequest;

use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBundle\Utilities\OMLObjectUtility;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLSyncronizerBundle\utilities\Structures_Graph;
use SynapEssentials\OMLSyncronizerBundle\utilities\Structures\Graph\Node;
use SynapEssentials\OMLSyncronizerBundle\utilities\Structures\Graph\Manipulator\AcyclicTest;
use SynapEssentials\OMLSyncronizerBundle\utilities\Structures\Graph\Manipulator\TopologicalSorter;
use SynapEssentials\CdnBundle\Cdn\CdnHandlerFactory;
use SynapEssentials\CdnBundle\Cdn\CdnHostType;

/**
 * Description of PostExecuteM
 *
 * @author vinod
 */
class PostExecuteM implements PostExecuteI 
{
    private $omlObjectList    = array();
    private $omlPropertyList  = array();
    private $objectIds        = array();
    private $updateRequest    = array();
    private $deleteRequest    = array();
    private $uploadRequest    = array();

    /**
     * This function updates exportRequest.
     * In sequence of generating exportData file fetching OrgHierarchyData and metaData.
     * generat json file and upload on s3 storage.
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     * @throws SynapExceptions
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) 
    {
        ini_set("max_execution_time", 0);
        ini_set("memory_limit", -1);

        $serviceQue->setImportRequestStatus(true);
        $processedData = $data->getAll();
        $exportRequestId = $processedData['id'];
        $properties = $processedData['properties'];
        $familyTitle = $properties['dataFamily'];
        $organizationId = $properties['organizationId'];

        $exportedData = array();
        // get org specific meta object
        $metaObjectType = $this->getMetaList($serviceQue);
        $metaDataArray = $this->getDataFromObjectType($metaObjectType, $organizationId, $serviceQue);
        $this->combineRequestParams($exportedData, $metaDataArray);
        
        // get data from some additional tables
        $additionalObjectType = array('state', 'stateName');
        $additionalDataArray = $this->getDataFromObjectType($additionalObjectType, $organizationId, $serviceQue);
        $this->combineRequestParams($exportedData, $additionalDataArray);
        
        // get synapUser hierarchy data.
        $excludeObjectList = array('synapUserToken', 'synapUserGroup');
        $userDataArray = $this->getHierarchyData('synapUser', $excludeObjectList, $organizationId, $serviceQue);        
        $this->combineRequestParams($exportedData, $userDataArray);

        if ($familyTitle == 'metaDataFamily:organization') {
            $excludeObjectList = array('drFirstConfig', 'appointmentSchedule', 
                'txPlanConfig', 'organizationFolder', 'journal');
            $orgDataArray = $this->getHierarchyData('organization', $excludeObjectList, $organizationId, $serviceQue);
            $this->combineRequestParams($exportedData, $orgDataArray);
        }
        
        // get synapWorkflow hierarchy data.
        $excludeObjectList = array('synapWorkflowVariables');
        $workflowDataArray = $this->getHierarchyData('synapWorkflow', $excludeObjectList, $organizationId, $serviceQue);        
        $this->combineRequestParams($exportedData, $workflowDataArray);
        
        // Process data for required modification in data set.
        $this->processData($exportedData);
        
        // collect data
        $objectsArray = $this->convertToObjectArray($exportedData);
        
        $importRequest = $this->prepareBulkRequest($objectsArray, $serviceQue);
        
        $fileKey = $this->uploadJson($importRequest, $serviceQue);
        
        $this->updateExportRequest($exportRequestId, $fileKey, $serviceQue);
        
        return true;
    }
    
    /**
     * This function updateExportRequest by updating exportData url.
     * 
     * @param type        $exportRequestId    request Id
     * @param type        $fileKey            fileKey for exported data
     * @param ServiceQueI $serviceQue         serviceQ
     * @return void 
     * @throws SynapExceptions
     */
    private function updateExportRequest($exportRequestId, $fileKey, ServiceQueI $serviceQue) 
    {
        $updateRequest = array(
            'properties' => array(
                'exportData' => $fileKey
            ),
            'conditions' => array(
                'object' => array($exportRequestId)
            )
        );
        $response = $serviceQue->executeQue('ws_oml_update', $updateRequest);

        if ($response['status']['success'] != true) { // Unable to write tempTable
            throw new SynapExceptions(SynapExceptionConstants::INTERNAL_SERVER_ERROR,500);
        }

        return;
    }
    
    /**
     * 
     * @param type $multiObjDataArray
     * @return type
     */
    private function processData(&$multiObjDataArray)
    {
        // Generate newObjectIds for old one.
        foreach ($multiObjDataArray as $objectType => $dataArray) {
            foreach ($dataArray as $data) {
                $id = $data['id'];
                $newId = OMLObjectUtility::createNewObjectID($objectType);
                if (!isset($this->objectIds[$id])) {
                    $this->objectIds[$id] = $newId;
                }
            }
        }
        
        // Unset isDeleted and Replace objectIds
        foreach ($multiObjDataArray as $objectType => &$dataArray) {
            $propertyMetaArray = OMLObjectUtility::getObjectPropMeta($objectType);            
            foreach ($dataArray as &$data) {
                // Process at propertyLevel
                foreach ($data as $key => $val) {
                    // Fix: propertyMetaData doesn't have isDeleted property
                    if ($key == 'isDeleted' || empty($val)) {
                        continue;
                    }
                    $propertyMetaData = $propertyMetaArray[$key];
                    $isRequired = $propertyMetaData->isRequired();
                    $dataType   = $propertyMetaData->getPropertyDataType();
                    
                    // Remove properties which have default values
                    $defaultValue = $propertyMetaData->getDefaultValue();
                    if (is_scalar($val) && $defaultValue == $val) {
                        unset ($data[$key]);
                    }
                    
                    // If property having cdnUrl data then download content and generate new fileKey.
                    if (strtolower($dataType) == 'cdnurlpublic' || 
                        strtolower($dataType) == 'cdnurlprivate') {

                            $cdnTempFileId = $this->updateCdnData($objectType, $key, $val);
                            if ($cdnTempFileId) {
                                $data[$key] = $cdnTempFileId;
                            } else {
                                unset($data[$key]);
                            }
                            continue;
                        }
                    
                    // replace old objectId to new objectId
                    // If vaule is a objectId and its new replaceValue is available than change it by new value
                    // ObjectIds are replacing due to some testing requirements as same objectId cannot create twice.
                    if (is_string($val) && isset($this->objectIds[$val])) {
                        $data[$key] = $this->objectIds[$val];
                    } elseif (is_array($val)) {
                        if ($dataType == 'json') {
                            $newVal = $val;
                            $this->updateJsonData($newVal);                            
                        } else {
                            $newVal = array();
                            foreach ($val as $k => $v) {
                                if(is_string($v) && isset($this->objectIds[$v])) {
                                    $newVal[$k] = $this->objectIds[$v];
                                } else {
                                    $newVal[$k] = $v;
                                }
                            }
                        }
                        
                        $data[$key] = $newVal;
                    }
                }
                
                // If record is deleted than store its Id in another array so bulk delete can be performed.
                if ($data['isDeleted'] == true) {
                    $this->deleteRequest[] = $data['id'];
                }
                
                // Unset isDeleted because isDeleted is not accepted by create service.
                if (isset($data['isDeleted'])) {
                    unset($data['isDeleted']);
                }
                // remove checksum
                if (isset($data['checksum'])) {
                    unset($data['checksum']);
                }
                // remove objectPath
                if (isset($data['objectPath'])) {
                    unset($data['objectPath']);
                }
            }
        }
        
        return;
    }
    
    /**
     * This function update ids inside jsonData.
     * 
     * @param array $jsonData
     * @return void
     */
    private function updateJsonData(&$jsonData)
    {
        foreach ($jsonData as $key => &$data) {
            if (is_array($data)) {
                $this->updateJsonData($data);
            }
            
            if (is_string($data) && isset($this->objectIds[$data])) {
                $jsonData[$key] = $this->objectIds[$data];
            }
        }
        
        return;
    }
    
    /**
     * This function create request for uploadFile service
     * and returns new cdnTempFileId.
     * 
     * @param type $cdnFileKey
     */
    private function updateCdnData($objectType, $propertyName, $cdnFileKey)
    {
        $objectMetaData = OMLObjectUtility::getObjectMetaData($objectType);
        $propertyMetaData = $objectMetaData->getPropertyByName($propertyName);
        $dataAttribute = $propertyMetaData->getPropertyDataAttributes();
        
        $dataType = $propertyMetaData->getPropertyDataType();
        $isPublic = 0;
        if (strtolower($dataType) == 'cdnurlpublic') {
            $isPublic = 1;
        }
        
        // generate new cdnTempFileId
        $cdnTempFileId = OMLObjectUtility::createNewObjectID('cdnTempFile');
        
        // get file content
        $cdnHandlerFactory = CdnHandlerFactory::getInstance();
        $cdnHandler  = $cdnHandlerFactory->generate(CdnHostType::AWS);
        
        try {
            $fileContent = $cdnHandler->downloadFile($cdnFileKey, $isPublic);
        } catch (\Exception $ex) {
            $fileContent =  "Unable to read from CDN";
        }
        
        // Prepare content upload request
        $fileUploadRequest = array (
            'id'           => $cdnTempFileId,
            'fileName'     => $cdnFileKey,
            'objectType'   => $objectType,
            'property'     => $propertyName,
        );
        
        if (isset($dataAttribute['fileExt']) && $dataAttribute['fileExt'] == 'json') {
            $encodedContent = json_decode($fileContent, true);
            if (empty($encodedContent)) {
                $encodedContent = array("Invalid Json" => $fileContent);
            }
        } else {
            if (empty($fileContent)) {
                $fileContent = array("Content not available");
            }
            $encodedContent = base64_encode($fileContent);
        }
        
        if (empty($encodedContent)) {
            return false;
        }
        
        $fileUploadRequest['fileContent'] = base64_encode(gzcompress(serialize($encodedContent)));
        $this->uploadRequest[] = $fileUploadRequest;
        
        return $cdnTempFileId;
    }

    /**
     * 
     * @param type $json
     * @param ServiceQueI $serviceQue
     * @return type
     * @throws SynapExceptions
     */
    private function uploadJson($data, ServiceQueI $serviceQue) 
    {
        $uploadRequest = array(
            'objectType'  => 'dataExportRequest',
            'property'    => 'exportData',
            'fileName'    => 'dataExport.json',
            'fileContent' => $data
        );

        $response = $serviceQue->executeQue('ws_oml_file_upload', $uploadRequest);

        if ($response['status']['success'] != true) { // Unable to write tempTable
            throw new SynapExceptions(SynapExceptionConstants::INTERNAL_SERVER_ERROR,500);
        }

        $fileKey = $response['data']['id'];

        return $fileKey;
    }

    /**
     * Preparing create bulk request for given dataArray.
     * 
     * @param type $dataArray Request parameters
     * @param type $bulkRequestArray
     * @param ServiceQueI $serviceQue
     * @return type
     */
    private function prepareBulkRequest($dataArray, ServiceQueI $serviceQue)
    {
        $bulkRequestArray = array();
        $sortedObjectIds = $this->getSortedObjectList($dataArray, $serviceQue);
        $omlObjectList    = $this->getOmlObjectList($serviceQue);
        
        // Prepare upload bulk request
        foreach ($this->uploadRequest as $uploadParam) {
             $uploadRequestParam = array (
                'type'  => 'upload',
                'data'  => $uploadParam
            );
            
            $bulkRequestArray[] = $uploadRequestParam;
        }
        
        // Prepare Insert bulk request
        foreach ($sortedObjectIds as $objectId) {
            $objIdComponent = explode(':', $objectId);
            if (count($objIdComponent) == 2) {
                $objectType = $objIdComponent[0];
            } else {
                continue;
            }
            
            $requestParam = array(
                'type'  => 'create',
                'data'  => array(
                    'objectType' => $objectType,
                    'properties' => $dataArray[$objectId],
                )
            );

            if (!empty($omlObjectList[$objectType]['parent'])) {
                $parentProperty = $omlObjectList[$objectType]['parent'].'Id';
                $parentValue    = $dataArray[$objectId][$parentProperty];
                $requestParam['data']['parent'] = $parentValue;
            }
            
            $bulkRequestArray[] = $requestParam;
        }
        
        // Prepare Update request
        foreach ($this->updateRequest as $objectId => $properties) {
            if (count($properties) == 0) {
                continue;
            }

            $updateRequestParam = array (
                'type'  => 'update',
                'data'  => array(
                    'properties' => $properties,
                    'conditions' => array(
                        'object' => array($objectId)
                    )
                )
            );
            
            $bulkRequestArray[] = $updateRequestParam;
        }
        
        // prepare Delete request
        foreach ($this->deleteRequest as $objectId) {
            $deleteRequestParam = array (
                'type'  => 'delete',
                'data'  => array($objectId)
            );
            
            $bulkRequestArray[] = $deleteRequestParam;
        }
        
        return $bulkRequestArray;
    }
    
    /**
     * 
     * @param type $objectTypeArray
     * @param type $organizationId
     * @param ServiceQueI $serviceQue
     * @return type
     * @throws SynapExceptions
     */
    private function getDataFromObjectType($objectTypeArray, $organizationId, ServiceQueI $serviceQue)
    {
        $requestParam = array();
        foreach ($objectTypeArray as $objectName) {
            $requestParam[] = array(
                'type'      => $objectName,
                'outKey'    => $objectName,
                'conditions'=> array(array(
                    'organizationId' => $organizationId,
                )),
                'requiredCount'            => 0,
                'includeDeleted'           => 1,
                'requiredAdditionalInfo'   => 0,
                'requiredCalculatedFields' => 0,
//               'sendNullKey'              => 1,
            );
        }

        $response = $serviceQue->executeQue('ws_oml_read', $requestParam);
        if ($response['status']['success'] != true) { // Unable to write tempTable
            throw new SynapExceptions(SynapExceptionConstants::INTERNAL_SERVER_ERROR,500);
        }

        $resultData = $response['data'];

        return $resultData;
    }
    
    /**
     * 
     * @param type $familyHead
     * @param type $excludeObjectList
     * @param type $organizationId
     * @param ServiceQueI $serviceQue
     * @return array
     * @throws SynapExceptions
     */
    private function getHierarchyData($familyHead, $excludeObjectList, $organizationId, ServiceQueI $serviceQue)
    {
        $childHierarchy = $this->getChildHierarchy($familyHead, $excludeObjectList, $serviceQue);
        $childRequest = $this->prepareChildRequest($childHierarchy);
        
        $conditions = array();
        if ($familyHead == 'organization') {
            $conditions[] = array('id' => $organizationId);
        } else {
            $conditions[] = array('organizationId' => $organizationId);
        }
        
        $orgRequest = array(array(
            'type'       => $familyHead,
            'outKey'     => $familyHead,
            'conditions' => $conditions,
            'child'      => $childRequest,
            'requiredCount'            => 0,
            'includeDeleted'           => 1,
            'requiredAdditionalInfo'   => 0,
            'requiredCalculatedFields' => 0,
//           'sendNullKey'              => 1,
        ));
        
        $response = $serviceQue->executeQue('ws_oml_read', $orgRequest);
        if ($response['status']['success'] != true) { // Unable to write tempTable
            throw new SynapExceptions(SynapExceptionConstants::INTERNAL_SERVER_ERROR,500);
        }
        
        $flattenDataArray = array();
        $this->flattenData($familyHead, $response['data'][$familyHead], $flattenDataArray, $serviceQue);
        
        return $flattenDataArray;
    }

    /**
     * 
     * @param type $familyHierarchy
     * @return type
     */
    private function prepareChildRequest($familyHierarchy) 
    {
        $request = array();

        foreach ($familyHierarchy as $objectType => $children) {
            $childRequest = array(
//                'sendNullKey'              => 1,
                'requiredCount'            => 0,
                'includeDeleted'           => 1,
                'requiredAdditionalInfo'   => 0,
                'requiredCalculatedFields' => 0,
            );
            $childRequest['type'] = $objectType;
            if ($objectType == 'account') {
                $childRequest['conditions'][] = array(
                    'patientId' => array('ISNULL' => true)
                );
            }

            if (is_array($children) && !empty($children)) {
                $childRequest['child'] = $this->prepareChildRequest($children);
            }

            $request[] = $childRequest;
        }

        return $request;
    }

    /**
     * 
     * @param ServiceQueI $serviceQue
     * @return type
     * @throws SynapExceptions
     */
    private function getOmlObjectList(ServiceQueI $serviceQue) 
    {
        if (empty($this->omlObjectList)) {
            $requestParam = array(array(
                'type' => 'omlObject',
                'outKey' => 'response',
                'sendNullKey' => 1,
                'requiredCount' => 0,
                'requiredAdditionalInfo' => 0,
                'requiredCalculatedFields' => 0,
            ));

            $response = $serviceQue->executeQue('ws_oml_read', $requestParam);
            if ($response['status']['success'] != true) { // Unable to write tempTable
                throw new SynapExceptions(SynapExceptionConstants::INTERNAL_SERVER_ERROR,500);
            }

            $resultData = $response['data']['response'];

            $omlObjectList = array();
            foreach ($resultData as $omlObject) {
                $objectName = $omlObject['name'];
                $omlObjectList[$objectName] = $omlObject;
            }

            $this->omlObjectList = $omlObjectList;
        }

        return $this->omlObjectList;
    }

    /**
     * 
     * @param ServiceQueI $serviceQue
     * @return type
     * @throws SynapExceptions
     */
    private function getOmlPropertyList(ServiceQueI $serviceQue) 
    {
        if (empty($this->omlPropertyList)) {
            $omlProperties = array();
            $requestParam = array(array(
                    'type' => 'omlProperty',
                    'outKey' => 'response',
                    'sendNullKey' => 1,
                    'requiredCount' => 0,
                    'requiredAdditionalInfo' => 0,
                    'requiredCalculatedFields' => 0,
            ));

            $response = $serviceQue->executeQue('ws_oml_read', $requestParam);
            if ($response['status']['success'] != true) { // Unable to write tempTable
                throw new SynapExceptions(SynapExceptionConstants::INTERNAL_SERVER_ERROR,500);
            }

            $resultData = $response['data']['response'];
            foreach ($resultData as $propertyMeta) {
                $objectName = $propertyMeta['object'];
                $propertyName = $propertyMeta['property'];
                $omlProperties[$objectName][$propertyName] = $propertyMeta;
            }

            $this->omlPropertyList = $omlProperties;
        }

        return $this->omlPropertyList;
    }
    
    /**
     * Prepare collection of all metaObjectType.
     * 
     * @param ServiceQueI $serviceQue
     * @return type
     */
    private function getMetaList(ServiceQueI $serviceQue)
    {
        $omlObjectList = $this->getOmlObjectList($serviceQue);
        $metaObjectList = array();
        foreach ($omlObjectList as $object) {
            $objectName = $object['name'];
            if (preg_match("/^(meta)/", $objectName)) {
                $metaObjectList[] = $objectName;
            }
        }
        
        return $metaObjectList;
    }
    
    /**
     * 
     * @param type $objectType
     * @param type $objectDataArray
     * @param array $flattenDataArray
     * @param ServiceQueI $serviceQue
     * @return type
     */
    private function flattenData($objectType, $objectDataArray, &$flattenDataArray, ServiceQueI $serviceQue) 
    {
        $childObjectArray = $this->getChildObject($objectType, $serviceQue);
        foreach ($objectDataArray as $objectData) {
            foreach ($childObjectArray as $childObjectType) {
                if (isset($objectData[$childObjectType])) {
                    $this->flattenData($childObjectType, $objectData[$childObjectType], $flattenDataArray, $serviceQue);
                }
            }
        }
        
        $this->removeChildData($objectType, $objectDataArray, $serviceQue);
        foreach ($objectDataArray as $data) {
            $flattenDataArray[$objectType][] = $data;
        }
        
        
        return;
    }
    /**
     * This function converts objectDataCollection to objectArray.
     * 
     * @param type $objectTypeDataCollection
     * @return type
     */
    private function convertToObjectArray($objectTypeDataCollection)
    {
        $objectArray = array();
        foreach ($objectTypeDataCollection as $objectType => $objectTypeData) {
            foreach ($objectTypeData as $object) {
                $objectId = $object['id'];
                $objectArray[$objectId] = $object;
            }
        }
        
        return $objectArray;
    }

    /**
     * This function sort objects in topologically.
     * 
     * @param ServiceQueI $serviceQue
     * @return type
     * @throws SynapExceptions
     */
    private function getSortedObjectList(&$objectDataArray, ServiceQueI $serviceQue) 
    {
        // creating graph
        $graph = new \SynapEssentials\OMLSyncronizerBundle\utilities\Structures_Graph();
        $nodeArray = array();
        foreach ($objectDataArray as $objectId => $objectData) {
            $nodeArray[$objectId] = new Node();
            $nodeArray[$objectId]->setData($objectId);
            $graph->addNode($nodeArray[$objectId]);
        }

        // connects node each other
        foreach ($objectDataArray as $objectId => $objectData) {
            $objectType = OMLObjectUtility::getObjectType($objectId);
            $objectMetaData = OMLObjectUtility::getObjectMetaData($objectType);
            
            foreach ($objectData as $propertyName => $propertyValue) {
                $propertyMetaData = $objectMetaData->getPropertyByName($propertyName);
                $dataType = $propertyMetaData->getPropertyDataType();
                // only refer property value is playing role in connect nodes
                if (strtolower($dataType) != 'refer') {
                    continue;
                }
                
                // Handle cyclic issue by passing optional refer properties.
                $isRequired = $propertyMetaData->isRequired();
                if (!$isRequired && $propertyName != 'organizationId'
                        && $propertyName != 'synapUserId' && !empty($propertyValue)) {
                    $this->updateRequest[$objectId][$propertyName] = $propertyValue;
                    unset($objectDataArray[$objectId][$propertyName]);
                    continue;
                }
                
                // In case of allowMultiple $propertyValue comes in array format.
                $propertyValueArray = (array) $propertyValue;
                
                foreach ($propertyValueArray as $val) {
                    if (is_scalar($val) && isset($nodeArray[$val])) {
                        if ($objectId != $val) {
                            $nodeArray[$objectId]->connectTo($nodeArray[$val]);
                        }
                    }
                }
            }
        }

        // sort graph
        $sorter = new TopologicalSorter();
        $data = $sorter->sort($graph);
        $sortedObjectList = array();
        for ($i = 0; $i < count($data); $i++) {
            for ($j = 0; $j < count($data[$i]); $j++) {
                $sortedObjectList[] = $data[$i][$j]->getData();
            }
        }
        
        $sortedObjList = array_reverse($sortedObjectList, true);
        $sortedObjList = array_values($sortedObjList);
        
        return $sortedObjList;
    }
    
    /**
     * This function returns child objects in hierarchal fashion.
     * 
     * @param type $parentObjectType
     * @param type $excludeObjectList
     * @param ServiceQueI $serviceQue
     * @return array
     */
    private function getChildHierarchy($parentObjectType, $excludeObjectList, ServiceQueI $serviceQue) 
    {
        $familyHierarchy = array();
        $childObjects = $this->getChildObject($parentObjectType, $serviceQue);
        foreach ($childObjects as $object) {
            // exclude from Hierarchy
            if (in_array($object, $excludeObjectList)) {
                continue;
            }

            $subHierarchy = $this->getChildHierarchy($object, $excludeObjectList, $serviceQue);
            if (count($subHierarchy) == 1 && !is_array(current($familyHierarchy))) {
                $familyHierarchy[$object] = array();
            } else {
                $familyHierarchy[$object] = $subHierarchy;
            }
        }

        return $familyHierarchy;
    }

    /**
     * This function returns child objectTypes.
     * 
     * @param type $parentObjectType
     * @param ServiceQueI $serviceQue
     * @return type
     */
    private function getChildObject($parentObjectType, ServiceQueI $serviceQue) 
    {
        $childObject = array();
        $objectMetaDataList = $this->getOmlObjectList($serviceQue);
        foreach ($objectMetaDataList as $object) {
            $parentName = $object['parent'];
            $objectName = $object['name'];
            if ($parentObjectType == $parentName) {
                $childObject[] = $objectName;
            }
        }

        return $childObject;
    }
    
    /**
     * This function remove childData from mainObject passed as objectData.
     * 
     * @param type $objectType
     * @param type $objectData
     * @param ServiceQueI $serviceQue
     * @return type
     */
    private function removeChildData($objectType, &$objectData, ServiceQueI $serviceQue) 
    {
        $childObjects = $this->getChildObject($objectType, $serviceQue);
        foreach ($objectData as &$data) {
            foreach ($childObjects as $childObjType) {
                if (isset($data[$childObjType])) {
                    unset($data[$childObjType]);
                }
            }
        }

        return;
    }
    
    /**
     * This function combine $requestParamArray1 and $requestParamArray2.
     * 
     * @param array $requestParamArray1
     * @param array $requestParamArray2
     * @return array
     */
    private function combineRequestParams(&$combinedRequestArray, $requestParamArray2)
    {
        foreach ($requestParamArray2 as $key => $requestParam) {
            $combinedRequestArray[$key] = $requestParam;
        }
        
        return;
    }

    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
        return true;
    }

    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    public function postExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    public function postExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }
    
}
