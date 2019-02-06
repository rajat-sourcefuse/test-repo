<?php

namespace SynapEssentials\OMLBLBundle\Implementations\SynapUser;

use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;

/**
 * BL class for postExecute of SynapUser.
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
 */
class PostExecuteM implements PostExecuteI {

    /**
     * This function will create a user token and send registration email
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue) {
        // if email sent only then token will be created and email will be sent
        if (!empty($data->getData('properties')['email'])) {
            // create user token
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
            //$userData['token'] = $resp['data']['id'];
            $userData['registerPageUrl'] = $obj->getParameter("uiUrl").'/register?token='.$resp['data']['id'];                        
            $email->registrationEmail($userData);
        }

        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteDelete($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue) {
        // if email sent only then token will be created and email will be sent
        // check for synap user id is set and not empty
        if (!empty($data->getData('properties')['email']) && isset($data->getData('conditions')['object'][0]) && !empty($data->getData('conditions')['object'][0])) {

            //synap user id
            $synapuserid = $data->getData('conditions')['object'][0];

            $obj = Configurator::getInstance();
            // create user token
            $objectData['parent'] = $synapuserid;
            $objectData['properties']['tokenType'] = 'metaTokenType:register';
            // Set token expiry time variable            
            $tokenExpiryTime = time() + ($obj->getParameter("registration_token_expiry") * 24 * 60 * 60);
            $objectData['properties']['tokenExpiryTime'] = $tokenExpiryTime;
            $objectData['objectType'] = 'synapUserToken';
            // check the the synap token exist the synap user id
            $searchKey = [];
            $searchKey[0]['type'] = 'synapUserToken';
            $searchKey[0]['conditions'][] = ['synapUserId' => $synapuserid];
            $searchKey[0]['outKey'] = 'response';

            $synapResp = $serviceQue->executeQue("ws_oml_read", $searchKey);

            /* AP-1805 */
            $sendMail = (isset($data->getData('extra')['sendemail']) && !$data->getData('extra')['sendemail']) ? FALSE : TRUE;
            
            $synapservicetype = 'ws_oml_create';
            if (!empty($synapResp['data']['response'])) {
                $synapservicetype = 'ws_oml_update';
                $objectData['conditions']['object'][0] = $synapResp['data']['response'][0]['id'];
                $userData['token'] = $synapResp['data']['response'][0]['id'];
            }
            // Send registration email
            if ($sendMail) {
                $resp = $serviceQue->executeQue($synapservicetype, $objectData);                
                $serviceContainer = $obj->getServiceContainer();
                $email = $serviceContainer->get('emailUtil');
                $userData['email'] = $data->getData('properties')['email'];
                $userData['name'] = $data->getData('properties')['firstName'] . ' ' . $data->getData('properties')['lastName'];
                if ($synapservicetype == 'ws_oml_create') {
                    $userData['token'] = $resp['data']['id'];
                }

                $userData['orgName'] = "organizationName";
                $userData['orgName'] = $obj->getParameter("organizationName");
                $email->registrationEmail($userData);
            }
        }
        return true;
    }

    /**
     * Function will perform some actions after execute get
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteGet($data, ServiceQueI $serviceQue) {
        return true;
    }

    /**
     * Function will perform some actions after execute view
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteView($data, ServiceQueI $serviceQue) {
        return true;
    }

}
