<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Organization;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

/**
 * BL class for postExecute of organization.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;

class PostExecuteM implements PostExecuteI
{
    private $defaultProfile = 'in_built';
    
    /**
     * Function will create division, profile and make assignemnts for all this 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    { 
        //create organizationDivision
        $divisionId = $this->createDefaultDivision($data,$serviceQue);
        
        //create organizationEmployeeDivision
        $orgEmployeeId = $data->getData('organizationEmployeeId');
        if(!empty($orgEmployeeId)){
            $employeeDivisionData = array('employeeId'=>$orgEmployeeId,'divisionId'=>$divisionId);
            $this->assignOrganizationEmployeeDivision($employeeDivisionData,$serviceQue);
        }
        
        //create organizationProfile
        $profileId = $this->createOrganizationProfile($data,$serviceQue);
        
        //update organizationEmployee for profile
        $updateData = array('employeeId'=>$orgEmployeeId,'profileId'=>$profileId);
        $this->updateOrganizationEmployeeProfile($updateData, $serviceQue);
        return true;
    }

    /**
     * Function will perform some actions after execute delete
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
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
     * Function will perform some actions after execute update
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }
    
    /**
     * Function will create a default division for organization
     * @param type $data
     * @param $serviceQue
     * @return divisionId
     */
    public function createDefaultDivision($data,$serviceQue)
    {
        //create default division input array 
        $organizationId = $data->getData('id');        
        $orgDivisionInputData = array(
            'parent' => $organizationId,
            'objectType' => 'organizationDivision',
            'properties' => array(
                'name' => 'D0',
                'allowDelete'=>0//to mark division as non-deletable
            )
        );       
        
        $divisionResp = $serviceQue->executeQue('ws_oml_create', $orgDivisionInputData);
        $divisionId = $divisionResp['data']['id'];        
        return  $divisionId;
    }
    
    /** 
     * Function will make an assignment for employee and division
     * @param $data
     * @param $serviceQue
     * @return employeeDivisionId
     */
    public function assignOrganizationEmployeeDivision($data, $serviceQue)
    {
        //create employeedivision input array 
        $employeeId = $data['employeeId'];
        $divisionId = $data['divisionId'];
        $employeeDivisionInputData = array(
            'parent' => $employeeId,
            'objectType' => 'organizationEmployeeDivision',
            'properties' => array(
                'organizationDivisionId'=>$divisionId
            )
        );
        $employeeDivisionResp = $serviceQue->executeQue('ws_oml_create', $employeeDivisionInputData);
        $employeeDivisionId = $employeeDivisionResp['data']['id'];
        return  $employeeDivisionId;
    }
    
    /**
     * Function will create default profile 
     * @param type $data
     * @param $serviceQue
     * @return profileId
     */
    public function createOrganizationProfile($data, $serviceQue)
    {
        //get organizationAdmin profile permissions  
        $permissions = $this->getOrganizationAdminProfilePermissions($serviceQue);
        
        //create profile input array 
        $organizationId = $data->getData('id');
        $orgProfileInputData = array(
            'parent' => $organizationId,
            'objectType' => 'organizationProfile',
            'properties' => array(
                'name' => $this->defaultProfile,
                'allowDelete'=>0,
                'permission' => $permissions
            )
        );
        $profileResp = $serviceQue->executeQue('ws_oml_create', $orgProfileInputData);
        $profileId = isset($profileResp['data']['id'])?$profileResp['data']['id']:'';
        return  $profileId;
    }
    
    /**
     * Function will get all the permissions for organizationAdmin profile
     * @param $serviceQue
     * @return permissions
     */
    public function getOrganizationAdminProfilePermissions($serviceQue)
    {
        $permissions = array();
        
        //create search key for oraganizationAdmin profile permissions
        $searchKey = [];
        $searchKey[0]['type'] = 'metaSynapProfile';
        $searchKey[0]['outKey'] = 'response';
        /* condition to read all permissions id for organizationAdmin */
        $searchKey[0]['conditions'][] = array('profile' => 'organizationAdmin'); 
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $permissionData = isset($resp['data']['response'])?$resp['data']['response']:'';
        
        if(!empty($permissionData)){
            foreach($permissionData as $detail){
                $permissions[] = $detail['permission'];
            }
        }
        return $permissions;
    }
    
    /**
     * Function will update the employee with given permissions
     * @param type $data
     * @param $serviceQue
     * @param updatedResp
     */
    public function updateOrganizationEmployeeProfile($dataToUpate, $serviceQue){
        $updateObjs['conditions']['object'][0] = $dataToUpate['employeeId'];
        $updateObjs['properties']['profile'] = array($dataToUpate['profileId']);        
        $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
        return $upResp;
    }

}
