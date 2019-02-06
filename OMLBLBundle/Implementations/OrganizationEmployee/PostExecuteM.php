<?php
namespace SynapEssentials\OMLBLBundle\Implementations\OrganizationEmployee;
use SynapEssentials\OMLBundle\Interfaces\ServiceQueI;
/**
 * BL class for postExecute of organizationEmployee.
 *
 * @author Bhupesh Gupta <bhupesh.gupta@sourcefuse.com>
 */
use SynapEssentials\OMLBLBundle\Interfaces\PostExecuteI;
class PostExecuteM implements PostExecuteI
{
    /**
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteCreate($data, ServiceQueI $serviceQue)
    { 
        return true;
    }
    /**
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
     * Function will perform some actions after execute get
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteGet($data, ServiceQueI $serviceQue)
    {  
         
        /* AP-1564 * Revenue Cycle Manager Profile Still present in GET API response, AP-1633 fix */
        if (!empty($data->getData('responseArr'))) {
            $responseArr = $data->getData('responseArr');  
            $responseArr2=array();
            if(!isset($responseArr[0])) {
                $responseArr2 = $this->setGetGata($responseArr);
                $data->setData( $responseArr2,'responseArr',null,true);
            }else{ 
                foreach($responseArr as $resKey=>$res){ 
                   $responseArr2[$resKey] = $this->setGetGata($res);
                }
                $data->setData( $responseArr2,'responseArr');
            } 
        }
        return true;
    }
    /**
     * Function is used to unset/modify data
     *  
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    private function setGetGata($data){
        if(isset($data["profile"][0]) && !empty($data["profile"][0])){ 
            foreach ($data['profile'] as $key=>$profile) {
                // unset profile where is_internal = 1
               if(isset($data['profileValue'][$profile]['profileIsInternal']) && $data['profileValue'][$profile]['profileIsInternal'] == 1){ 
                    unset($data['profileValue'][$profile]);
                    unset($data['profile'][$key]);
               }  
            }
        }
        return $data;
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
     * 
     * @param type $data
     * @param ServiceQueI $serviceQue
     * @return boolean
     */
    public function postExecuteUpdate($data, ServiceQueI $serviceQue)
    {
        return true;
    }
}