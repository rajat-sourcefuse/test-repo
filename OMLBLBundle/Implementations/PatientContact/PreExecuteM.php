<?php

namespace SynapEssentials\OMLBLBundle\Implementations\PatientContact;

/**
 * PreExecuteM is for handling the business logic in pre execution of services
 *
 * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;

class PreExecuteM implements PreExecuteI {

    const PATIENT_CONTACT_ADDRESS = 'patientContactAddress';
    const BILLING = 'metaAddressType:billing';
    const CONTACT_FAMILY = 'metaContactType:family';
    const CONTACT_OTHER = 'metaContactType:other';
    const SYNAP_USER_CREATE = 'CREATE';
    const SYNAP_USER_UPDATE = 'UPDATE';

    /**
     * function will validate data before execute create
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @throws SynapExceptions
     */
    public function preExecuteCreate($data, ServiceQueI $serviceQue) {

        // if contact type family and other then relationship required
        if ($data->getData('properties')['contactType'] == self::CONTACT_FAMILY ||
                $data->getData('properties')['contactType'] == self::CONTACT_OTHER) {
            if (empty($data->getData('properties')['relationship'])) {
                throw new SynapExceptions(SynapExceptionConstants::RELATIONSHIP_SHOULD_COME,400);
            }
        } else {
            if (!empty($data->getData('properties')['relationship'])) {
                // unset relationship data
                $updateArr = array('relationship');
                $data->unSetData($updateArr, 'properties');
            }
        }

        // Check if create new syanpuser is true
        if (isset($data->getData('properties')['createNewSynapUser']) && ($data->getData('properties')['createNewSynapUser'])) {
            // for creating synapUser
            $this->synapUserCreate($data, $serviceQue, $type = self::SYNAP_USER_CREATE);
        }
        // for address
        $this->addressCreate($data);

        // for telephone
        $this->telephoneCreate($data);

        // for patient contact only one can be primary
        $patientId = $data->getData('parent');
        $this->primaryContact($data, $patientId, $serviceQue);
    }

    /**
     * BL for only one contact should be primary
     * 
     * @param type $data
     * @param type $patientId
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function primaryContact($data, $patientId, $serviceQue) {
        // fetching contact data for a patient from db
        $searchKey = [];
        $searchKey[0]['type'] = 'patientContact';
        $searchKey[0]['objectId'] = $patientId;
        $searchKey[0]['outKey'] = 'response';
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $contactDataInDB = $aResp['data']['response'];

        // fetching primary contact id from $contactDataInDB
        $primaryContactDBID = null;
        if (!empty($contactDataInDB)) {
            foreach ($contactDataInDB as $contactDBVal) {
                if ($contactDBVal['isPrimary']) {
                    $primaryContactDBID = $contactDBVal['id'];
                }
            }
        }

        // only one should be primary
        if (isset($data->getData('properties')['isPrimary']) &&
                $primaryContactDBID != null) {
            if ($data->getData('properties')['isPrimary']) {
                $updateObjs['conditions']['object'][0] = $primaryContactDBID;
                $updateObjs['properties']['isPrimary'] = false;
                $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
            }
        }
    }

    /**
     * BL for address: atleast one address is required
     * only one can be billing address
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function addressCreate($data) {
        // Atleast one address required
        if (!isset($data->getData('properties')['patientContactAddress'])) {
            throw new SynapExceptions(SynapExceptionConstants::ONE_ADDRESS_REQUIRED,400);
        } else {
            $contactAddressData = $data->getData('properties')['patientContactAddress'];
            $count = count($contactAddressData);

            // only one address should be for billing
            if ($count > 1) {
                $addressTypeCount = 0;
                foreach ($contactAddressData as $value) {
                    if (isset($value['addressType']) &&
                            $value['addressType'] == self::BILLING) {
                        $addressTypeCount++;
                        // only one address should be for billing
                        if ($addressTypeCount > 1) {
                            throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_BILLING_ADDRESS,400);
                        }
                    }
                }
            }
        }
    }

    /**
     * BL for telephone: atleast one telephone is required
     * only one telephone should be primary.
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function telephoneCreate($data) {
        // Atleast one telephone required
        if (!isset($data->getData('properties')['patientContactTelephone'])) {
            throw new SynapExceptions(SynapExceptionConstants::ONE_TELEPHONE_REQUIRED,400);
        } else {
            $contactTelephoneData = $data->getData('properties')['patientContactTelephone'];
            $countTele = count($contactTelephoneData);

            // only one telephone: by default this will be primary
            if ($countTele == 1) {
                $updateArr = array("patientContactTelephone" => array("isPrimary" => true));
                $data->setData($updateArr, 'properties', $countTele - 1);
            } elseif ($countTele > 1) {
                $contactTeleCount = 0;
                foreach ($contactTelephoneData as $value) {
                    if (isset($value['isPrimary']) && $value['isPrimary']) {
                        $contactTeleCount++;
                    }
                }
                // atleast one telephone should be primary
                if ($contactTeleCount > 1) {
                    throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY,400, array('object' => 'patientContactTelephone'));
                } elseif ($contactTeleCount == 0) {
                    throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY,400, array('object' => 'patientContactTelephone'));
                }
            }
        }
    }

    /**
     * @description function will validate data before execute delete
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteDelete($data, ServiceQueI $serviceQue) {
        // fetching data from db
        $searchKey = [];
        $searchKey[0]['objectId'] = $data->getData();
        $searchKey[0]['outKey'] = 'response';
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objIdDataInDB = $aResp['data']['response'];

        if (!empty($objIdDataInDB) && $objIdDataInDB['isPrimary']) {
            throw new SynapExceptions(SynapExceptionConstants::CAN_NOT_DELETE_PRIMARY,400, array('object' => 'patientContact'));
        }
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
     * @description function will validate data before execute update
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @param ServiceQueI $serviceQue
     */
    public function preExecuteUpdate($data, ServiceQueI $serviceQue) {
        $patientContactId = $data->getData('conditions')['object'][0];

        // fetching addresses data for a patient contact from db

        $searchKey = array(array(
                'type' => 'patientContactAddress',
                'objectId' => $patientContactId,
                'outKey' => 'response'
        ));
        $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $contactAddressDataInDB = $aResp['data']['response'];

        // billing address id from $contactAddressDataInDB
        $billingAddressDBID = null;
        foreach ($contactAddressDataInDB as $addDBVal) {
            if ($addDBVal['addressType'] == self::BILLING) {
                $billingAddressDBID = $addDBVal['id'];
            }
        }

        // fetching telephone data for a patient contact from db
        $searchKey = array(array(
                'type' => 'patientContactTelephone',
                'objectId' => $patientContactId,
                'outKey' => 'response'
        ));
        $tResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $contactTelephoneDataInDB = $tResp['data']['response'];

        // fetching primary telephone id from $telephoneDataInDB
        $primaryTeleID = null;
        foreach ($contactTelephoneDataInDB as $teleDBVal) {
            if ($teleDBVal['isPrimary']) {
                $primaryTeleID = $teleDBVal['id'];
            }
        }

        // for patientContactAddress
        $this->addressUpdate($billingAddressDBID, $data, $serviceQue);

        // for patientContactTelephone
        $this->telephoneUpdate($primaryTeleID, $data, $serviceQue);

        // for synapUser
        // Check If synap user create is checked
        if (isset($data->getData('properties')['createNewSynapUser']) && ($data->getData('properties')['createNewSynapUser'])) {
            $this->synapUserCreate($data, $serviceQue, $type = self::SYNAP_USER_UPDATE);
        }
    }

    /**
     * BL for address in update
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $billingAddressDBID
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function addressUpdate($billingAddressDBID, $data, $serviceQue) {
        if (isset($data->getData('properties')['patientContactAddress'])) {
            $contactAddressData = $data->getData('properties')['patientContactAddress'];
            $count = count($contactAddressData);
            if ($count == 1) {
                // for billing address type
                $this->addressTypeUpdate($contactAddressData, $billingAddressDBID, $count);
            } elseif ($count > 1) {
                $addressTypeDataIdArr = [];
                $billingAddressDataID = null;
                $billingAddressTypeCount = 0;
                foreach ($contactAddressData as $value) {
                    if (isset($value['addressType'])) {
                        if (isset($value['id'])) {
                            $addressTypeDataIdArr[] = $value['id'];
                        }

                        if ($value['addressType'] == self::BILLING) {
                            $billingAddressTypeCount++;
                            if (isset($value['id'])) {
                                $billingAddressDataID = $value['id'];
                            }
                        }
                    }
                }
                // only one should be billing address
                $this->addressTypeUpdate($contactAddressData, $billingAddressDBID, $count, $billingAddressDataID, $billingAddressTypeCount, $addressTypeDataIdArr);
            }
        }
    }

    /**
     * BL for only one address is for billing
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $contactAddressData
     * @param type $billingAddressDBID
     * @param type $count
     * @param type $billingAddressDataID
     * @param type $billingAddressTypeCount
     * @param type $addressTypeDataIdArr
     * @throws SynapExceptions
     */
    private function addressTypeUpdate($contactAddressData, $billingAddressDBID, $count, $billingAddressDataID = null, $billingAddressTypeCount = 0, $addressTypeDataIdArr = []) {
        $success = true;
        if ($count == 1) {
            if (isset($contactAddressData[key($contactAddressData)]['addressType']) &&
                    $contactAddressData[key($contactAddressData)]['addressType'] == self::BILLING) {
                if (isset($contactAddressData[key($contactAddressData)]['id'])) {
                    if (($billingAddressDBID != null) &&
                            ($billingAddressDBID != $contactAddressData[key($contactAddressData)]['id'])) {
                        $success = false;
                    }
                } elseif ($billingAddressDBID != null) {
                    // if try to create new of type billing
                    $success = false;
                }
            }
        } elseif ($count > 1) {
            if ($billingAddressTypeCount > 1) {
                $success = false;
            } elseif ($billingAddressTypeCount == 1) {
                // if only one billing address come and that is not same
                // whatever we have in the db
                if ($billingAddressDataID != null) {
                    if (($billingAddressDBID != null) &&
                            ($billingAddressDBID != $billingAddressDataID)) {
                        if (!in_array($billingAddressDBID, $addressTypeDataIdArr)) {
                            // if existing $billingAddressID is not in the $addressDataIdArr
                            $success = false;
                        }
                    }
                } elseif ($billingAddressDBID != null) {
                    if (!in_array($billingAddressDBID, $addressTypeDataIdArr)) {
                        // if existing $billingAddressID is not in the $addressDataIdArr
                        $success = false;
                    }
                }
            }
        }
        if (!$success) {
            throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_BILLING_ADDRESS,400);
        }
    }

    /**
     * BL for telephone in update: atleast and only one should be primary
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $primaryTeleID
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function telephoneUpdate($primaryTeleID, $data, $serviceQue) {
        if (isset($data->getData('properties')['patientContactTelephone'])) {
            $telephoneData = $data->getData('properties')['patientContactTelephone'];
            $telecount = count($telephoneData);
            if ($telecount == 1) {
                // only one should be primary
                if (isset($telephoneData[key($telephoneData)]['isPrimary'])) {
                    if ($telephoneData[key($telephoneData)]['isPrimary']) {
                        if (!isset($telephoneData[key($telephoneData)]['id']) ||
                                (isset($telephoneData[key($telephoneData)]['id']) &&
                                $telephoneData[key($telephoneData)]['id'] != $primaryTeleID)) {
                            $updateObjs['conditions']['object'][0] = $primaryTeleID;
                            $updateObjs['properties']['isPrimary'] = false;
                            $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
                        }
                    } else {
                        if (isset($telephoneData[key($telephoneData)]['id'])) {
                            if ($primaryTeleID == $telephoneData[key($telephoneData)]['id']) {
                                throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY,400, array('object' => 'patientContactTelephone'));
                            }
                        }
                    }
                }
            } elseif ($telecount > 1) {
                $teleDataIdArr = [];
                $primaryTeleCount = 0;
                $primaryTeleDataID = null;
                foreach ($telephoneData as $tvalue) {
                    if (isset($tvalue['id'])) {
                        $teleDataIdArr[] = $tvalue['id'];
                    }
                    if (isset($tvalue['isPrimary']) && $tvalue['isPrimary']) {
                        $primaryTeleCount++;
                        if (isset($tvalue['id'])) {
                            $primaryTeleDataID = $tvalue['id'];
                        }
                    }
                }
                // only one should be primary
                if ($primaryTeleCount == 1) {
                    if ($primaryTeleID != $primaryTeleDataID) {
                        $updateObjs['conditions']['object'][0] = $primaryTeleID;
                        $updateObjs['properties']['isPrimary'] = false;
                        $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
                    }
                } elseif ($primaryTeleCount > 1) {
                    throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY,400, array('object' => 'patientContactTelephone'));
                } else {
                    // if try to make existing primary telephone to non primary
                    if (in_array($primaryTeleID, $teleDataIdArr)) {
                        throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY,400, array('object' => 'patientContactTelephone'));
                    }
                }
            }
        }
    }

    /**
     * This function is used to create synap user 
     * @param type $data
     * @param type $serviceQue
     * @param type $type
     * @return type
     * @throws SynapExceptions
     * @author Sourabh Grover <sourabh.grover@sourcefuse.com>
     */
    private function synapUserCreate($data, $serviceQue, $type) {


        if ($type == self::SYNAP_USER_CREATE) {
            $email = isset($data->getData('properties')['email']) && !empty($data->getData('properties')['email']) ? $data->getData('properties')['email'] : '';
            $firstName = $data->getData('properties')['firstName'];
            $lastName = $data->getData('properties')['lastName'];
        }

        if ($type == self::SYNAP_USER_UPDATE) {
            $patientContactId = $data->getData('conditions')['object'][0];
            // fetching patient data for  from db
            $searchKey = [];
            $searchKey[0]['objectId'] = $patientContactId;
            $searchKey[0]['outKey'] = 'response';
            $patientContactResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $patientContactDataInDB = $patientContactResp['data']['response'];

            // Set value for email
            if (isset($data->getData('properties')['email']) && !empty($data->getData('properties')['email'])) {
                $email = $data->getData('properties')['email'];
            } else {
                $email = isset($patientContactDataInDB['email']) && !empty($patientContactDataInDB['email']) ? $patientContactDataInDB['email'] : '';
            }

            // Set value for firstname
            if (isset($data->getData('properties')['firstName']) && !empty($data->getData('properties')['firstName'])) {
                $firstName = $data->getData('properties')['firstName'];
            } else {
                $firstName = isset($patientContactDataInDB['firstName']) && !empty($patientContactDataInDB['firstName']) ? $patientContactDataInDB['firstName'] : '';
            }

            // Set value for lastname
            if (isset($data->getData('properties')['lastName']) && !empty($data->getData('properties')['lastName'])) {
                $lastName = $data->getData('properties')['lastName'];
            } else {
                $lastName = isset($patientContactDataInDB['lastName']) && !empty($patientContactDataInDB['lastName']) ? $patientContactDataInDB['lastName'] : '';
            }
        }

        if (!empty($email)) {
            // create new synapUser and refer it to the patient
            $objectData['properties']['email'] = $email;
            $objectData['properties']['firstName'] = $firstName;
            $objectData['properties']['lastName'] = $lastName;

            $objectData['objectType'] = 'synapUser';
            $resp = $serviceQue->executeQue('ws_oml_create', $objectData);
            $data->setData(array('synapUserId' => $resp['data']['id'], 'createNewSynapUser' => '0'), 'properties');
        } else {
            throw new SynapExceptions(SynapExceptionConstants::CREATE_SYNAPUSER_EMAIL_REQUIRED,400);
        }

        return;
    }

}
