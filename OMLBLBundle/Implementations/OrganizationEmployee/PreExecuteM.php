<?php
namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationEmployee;
/**
 * BL class for preExecute of organizationEmployee.
 *
 * @author Manish Kumar <manish.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use \SynapEssentials\Utilities\SessionUtility;
use \SynapEssentials\AccessControlBundle\Interfaces\SessionConstants;

class PreExecuteM implements PreExecuteI
{

    const SYNAP_USER_CREATE = 'CREATE';
    const SYNAP_USER_UPDATE = 'UPDATE';
    const USER_TYPE_SYSTEM = 'metaUserType:system';
    const OBJECT_TYPE = 'organizationEmployee';
    const AUTH_USER = 'metaUserType:admin';
    /**
     * This function checks if a synapUser already exist. 
     * If yes then refer the same to organizationEmployee or create a new synapUser and then refer
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
       // $this->validateAdminUser(self::OBJECT_TYPE, 'add');
        // Check if create new syanpuser is true        
        $this->createSynapUser($data, $serviceQue, $type = self::SYNAP_USER_CREATE);
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
       // $this->validateAdminUser(self::OBJECT_TYPE, 'delete');
        $orgEmpId = $data->getData();
        $this->restrictSystemUserOperation($serviceQue, $orgEmpId, 'delete');
        //delete entry from synap user as well
        $this->deleteSynapUser($data, $serviceQue);
        return true;
    }

    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue)
    {
        // BL for not to fetch system type user
        // $conditions = !empty($data->getData('conditions')) ? $data->getData('conditions') : array();
        // $removeSystemUserCondition = array('userType' => array('NE' => self::USER_TYPE_SYSTEM));
        // array_push($conditions, $removeSystemUserCondition);
        // $data->setData($conditions, 'conditions');
        return true;
    }

    /**
     * Function will validate data before execute view
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
     * This function validates if ssn has been changed. if yes then untie 
     * existing synap user ID and create a new user
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        //$this->validateAdminUser(self::OBJECT_TYPE, 'update');
        $this->restrictSystemUserOperation($serviceQue, $data->getData('conditions')['object'][0], 'update');
        
        // Check if create new syanpuser is true        
        $this->createSynapUser($data, $serviceQue, $type = self::SYNAP_USER_UPDATE);

        if (isset($data->getData('properties')['status'])) {
            $searchKey = array(array(
                'objectId' => $data->getData('conditions')['object'][0],
                'outKey' => 'response'
            ));
            $respEmp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            if (!empty($respEmp['data']['response']['synapUserId'])) {
                $userId = $respEmp['data']['response']['synapUserId'];
                $searchKey = [];
                $searchKey[0]['objectId'] = $userId;
                $searchKey[0]['outKey'] = 'response';
                $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
                if (!empty($resp['data']['response']['username'])) {
                    $username = $resp['data']['response']['username'];
                    $status = $data->getData('properties')['status'];
                    $this->changeSynapUserStatus($userId, $status, $serviceQue);
                }
            }
        }
        return true;
    }

    /**
     * This function is used to change synap userstatus 
     * @param type $userId
     * @param type $status
     * @param type $serviceQue     
     * @throws SynapExceptions
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     */
    private function changeSynapUserStatus($userId, $status, $serviceQue)
    {
        $objectData = array();
        $objectData['conditions']['object'][0] = $userId;
        if ($status == 'metaEmployeeStatus:active') {
            $objectData['properties']['isActive'] = true;
        } else if ($status == 'metaEmployeeStatus:inactive') {
            $objectData['properties']['isActive'] = false;
        }
        $resp = $serviceQue->executeQue('ws_oml_update', $objectData);
        return;
    }


    /**
     * This function is used to create synap user and update the infomation 
     * @param type $data
     * @param type $serviceQue
     * @param type $type
     * @return type
     * @throws SynapExceptions
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     */
    private function createSynapUser($data, $serviceQue, $type)
    {
        $orgEmployeeData = [];
        // create the employee      

        if ($type == self::SYNAP_USER_CREATE) {
            $servicetype = 'ws_oml_create';
            $email = isset($data->getData('properties')['workEmail']) && !empty($data->getData('properties')['workEmail']) ? $data->getData('properties')['workEmail'] : null;
            $firstName = $data->getData('properties')['firstName'];
            $lastName = $data->getData('properties')['lastName'];
          
            $status = $data->getData('properties')['status'];
        } else if ($type == self::SYNAP_USER_UPDATE) {
            $servicetype = 'ws_oml_update';
            $orgEmployeeId = $data->getData('conditions')['object'][0];
            // fetch employee data from db
            $orgEmployeeResp = $this->fetchEmployeeData($orgEmployeeId, $serviceQue);

            $orgEmployeeData = $orgEmployeeResp['data']['response'];
            // Set value for email
            if (isset($data->getData('properties')['workEmail']) && !empty($data->getData('properties')['workEmail'])) {
                $email = $data->getData('properties')['workEmail'];
            } else {
                $email = isset($orgEmployeeData['workEmail']) && !empty($orgEmployeeData['workEmail']) ? $orgEmployeeData['workEmail'] : '';
            }
            // Set value for firstname
            if (isset($data->getData('properties')['firstName']) && !empty($data->getData('properties')['firstName'])) {
                $firstName = $data->getData('properties')['firstName'];
            } else {
                $firstName = isset($orgEmployeeData['firstName']) && !empty($orgEmployeeData['firstName']) ? $orgEmployeeData['firstName'] : '';
            }
            // Set value for lastname
            if (isset($data->getData('properties')['lastName']) && !empty($data->getData('properties')['lastName'])) {
                $lastName = $data->getData('properties')['lastName'];
            } else {
                $lastName = isset($orgEmployeeData['lastName']) && !empty($orgEmployeeData['lastName']) ? $orgEmployeeData['lastName'] : '';
            }



           
            
            // Set value for lastname
            if (isset($data->getData('properties')['status']) && !empty($data->getData('properties')['status'])) {
                $status = $data->getData('properties')['status'];
            } else {
                $status = isset($orgEmployeeData['stauts']) && !empty($orgEmployeeData['status']) ? $orgEmployeeData['status'] : '';
            }
        }
        $objectData = array();
        // check the email is not empty
        if (!empty($email)) {
            $email = strtolower($email);
            // fetching employee data from email by from db
            $uniqueEmail = false;
            //fetch employee data based on email
            $emailAddressData = $this->fetchDataByEmail($email, $orgEmployeeData, $objectData, $serviceQue);

            $emailAddressDataCount = count($emailAddressData);
            // check for create the Employee
            if ($emailAddressDataCount == 0) {
                $uniqueEmail = true;
            }
            // check the varible @ $uniqueEmail is value for true or false
            if ($uniqueEmail) {
                $objectData['properties']['username'] = $email;
                $objectData['properties']['email'] = $email;
            } else {
                throw new SynapExceptions(SynapExceptionConstants::EMAIL_ALREADY_EXISTS, 409);
            }
            $objectData['properties']['firstName'] = $firstName;
            $objectData['properties']['lastName'] = $lastName;
           
            $userType = $data->getData('userType');
            if (isset($userType) && !empty($userType) &&($userType)) {
                
                $objectData['properties']['userType'] = $userType;
            }
           
           
            if ($status == 'metaEmployeeStatus:active') {
                $objectData['properties']['isActive'] = true;
            } else if ($status == 'metaEmployeeStatus:inactive') {
                $objectData['properties']['isActive'] = false;
            }
            $objectData['objectType'] = 'synapUser';
            $data->setData(array('properties' => array('workEmail' => $email)));
            $resp = $serviceQue->executeQue($servicetype, $objectData);
            if ($servicetype == 'ws_oml_create') {
                $data->setData(array('synapUserId' => $resp['data']['id'], 'createNewSynapUser' => '0'), 'properties');
            }
        } else {
            throw new SynapExceptions(SynapExceptionConstants::CREATE_SYNAPUSER_WORKEMAIL_REQUIRED, 400);
        }
        return;
    }

    //function will fetch employee data based om employee id
    private function fetchEmployeeData($orgEmployeeId, $serviceQue)
    {
        $searchKey = [];
        $searchKey[0]['objectId'] = $orgEmployeeId;
        $searchKey[0]['outKey'] = 'response';
        $orgEmployeeResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        return $orgEmployeeResp;
    }

    /*
     * Function will fetch data by email
     */

    private function fetchDataByEmail($email, $orgEmployeeData, &$objectData, $serviceQue)
    {
        $searchKey = [];
        $searchKey[0]['type'] = 'organizationEmployee';
        $searchKey[0]['conditions'][] = ['workEmail' => ["ILIKE" => $email]];
        $searchKey[0]['outKey'] = 'response';
        // if Employee edit case already exist the synap user
        if (isset($orgEmployeeData['synapUserId']) && !empty($orgEmployeeData['synapUserId'])) {
            // check the Employee email is not inclued the duplicate email address
            $searchKey[0]['conditions'][] = array('synapUserId' => array('NOTIN' => array($orgEmployeeData['synapUserId'])));
            $objectData['conditions']['object'][0] = $orgEmployeeData['synapUserId'];
        }
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $emailAddressData = $aResp['data']['response'];
        return $emailAddressData;
    }

    /**
     * Delete user entry from synap table as well
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    private function deleteSynapUser($data, $serviceQue)
    {
        $employeeId = $data->getData();

        //fetch employee data
        $searchKey = [];
        $searchKey[0]['objectId'] = $employeeId;
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        //get synap user id and delete user
        $synapUserId = isset($resp['data']['response']['synapUserId']) ? $resp['data']['response']['synapUserId'] : '';
        if ($synapUserId) {
            $deleteKey = [$synapUserId];
            $serviceQue->executeQue("ws_oml_delete", $deleteKey);
        }
        return;
    }

  
    
    /**
     * Restrict system type user update and delete operation
     * @param ServiceQueI $serviceQue
     * @param id $orgEmpId
     * @param action $action
     * @return boolean
     */
    private function restrictSystemUserOperation($serviceQue, $orgEmpId, $action)
    {
        $searchData = [];
        $searchData["type"] = self::OBJECT_TYPE;
        $searchData["outKey"] = 'response';
        $searchData["conditions"][] = array('id' => $orgEmpId);
        $objectResp = $serviceQue->executeQue('ws_oml_search', $searchData);
        $objectData = reset($objectResp["data"]['response']);
        if (!empty($objectData)) {
            if ($objectData['userType'] == self::USER_TYPE_SYSTEM) {

                throw new SynapExceptions(SynapExceptionConstants::AUTH_ACCESS_DENIED, 400, array('object' => self::OBJECT_TYPE, 'action' => $action));
            }
        }
        return true;
    }

}
