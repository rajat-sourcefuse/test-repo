<?php

namespace SynapEssentials\OMLBLBundle\Implementations\Patient;

use SynapEssentials\OMLBLBundle\Implementations\Utility\Utility;
use SynapEssentials\TransactionManagerBundle\EventListener\Configurator;
use SynapEssentials\OMLBLBundle\Interfaces\PreExecuteI;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptions;
use SynapEssentials\SynapExceptionsBundle\Controller\SynapExceptionConstants;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
use Externals\DrFirstBundle\Managers\UploadDataManager;
use SynapEssentials\OMLBundle\Utilities\DateTimeUtility;
use SynapEssentials\Utilities\SessionUtility;
use SynapEssentials\AccessControlBundle\Interfaces\SessionConstants;


class PreExecuteM implements PreExecuteI
{
    private $empStatusArr = [
        'metaEmploymentStatus:employedFullTime',
        'metaEmploymentStatus:employedPartTime',
        'metaEmploymentStatus:employedSelf'
    ];

    const BILLING = 'metaAddressType:billing';
    const SYNAP_USER_CREATE = 'CREATE';
    const SYNAP_USER_UPDATE = 'UPDATE';
    const OBJ_PATIENT_ALLERGY = 'patientAllergy';
    const RESPONSE = 'response';
    const PROPERTIES = 'properties';
    const CONDITIONS = 'conditions';
    const PAT_TEL = 'patientTelephone';
    const OBJECTS = 'object';
    const LASTNAME = 'lastName';
    public function preExecuteCreate($data, ServiceQueI $serviceQue)
    {
        if (!(isset($data->getData(self::PROPERTIES)['isDuplicateAllow']) && $data->getData(self::PROPERTIES)['isDuplicateAllow'] == 1)) {
            //check if patient exists for given identifier
            //identifier includes check for email or identity or (lastname and dob and gender)
            $this->checkPatientDuplicate($data, $serviceQue);

        }
         // for address
        $this->checkClientAddress($data);
          // for patient contact
        $this->checkPatientContact($data);
         // for telephone
        $this->telephoneCreate($data);
         // for employment info
        $this->empInfoCreate($data);
        // create the syanp user

        $this->newsynapUserCreate($data, $serviceQue, $type = self::SYNAP_USER_CREATE);
        return true;
    }
    public function preExecuteDelete($data, ServiceQueI $serviceQue)
    {
        $utilityInstance = new Utility();
        // If pateint is eligible to be created on dr first
        if ($utilityInstance->isPatientCreatedForDrFirst($data->getData())) {
            $result = ($data->getAll());
            $configurator = Configurator::getInstance();
            $uploadDataToDrFirst = new UploadDataManager($configurator->getServiceContainer());
            $uploadDataToDrFirst->submitPatientDemographic($result, true);
        }
        return true;
    }
    public function preExecuteGet($data, ServiceQueI $serviceQue)
    {
        return true;
    }
    public function preExecuteView($data, ServiceQueI $serviceQue)
    {
        return true;
    }
    public function preExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        $properties = $data->getData(self::PROPERTIES);
        $conditions = $data->getData(self::CONDITIONS);
        $patientId = $conditions[self::OBJECTS][0];
        if (key_exists(self::OBJECTS, $conditions) && key_exists('noKnownDrugAllergies', $properties)) {

            $noKnownDrugAllergies = $properties['noKnownDrugAllergies'];
            if ($noKnownDrugAllergies == 1) {
                $patientAllergy = array();
                $patientAllergy[0]['type'] = self::OBJ_PATIENT_ALLERGY;
                $patientAllergy[0][self::CONDITIONS][0] = array('patientId' => $patientId);
                $patientAllergy[0]['outKey'] = self::OBJ_PATIENT_ALLERGY;
                $ArrPatientAllergy = $serviceQue->executeQue("ws_oml_read", $patientAllergy);
                $allergyData = $ArrPatientAllergy['data'][self::OBJ_PATIENT_ALLERGY];
                if (count($allergyData) > 0) {
                    throw new SynapExceptions(SynapExceptionConstants::PATIENT_ALLERGIES_ALREADY_ADDED, 400);
                }
            }
        }
         // for address
        if (isset($data->getData(self::PROPERTIES)['patientAddress'])) {
            $this->checkClientAddressForUpdate($data, $serviceQue);
        }

