<?php

namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationProfile;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;

/**
 * BL class for preExecute of organizationProfile.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;

class PreExecuteM implements PreExecuteI
{

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        // Check any permission isn't a systemAssigned
        // permissions which are systemAssigned is not allowed to assign manually.
        $systemAssignedPermissions = $this->getSystemAssignedPermissions($data, $serviceQue);
        if (!empty($systemAssignedPermissions)) {
            $permissionCsv = implode(', ', $systemAssignedPermissions);
            throw new SynapExceptions(SynapExceptionConstants::SYSTEM_ASSIGNED_PERMISSION_CANT_USE,
                   403, array('permissions' => $permissionCsv));
        }
        
        // append cascaded permissions with data.
        $this->setCascadedPermissions($data, $serviceQue);
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        // fetching profile detail data from db
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData();
        $searchKey[0]['outKey'] = 'response';
        $profileResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objDataInDB = $profileResp['data']['response'];
        
        if (!empty($objDataInDB) && !$objDataInDB['allowDelete']) {
            throw new SynapExceptions(SynapExceptionConstants::CAN_NOT_DELETE_REQ,
                  403,  array('object' => 'organizationProfile'));
        }
    }
    
    /**
     * Function will perform some actions after execute get
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue)
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
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        // Check any permission isn't a systemAssigned
        // permissions which are systemAssigned is not allowed to assign manually.
        $systemAssignedPermissions = $this->getSystemAssignedPermissions($data, $serviceQue);
        if (!empty($systemAssignedPermissions)) {
            $permissionCsv = implode(', ', $systemAssignedPermissions);
            throw new SynapExceptions(SynapExceptionConstants::SYSTEM_ASSIGNED_PERMISSION_CANT_USE,
                  403,  array('permissions' => $permissionCsv));
        }
        
        // append cascaded permissions with data.
        $this->setCascadedPermissions($data, $serviceQue);
        return true;
    }
    
    /**
     * This function returns cascaded permissions associated with given permissions.
     * 
     * @param Array $permissions
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     * @return array
     */
    private function getCascadedPermissions($permissions, ServiceQueI $serviceQue) 
    {
        $cascadedPermissions = array();
        
        $requestParam = array(array(
            'objectId'      => $permissions,
            'outKey'        => 'response',
            'sendNullKey'   => 1,
            'requiredCount' => 0,
            'requiredAdditionalInfo'   => 0,
            'requiredCalculatedFields' => 0,
            
        ));
        
        $response = $serviceQue->executeQue("ws_oml_read", $requestParam);
        
        if ($response['status']['success'] != true) { // Unable to read metaPermission
            throw new SynapExceptions(SynapExceptionConstants::INTERNAL_SERVER_ERROR,500);
        }
        
        $resultArray = $response['data']['response'];
        
        foreach ($resultArray as $result) {
//            $permissions = (array)$result['cascadedPermission'];
            $permissions = isset($result['cascadedPermission']) ? (array)$result['cascadedPermission'] : array();
            foreach ($permissions as $permission) {
                $cascadedPermissions[] = $permission;
            }
        }
        
        return $cascadedPermissions;
    }
    
    /**
     * This function sets cascaded permissions.
     * get metaPermissions and make list of cascaded permissions
     * append these permissions with data->permissions
     * @param array $data request data
     * @param ServiceQueI $serviceQue
     * @return void
     */
    private function setCascadedPermissions($data, ServiceQueI $serviceQue)
    {
        $properties = $data->getData('properties');
        $requestedPermissions = $properties['permission'];
        $cascadedPermissions = $this->getCascadedPermissions($requestedPermissions, $serviceQue);
        
        foreach ($cascadedPermissions as $permission)  {
            if (!in_array($permission, $requestedPermissions)) {
                $requestedPermissions[] = $permission;
            }
        }
        
        $data->setData(array('permission' => $requestedPermissions), 'properties');
        
        return;
    }
    
    /**
     * This function returns permissions which are systemAssigned and send with request.
     * 
     * @param  $data
     * @param ServiceQueI $serviceQue
     * @return array
     * @throws SynapExceptions
     */
    private function getSystemAssignedPermissions($data, ServiceQueI $serviceQue)
    {
        $systemAssignedPermissions = array();
        $properties = $data->getData('properties');
        $requestedPermissions = $properties['permission'];
        if (empty($requestedPermissions)) {
            return $systemAssignedPermissions;
        }
        
        $requestParam = array(array(
            'type'          => 'metaPermission',
            'outKey'        => 'response',
            'sendNullKey'   => 1,
            'requiredCount' => 0,
            'requiredAdditionalInfo'   => 0,
            'requiredCalculatedFields' => 0,
            'conditions' => array(
                array('id' => array('in' => $requestedPermissions)),
                array('systemAssignedOnly' => true)
            )
        ));
        
        $response = $serviceQue->executeQue("ws_oml_read", $requestParam);
        
        if ($response['status']['success'] != true) { // Unable to read metaPermission
            throw new SynapExceptions(SynapExceptionConstants::INTERNAL_SERVER_ERROR,500);
        }
        
        $resultArray = $response['data']['response'];
        
        foreach ($resultArray as $result) {
            $systemAssignedPermissions[] = $result['id'];
        }
        
        return $systemAssignedPermissions;
    }

}