<?php

namespace MauticPlugin\DOIConfirmBundle\Helper;
use Mautic\LeadBundle\Helper\CustomFieldHelper;

class LeadHelper {

    public static function leadFieldUpdate($leadFieldUpdate, $leadModel, $lead, $ip = null ) {

        if(empty($leadFieldUpdate))
        {
            return;
        }

        //get lead field configs 
        $leadValueConfigs = explode(',',$leadFieldUpdate );   
        
        //get current lead fields and values
        $leadFields = $lead->getFields(true);

        $leadValues = [];
        foreach($leadValueConfigs as $leadValueConfig)
        {
            list($leadFieldAlias, $leadFieldValue) = array_merge( explode( '=', $leadValueConfig ), array( true ) );

            // we replace tokens if any
            if($leadFieldAlias && $leadFieldValue)
            {
                $timestring = date("d.m.Y H:i:s");
                
                //generate token
                try {
                    $token = bin2hex(random_bytes(16));
                } catch (\Throwable $exception) {
                    $token = sha1(uniqid('', true));
                }

                $leadFieldValue = str_replace('{doi_ip}', $ip, $leadFieldValue);
                $leadFieldValue = str_replace('{doi_timestamp}', $timestring, $leadFieldValue);
                $leadFieldValue = str_replace('{tokenid}', $token, $leadFieldValue);
                
                //if $leadFieldValue is token then replace with current lead field value
                if( preg_match('/\{.*\}/', $leadFieldValue) )
                {
                    $tokenAlias = str_replace(['{','}'],'',$leadFieldValue);
                    if(isset($leadFields[ $tokenAlias ])) {
                        $leadFieldValue = $leadFields[$tokenAlias]['normalizedValue'];
                    }
                }
                
                $leadValues[$leadFieldAlias] = $leadFieldValue;
            }

        }

        if(!empty($leadValues) && !empty($leadFields)){
            $leadModel->setFieldValues($lead, CustomFieldHelper::fieldsValuesTransformer($leadFields, $leadValues), false);
            $leadModel->saveEntity($lead); 
        }

    }

}