        // for contact
        if (isset($data->getData(self::PROPERTIES)['patientContact'])) {
            $this->checkPatientContactForUpdate($data, $serviceQue);
        }
          // for employment info
        $this->empInfoUpdate($patientId, $data, $serviceQue);
         // for telephone
          // fetching primary telephone id from $telephoneDataInDB


           // fetching telephone data for a patient from db
        $searchKey = [];
        $searchKey[0]['objectId'] = $patientId;
        $searchKey[0]['outKey'] = self::RESPONSE;
        $searchKey[0]['type'] = self::PAT_TEL;
        $tResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $telephoneDataInDB = $tResp['data'][self::RESPONSE];
        if (!empty($telephoneDataInDB) && !isset($telephoneDataInDB[0])) {
            $telephoneDataInDB = [$tResp['data'][self::RESPONSE]];
        }
        $primaryTeleID = null;
        foreach ($telephoneDataInDB as $teleDBVal) {
            if ($teleDBVal['isPrimary']) {
                $primaryTeleID = $teleDBVal['id'];
            }
        }
        $this->telephoneUpdate($primaryTeleID, $data, $serviceQue);
        $this->newsynapUserCreate($data, $serviceQue, $type = self::SYNAP_USER_UPDATE);
    }
    /**
     * BL for emp info in update
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $patientId
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function empInfoUpdate($patientId, $data, $serviceQue)
    {
        if (isset($data->getData('properties')['employmentStatus'])) {
            // fetching employment info from db
            $searchKey = [];
            $searchKey[0]['type'] = 'patientEmploymentInfo';
            $searchKey[0]['objectId'] = $patientId;
            $searchKey[0]['outKey'] = 'response';
            $empInfoResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
            $empInfoDataInDB = $empInfoResp['data']['response'];
            if (!empty($empInfoDataInDB) && !isset($empInfoDataInDB[0])) {
                $empInfoDataInDB = [$empInfoResp['data']['response']];
            }

            if (in_array($data->getData('properties')['employmentStatus'], $this->empStatusArr)) {

                // EmploymentInfo is required
                if (empty($empInfoDataInDB) &&
                    empty($data->getData('properties')['patientEmploymentInfo'])) {
                    throw new SynapExceptions(SynapExceptionConstants::EMPLOYMENT_INFO_REQUIRED, 400);
                }
            } else {
                // in this case EmploymentInfo is not required
                // delete from db if there
                if (!empty($empInfoDataInDB)) {
                    $deleteObj = [$empInfoDataInDB[0]['id']];
                    $empInfoResp = $serviceQue->executeQue("ws_oml_delete", $deleteObj);
                }
                // unset the EmploymentInfo data if it is there in request
                if (isset($data->getData('properties')['patientEmploymentInfo'])) {
                    $updateArr = array('patientEmploymentInfo');
                    $data->unSetData($updateArr, 'properties');
                }
            }
        }
    }
    private function empInfoCreate($data)
    {
        if (isset($data->getData(self::PROPERTIES)['employmentStatus']) &&
            in_array($data->getData(self::PROPERTIES)['employmentStatus'], $this->empStatusArr)) {
//below lines commented in relation to AP-152
//            if (empty($data->getData(self::PROPERTIES)['patientEmploymentInfo'])) {
//                throw new SynapExceptions(SynapExceptionConstants::EMPLOYMENT_INFO_REQUIRED);
//            }
        } else {
            $updateArr = array('patientEmploymentInfo');
            $data->unSetData($updateArr, self::PROPERTIES);
        }
    }
    /**
     * BL for telephone in update
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $primaryTeleID
     * @param type $data
     * @param type $serviceQue
     * @throws SynapExceptions
     */
    private function telephoneUpdate($primaryTeleID, $data, $serviceQue)
    {
        if (isset($data->getData(self::PROPERTIES)[self::PAT_TEL])) {
            $telephoneData = $data->getData(self::PROPERTIES)[self::PAT_TEL];
            $teleCount = count($telephoneData);
            if ($teleCount > 1) {
                $teleDataIdArr = [];
                $primaryTeleCount = 0;
                $primaryTeleDataID = null;
                foreach ($telephoneData as $teleValue) {
                    if (isset($teleValue['id'])) {
                        $teleDataIdArr[] = $teleValue['id'];
                    }
                    if (isset($teleValue['isPrimary']) && $teleValue['isPrimary']) {
                        $primaryTeleCount++;
                        if (isset($teleValue['id'])) {
                            $primaryTeleDataID = $teleValue['id'];
                        }
                    }
                }
                // only one should be primary
                if ($primaryTeleCount == 1) {
                    if ($primaryTeleID != $primaryTeleDataID) {
                        $updateObjs[self::CONDITIONS][self::OBJECTS][0] = $primaryTeleID;
                        $updateObjs[self::PROPERTIES]['isPrimary'] = false;
                        $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
                    }
                } elseif ($primaryTeleCount > 1) {
                    throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => self::PAT_TEL));
                } else {
                    // if try to make existing primary telephone to non primary
                    if (in_array($primaryTeleID, $teleDataIdArr)) {
                        throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => self::PAT_TEL));
                    }
                }
            } elseif ($teleCount == 1) {
                // only one should be primary
                if (isset($telephoneData[key($telephoneData)]['isPrimary'])) {
                    if ($telephoneData[key($telephoneData)]['isPrimary']) {
                        if (isset($telephoneData[key($telephoneData)]['id'])) {
                            $updateObjs[self::CONDITIONS][self::OBJECTS][0] = $primaryTeleID;
                            $updateObjs[self::PROPERTIES]['isPrimary'] = false;
                            $upResp = $serviceQue->executeQue("ws_oml_update", $updateObjs);
                        }
                    } else {
                        if (isset($telephoneData[key($telephoneData)]['id'])) {
                            if ($primaryTeleID == $telephoneData[key($telephoneData)]['id']) {
                                throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => self::PAT_TEL));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * BL for telephone: atleast one telephone is required.
     * 
     * @author Naveen Kumar <naveen.kumar@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function telephoneCreate($data)
    {
        // Atleast one telephone required
        if (!isset($data->getData(self::PROPERTIES)[self::PAT_TEL])) {
            throw new SynapExceptions(SynapExceptionConstants::ONE_TELEPHONE_REQUIRED, 400);
        } else {
            $telephoneData = $data->getData(self::PROPERTIES)[self::PAT_TEL];
            $countTele = count($telephoneData);

            // only one telephone: by default this will be primary
            if ($countTele == 1) {
                $updateArr = array("patientTelephone" => array("isPrimary" => true));
                $data->setData($updateArr, self::PROPERTIES, $countTele - 1);
            } elseif ($countTele > 1) {
                $primaryTeleCount = 0;
                foreach ($telephoneData as $value) {
                    if (isset($value['isPrimary']) && $value['isPrimary']) {
                        $primaryTeleCount++;
                    }
                }
                // atleast one telephone should be primary
                if ($primaryTeleCount > 1) {
                    throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => self::PAT_TEL));
                } elseif ($primaryTeleCount == 0) {
                    throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => self::PAT_TEL));
                }
            }
        }
    }
    /**
     * @description This function will check for duplicate patient based on email, identity and combination of dob, gender and last name      
     * @param type        $data
     * @param ServiceQueI $serviceQue
     *
     * @throws SynapExceptions
     */
    private function checkPatientDuplicate($data, ServiceQueI $serviceQue)
    {
        //create serach key to find duplicates
        $gender = $data->getData(self::PROPERTIES)['gender'];
        $dob = $data->getData(self::PROPERTIES)['dob'];
        $lastName = $data->getData(self::PROPERTIES)[self::LASTNAME];
        $email = isset($data->getData(self::PROPERTIES)['email']) ? $data->getData(self::PROPERTIES)['email'] : '';
        $identity = isset($data->getData(self::PROPERTIES)['identity']) ? $data->getData(self::PROPERTIES)['identity'] : '';

        //prepare conditions
        $conditions = array(
            array(
                array('dob' => $dob),
                array('gender' => $gender),
                array(self::LASTNAME => $lastName)
            )
        );

        if ($email) {
            array_push($conditions, 'OR', array('email' => $email));
        }
        if ($identity) {
            array_push($conditions, 'OR', array('identity' => $identity));
        }

        $searchKey = [];
        $searchKey[0]['type'] = 'patient';

        $searchKey[0][self::CONDITIONS] = $conditions;
        $searchKey[0]['outKey'] = self::RESPONSE;
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);

        if (count($resp['data'][self::RESPONSE]) > 0) {
            throw new SynapExceptions(SynapExceptionConstants::CLIENT_EXISTS, 409, array('clientId' => $resp['data'][self::RESPONSE][0]['id']));
        }
        return;
    }
    /**
     * BL for address: only one primary address.
     * 
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function checkClientAddressForUpdate($data, $serviceQue)
    {
        // Atleast one address required
        if (!empty($data->getData(self::PROPERTIES)['patientAddress'])) {
            $addressData = $data->getData(self::PROPERTIES)['patientAddress'];
            $addressCount = count($addressData);
            
            //check db primary address
            $primaryAddressId = null;
            $nonPrimary = false;
            $primaryAddress = $this->fetchPrimaryObject('patientAddress', $data, $serviceQue);
            if (!empty($primaryAddress)) {
                $primaryAddressId = $primaryAddress['id'];
            }

            if ($addressCount > 1) {
                $primaryAddressCount = 0;
                foreach ($addressData as $value) {
                    if (isset($value['isPrimary']) && $value['isPrimary']) {
                        $primaryAddressCount++;
                    } else if (isset($value['isPrimary']) && !$value['isPrimary'] && isset($value['id']) && $value['id'] == $primaryAddressId) {
                        //marking primary address as non - mandatory
                        $nonPrimary = true;
                    }
                }
                // atleast one address should be primary
                if ($primaryAddressCount > 1) {
                    throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'address'));
                } else if ($primaryAddressCount < 1 && $nonPrimary) {
                    throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'address'));
                }
            } else {
                $addressValue = $addressData[0];
                if (isset($addressValue['isPrimary']) && !$addressValue['isPrimary'] && isset($addressValue['id']) && $addressValue['id'] == $primaryAddressId) {
                    //marking primary address as non - mandatory
                    throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'address'));
                }
            }
        }
        return;
    }
    /**
     * BL for address: only one primary contact.
     * 
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function checkPatientContactForUpdate($data, $serviceQue)
    {
        // Atleast one address required
        if (!empty($data->getData(self::PROPERTIES)['patientContact'])) {
            $contactData = $data->getData(self::PROPERTIES)['patientContact'];
            $contactCount = count($contactData);
            
            //check db for primary contact
            $primaryContactId = null;
            $nonPrimary = false;
            $primaryContact = $this->fetchPrimaryObject('patientContact', $data, $serviceQue);
            if (!empty($primaryContact)) {
                $primaryContactId = $primaryContact['id'];
            }

            if ($contactCount > 1) {
                $primaryContactCount = 0;
                foreach ($contactData as $value) {
                    if (isset($value['isPrimary']) && $value['isPrimary']) {
                        $primaryContactCount++;
                    } else if (isset($value['isPrimary']) && !$value['isPrimary'] && isset($value['id']) && $value['id'] == $primaryContactId) {
                        //marking primary contact as non - mandatory
                        $nonPrimary = true;
                    }
                }
                // atleast one contact should be primary
                if ($primaryContactCount > 1) {
                    throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'client contact'));
                } else if ($primaryContactCount < 1 && ($nonPrimary || !$primaryContactId)) {
                    throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'client contact'));
                }
            } else {
                $contactValue = $contactData[0];
                if (isset($contactValue['isPrimary']) && !$contactValue['isPrimary'] && isset($contactValue['id']) && $contactValue['id'] == $primaryContactId) {
                    //marking primary contact as non - mandatory
                    throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'client contact'));
                }
            }
        }
        return;
    }
    /**
     * BL for address: atleast one address required.
     * 
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function checkClientAddress($data)
    {
        // Atleast one address required
        if (isset($data->getData(self::PROPERTIES)['patientAddress']) && !empty($data->getData(self::PROPERTIES)['patientAddress'])) {
            $addressData = $data->getData(self::PROPERTIES)['patientAddress'];
            $addressCount = count($addressData);
            // only one address: by default this will be primary
            if ($addressCount == 1) {
                $updateArr = array("patientAddress" => array("isPrimary" => true));
                $data->setData($updateArr, self::PROPERTIES, $addressCount - 1);
            } elseif ($addressCount > 1) {
                $primaryAddressCount = 0;
                foreach ($addressData as $value) {
                    if (isset($value['isPrimary']) && $value['isPrimary']) {
                        $primaryAddressCount++;
                    }
                }
                // atleast one address should be primary
                if ($primaryAddressCount > 1) {
                    throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'address'));
                } else if ($primaryAddressCount == 0) {
                    throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'address'));
                }
            }
        } else {
            // Message display the patient address is not found
            throw new SynapExceptions(SynapExceptionConstants::ONE_ADDRESS_REQUIRE_FOR_PATIENT, 400);
        }
    }
    /**     
     * function will fetch primary object
     * @param type        $data
     * @param ServiceQueI $serviceQue
     *
     * @throws SynapExceptions
     */
    private function fetchPrimaryObject($objectName, $data, ServiceQueI $serviceQue)
    {
        $searchKey = [];
        $searchKey[0]['type'] = $objectName;
        $searchKey[0][self::CONDITIONS][] = array('patientId' => $data->getData(self::CONDITIONS)[self::OBJECTS][0]);
        $searchKey[0][self::CONDITIONS][] = array('isPrimary' => true);
        $searchKey[0]['outKey'] = self::RESPONSE;
        $resp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $objectResponse = $resp['data'][self::RESPONSE];
        return !empty($objectResponse) ? $objectResponse[0] : '';
    }
    /**
     * BL for address: atleast one primary contact only
     * 
     * @author Manish Kumar <manish.kumar@sourcefuse.com>
     * @param type $data
     * @throws SynapExceptions
     */
    private function checkPatientContact($data)
    {
        // only one primary contact
        if (isset($data->getData(self::PROPERTIES)['patientContact']) && !empty($data->getData(self::PROPERTIES)['patientContact'])) {
            $contactData = $data->getData(self::PROPERTIES)['patientContact'];
            $contactCount = count($contactData);
            // only one contact: by default this will be primary
            if ($contactCount == 1) {
                $updateArr = array("patientContact" => array("isPrimary" => true));
                $data->setData($updateArr, self::PROPERTIES, $contactCount - 1);
            } elseif ($contactCount > 1) {
                $primaryContactCount = 0;
                foreach ($contactData as $value) {
                    if (isset($value['isPrimary']) && $value['isPrimary']) {
                        $primaryContactCount++;
                    }
                }
                // atleast one contact should be primary
                if ($primaryContactCount > 1) {
                    throw new SynapExceptions(SynapExceptionConstants::ONLY_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'client contact'));
                } else if ($primaryContactCount == 0) {
                    throw new SynapExceptions(SynapExceptionConstants::ATLEAST_ONE_SHOULD_PRIMARY, 400, array(self::OBJECTS => 'client contact'));
                }
            }
        }
    }
    private function newsynapUserCreate($data, $serviceQue, $type)
    {
        if (!isset($data->getData(self::PROPERTIES)['createNewSynapUser']) || !$data->getData(self::PROPERTIES)['createNewSynapUser']) {
            return;
        }   
            // create the patient
        if ($type == self::SYNAP_USER_CREATE) {
            $resp = $this->createSynapUser($data, $serviceQue);
        }
            // update the patient
        if ($type == self::SYNAP_USER_UPDATE) {
            $resp = $this->updateSynapUser($data, $serviceQue);
        }
        $objectData = array();
            // check the email is not empty
        if (!empty($resp['email'])) {
            $resp['email'] = strtolower($resp['email']);
                // @date:28june2017 check the patient email id already exists
                // fetching patient data from email by from db
            $existEmailType = false;
            $searchKey = [];
            $searchKey[0]['type'] = 'patient';
            $searchKey[0][self::CONDITIONS][] = ['email' => ["ILIKE" => $resp['email']]];
            $searchKey[0]['outKey'] = self::RESPONSE;
                // if patient edit case already exist the synap user
            if (isset($patientDataInDB['synapUserId']) && !empty($patientDataInDB['synapUserId'])) {
                    // check the patient email is not inclued the duplicate email address
                $searchKey[0][self::CONDITIONS][] = array('synapUserId' => array('NOTIN' => array($patientDataInDB['synapUserId'])));
                $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
                $emailaddressDataInDB = $aResp['data'][self::RESPONSE];
                $emailaddressDataInDBcount = count($emailaddressDataInDB);
                $objectData[self::CONDITIONS][self::OBJECTS][0] = $patientDataInDB['synapUserId'];
                    //result found zero redord 
                if ($emailaddressDataInDBcount == 0) {
                    $existEmailType = true;
                }
            } else {
                    // check the read the patient duplicate email address
                $aResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
                $emailaddressDataInDB = $aResp['data'][self::RESPONSE];
                $emailaddressDataInDBcount = count($emailaddressDataInDB);
                    // check for create the patient
                if ($emailaddressDataInDBcount == 0) {
                    $existEmailType = true;
                }
            }
                // check the vatible @ $existEmailType is value for true or false
            if ($existEmailType) {
                $objectData[self::PROPERTIES]['username'] = $resp['email'];
                $objectData[self::PROPERTIES]['email'] = $resp['email'];
            } else {
                throw new SynapExceptions(SynapExceptionConstants::EMAIL_ALREADY_EXISTS, 400);
            }
            $objectData[self::PROPERTIES]['firstName'] = $resp['firstName'];
            $objectData[self::PROPERTIES][self::LASTNAME] = $resp[self::LASTNAME];

            $objectData['objectType'] = 'synapUser';

            $data->setData(array(self::PROPERTIES => array('email' => $resp['email'])));
            $data->setData(array('userType' => 'metaUserType:client'), self::PROPERTIES);
            // echo $resp['servicetype'];
            // exit;
            $resp2 = $serviceQue->executeQue($resp['servicetype'], $objectData);


            if ($resp['servicetype'] == 'ws_oml_create') {

                $data->setData(array('synapUserId' => $resp2['data']['id'], 'createNewSynapUser' => '0'), self::PROPERTIES);
            }

        } else {
                // email is empty then create the syanap user and synapuser is not login
            if ($resp['servicetype'] == 'ws_oml_create') {
                $objectData[self::PROPERTIES]['firstName'] = $resp['firstName'];
                $objectData[self::PROPERTIES][self::LASTNAME] = $resp[self::LASTNAME];

                $objectData[self::PROPERTIES]['isActive'] = false;
                $objectData['objectType'] = 'synapUser';
                $resp2 = $serviceQue->executeQue($resp['servicetype'], $objectData);
                $data->setData(array('synapUserId' => $resp2['data']['id'], 'createNewSynapUser' => '0'), self::PROPERTIES);

                $data->setData(array('userType' => 'metaUserType:client'), self::PROPERTIES);

            } else if (isset($patientDataInDB['synapUserId'])) {
                $objectData[self::CONDITIONS][self::OBJECTS][0] = $patientDataInDB['synapUserId'];
                    //    $objectData[self::PROPERTIES]['username'] = $email;
                    //   $objectData[self::PROPERTIES]['email'] = $email;
                $objectData[self::PROPERTIES]['firstName'] = $resp['firstName'];
                $objectData[self::PROPERTIES][self::LASTNAME] = $resp[self::LASTNAME];
                $objectData[self::PROPERTIES]['isActive'] = false;

                $objectData['objectType'] = 'synapUser';

                $resp2 = $serviceQue->executeQue($resp['servicetype'], $objectData);
                $data->setData(array('userType' => 'metaUserType:client'), self::PROPERTIES);
            }
        }
        return;

    }
    private function createSynapUser($data, $serviceQue)
    {
        $resp = [];
        $resp['servicetype'] = 'ws_oml_create';
        $resp['email'] = isset($data->getData(self::PROPERTIES)['email']) && !empty($data->getData(self::PROPERTIES)['email']) ? $data->getData(self::PROPERTIES)['email'] : null;
        $resp['firstName'] = $data->getData(self::PROPERTIES)['firstName'];
        $resp[self::LASTNAME] = $data->getData(self::PROPERTIES)[self::LASTNAME];
        return $resp;

    }
    private function updateSynapUser($data, $serviceQue)
    {

        $resp = [];
        $resp['servicetype'] = 'ws_oml_update';
        $resp['patientId'] = $data->getData(self::CONDITIONS)[self::OBJECTS][0];
        // fetching patient data for  from db
        $searchKey = [];
        $searchKey[0]['objectId'] = $resp['patientId'];
        $searchKey[0]['outKey'] = self::RESPONSE;
        $patientResp = $serviceQue->executeQue("ws_oml_read", $searchKey);
        $patientDataInDB = $patientResp['data'][self::RESPONSE];
        // Set value for email
        if (isset($data->getData(self::PROPERTIES)['email']) && !empty($data->getData(self::PROPERTIES)['email'])) {
            $resp['email'] = $data->getData(self::PROPERTIES)['email'];
        } else {
            $resp['email'] = null;
            //$email = isset($patientDataInDB['email']) && !empty($patientDataInDB['email']) ? $patientDataInDB['email'] : '';
        }
        // Set value for firstname
        if (isset($data->getData(self::PROPERTIES)['firstName']) && !empty($data->getData(self::PROPERTIES)['firstName'])) {
            $resp['firstName'] = $data->getData(self::PROPERTIES)['firstName'];
        } else {
            $resp['firstName'] = isset($patientDataInDB['firstName']) && !empty($patientDataInDB['firstName']) ? $patientDataInDB['firstName'] : '';
        }
        // Set value for lastname
        if (isset($data->getData(self::PROPERTIES)[self::LASTNAME]) && !empty($data->getData(self::PROPERTIES)[self::LASTNAME])) {
            $resp[self::LASTNAME] = $data->getData(self::PROPERTIES)[self::LASTNAME];
        } else {
            $resp[self::LASTNAME] = isset($patientDataInDB[self::LASTNAME]) && !empty($patientDataInDB[self::LASTNAME]) ? $patientDataInDB[self::LASTNAME] : '';
        }
        return $resp;
    }
}