<?php

namespace SynapEssentials\OMLBLBundle\Implementations\SynapUser;

/**
 * BL class for preExecute of SynapUser.
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OpenamBundle\Openam\OpenamHandler;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;

class PreExecuteM implements PreExecuteI {

    /**
     * This function validates data before execute create
     * 
     * @param OMLServiceData $data
     * @param ServiceQueI $serviceQue
     * @return vodi
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {
        /**
         * Author Kuldeep Singh, Date: 09-08-2017 11:30AM, JIRA Id: AP-728
         * validation check for unique email id
         * 
         */
        if (isset($data->getData('properties')['email']) && !empty($data->getData('properties')['email'])) {
            $searchKey = [];
            $searchKey[0]['type'] = 'synapUser';
            $searchKey[0]['conditions'][] = ['email' => ["ILIKE" => $data->getData('properties')['email']]];
            $searchKey[0]['outKey'] = 'response';
            $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $emailaddressDataInDB = $aResp['data']['response'];
            $emailaddressDataInDBcount = count($emailaddressDataInDB);
            if ($emailaddressDataInDBcount > 0) {
                throw new SynapExceptions(SynapExceptionConstants::EMAIL_ALREADY_EXISTS,400);
            }
        }
        return true;
    }

    /**
     * Function will validate data before execute get
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will validate data before execute view
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * This function will mark user inactive in OpenAM when deleted from Synap
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
        $userId = $data->getData();
        $searchKey = [];
        $searchKey[0]['objectId'] = $userId;
        $searchKey[0]['outKey'] = 'response';
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        $openamHandler = OpenamHandler::getInstance();
        $username = $resp['data']['response']['email'];
        $resp = $openamHandler->markUserInactive($username);
        return true;
    }

    /**
     * This function validates data before execute update
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return void
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
       
        // Fetching synapuser data for  from db
        $synapUserId = $data->getData('conditions')['object'][0];
        $searchKey = [];
        $searchKey[0]['objectId'] = $synapUserId;
        $searchKey[0]['outKey'] = 'response';
        $synapUserResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $synapUserInDB = $synapUserResp['data']['response'];

        /* AP-1780 PIN should be allowed to entered if it's empty in DB otherwise not allowed 
         * But user can change signature if pin already exists in DB 
         * Simplified code for AP-1800
         */

        $properties = $data->getData('properties');

        if(!empty($properties['pin']) || !empty($properties['signature'])) {
            if(empty($properties['pin']) && !empty($properties['signature'])) { 
                throw new SynapExceptions(SynapExceptionConstants::SIGNATURE_NOT_ALLOWED_WITHOUT_PIN,400);
            } else if(!empty($properties['pin']) && empty($properties['signature'])) {
                throw new SynapExceptions(SynapExceptionConstants::PIN_NOT_ALLOWED_WITHOUT_SIGNATURE,400);
            } else if(!empty($synapUserInDB['pin'])) {
                throw new SynapExceptions(SynapExceptionConstants::PIN_ALREADY_CREATED,400);
            }
        }

        /**
         * Author Kuldeep Singh, Date: 09-08-2017 11:30AM, JIRA Id: AP-728
         * validation check for unique email id
         * 
         */
    
        if (isset($data->getData('properties')['email']) && !empty($data->getData('properties')['email'])) {
            $emailId = $data->getData('properties')['email'];
            

            
            $userSearchKey = [];
            $userSearchKey[0]['type'] = 'synapUser';
            $userSearchKey[0]['conditions'][] = ['email' => ["ILIKE" => $emailId]];
            $userSearchKey[0]['conditions'][] = array('id' => array('NOTIN' => array($synapUserId)));
            $userSearchKey[0]['outKey'] = 'response';
            $aResp = $serviceQue->executeQue("ws_oml_read", $userSearchKey);
            $emailaddressDataInDB = $aResp['data']['response'];
            $emailaddressDataInDBcount = count($emailaddressDataInDB);
            if ($emailaddressDataInDBcount > 0) {
                throw new SynapExceptions(SynapExceptionConstants::EMAIL_ALREADY_EXISTS,400);
            }

            $sendMail = TRUE;
            if (isset($synapUserInDB) && count($synapUserInDB) > 0) {
                if (strtolower(@$synapUserInDB['email']) == strtolower(@$data->getData('properties')['email'])) {
                    $sendMail = FALSE;
                }
            }
            $data->setData(array('extra' => array('sendemail' => $sendMail)));
        }
        /* end of validation check */

        // Check if create new syanpuser is true
        if (isset($data->getData('properties')['resendEmail']) && ($data->getData('properties')['resendEmail'])) {
            
            if (empty($synapUserInDB['email'])) {
                throw new SynapExceptions(SynapExceptionConstants::EMAIL_NOT_SET_FOR_USER,400);
            }

            $obj = Configurator::getInstance();
            $objectData['parent'] = $synapUserId;
            $objectData['properties']['tokenType'] = 'metaTokenType:register';
            // Set token expiry time variable            
            $tokenExpiryTime = time() + ($obj->getParameter("registration_token_expiry") * 24 * 60 * 60);
            $objectData['properties']['tokenExpiryTime'] = $tokenExpiryTime;
            $objectData['objectType'] = 'synapUserToken';
            $resp = $serviceQue->executeQue('ws_oml_create', $objectData);
            $data->setData(array('resendEmail' => '0'), 'properties');
            // Send registration email            
            $serviceContainer = $obj->getServiceContainer();
            $email = $serviceContainer->get('emailUtil');
            $userData['email'] = $synapUserInDB['email'];
            $userData['name'] = $synapUserInDB['firstName'] . ' ' . $synapUserInDB['lastName'];
            $userData['orgName'] = $obj->getParameter("organizationName");
            $userData['token'] = $resp['data']['id'];

            $email->registrationEmail($userData);
        }
        if ((isset($data->getData('properties')['status']) && (!isset($data->getData('properties')['isLocked'])) ) && ($data->getData('properties')['status'] == false)) {
            
            //mark inactive
            $openamHandler = OpenamHandler::getInstance();
            $username = $synapUserInDB['email'];
            $resp = $openamHandler->markUserInactive($username);
        } elseif ((isset($data->getData('properties')['status']) && (!isset($data->getData('properties')['isLocked'])) ) && ($data->getData('properties')['status'] == true)) {
            
            //mark active
            $openamHandler = OpenamHandler::getInstance();
            $username = $synapUserInDB['email'];
            $resp = $openamHandler->markUserActive($username);
        }
        return true;
    }

    private function createToken() {
        // if email sent only then token will be created and email will be sent
        if (!empty($data->getData('properties')['email'])) {

            $obj = Configurator::getInstance();
            // create user token
            $objectData['parent'] = $data->getData('id');
            $objectData['properties']['tokenType'] = 'metaTokenType:register';
            // Set token expiry time variable            
            $tokenExpiryTime = time() + ($obj->getParameter("registration_token_expiry") * 24 * 60 * 60);
            $objectData['properties']['tokenExpiryTime'] = $tokenExpiryTime;
            $objectData['objectType'] = 'synapUserToken';
            $resp = $serviceQue->executeQue('ws_oml_create', $objectData);

            // Send registration email            
            $serviceContainer = $obj->getServiceContainer();
            $email = $serviceContainer->get('emailUtil');
            $userData['email'] = $data->getData('properties')['email'];
            $userData['name'] = $data->getData('properties')['firstName'] . ' ' . $data->getData('properties')['lastName'];
            $userData['token'] = $resp['data']['id'];
            $email->registrationEmail($userData);
        }
    }

}
