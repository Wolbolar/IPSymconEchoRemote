<?php

declare(strict_types=1);

class IPSModule
{
    protected $InstanceID;

    private $properties = [];
    private $attributes = [];
    private $references = [];

    private $buffer = [];

    private $receiveDataFilter = '';
    private $forwardDataFilter = '';

    public function __construct($InstanceID)
    {
        $this->InstanceID = $InstanceID;
    }

    public function Create()
    {
        //Has to be overwritten by implementing module
    }

    public function Destroy()
    {
        //Has to be overwritten by implementing module
    }

    public function GetProperty($Name)
    {
        if (!isset($this->properties[$Name])) {
            throw new Exception(sprintf('Property %s not found', $Name));
        }

        return $this->properties[$Name]['Current'];
    }

    public function SetProperty($Name, $Value)
    {
        if (!isset($this->properties[$Name])) {
            throw new Exception(sprintf('Property %s not found', $Name));
        }

        $this->properties[$Name]['Pending'] = $Value;
    }

    public function GetConfiguration()
    {
        $result = [];
        foreach ($this->properties as $name => $property) {
            $result[$name] = $property['Current'];
        }

        return $result;
    }

    public function SetConfiguration($Configuration)
    {
        $json = json_decode($Configuration);
        if ($json === null) {
            throw new \Exception('Cannot parse configuration json');
        }
        foreach ($json as $name => $value) {
            if (isset($this->properties[$name])) {
                $this->properties[$name]['Pending'] = $value;
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        //Has to be overwritten by implementing module
    }

    public function HasChanges()
    {
        foreach ($this->properties as &$property) {
            if ($property['Current'] != $property['Pending']) {
                return true;
            }
        }

        return false;
    }

    public function ResetChanges()
    {
        foreach ($this->properties as &$property) {
            $property['Pending'] = $property['Current'];
        }
    }

    public function ApplyChanges()
    {
        foreach ($this->properties as &$property) {
            $property['Current'] = $property['Pending'];
        }

        //If the module overrides ApplyChanges, it might change the status again
        if (IPS_GetInstance($this->InstanceID)['InstanceStatus'] == 100 /* IS_CREATING */) {
            $this->SetStatus(102 /* IS_ACTIVE */);
        }
    }

    public function GetReceiveDataFilter()
    {
        // This getter function is only availabe in Stub, not in PHP-SDK
        // It is required to utilize the ReceiveDataFilter
        return $this->receiveDataFilter;
    }

    public function ReceiveData($JSONString)
    {
        //Has to be overwritten by implementing module
    }

    public function ForwardData($JSONString)
    {
        //Has to be overwritten by implementing module
        return '';
    }

    public function GetForwardDataFilter()
    {
        // This getter function is only availabe in Stub, not in PHP-SDK
        // It is required to utilize the ForwardDataFilter
        return $this->forwardDataFilter;
    }

    public function RequestAction($Ident, $Value)
    {
        //Has to be overwritten by implementing module
    }

    public function GetConfigurationForm()
    {
        return '{}';
    }

    public function GetConfigurationForParent()
    {
        return '{}';
    }

    public function Translate($Text)
    {
        return $Text;
    }

    public function GetReferenceList()
    {
        return $this->references;
    }

    protected function GetIDForIdent($Ident)
    {
        return IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
    }

    protected function RegisterPropertyBoolean($Name, $DefaultValue)
    {
        $this->RegisterProperty($Name, $DefaultValue, 0);
    }

    protected function RegisterPropertyInteger($Name, $DefaultValue)
    {
        $this->RegisterProperty($Name, $DefaultValue, 1);
    }

    protected function RegisterPropertyFloat($Name, $DefaultValue)
    {
        $this->RegisterProperty($Name, $DefaultValue, 2);
    }

    protected function RegisterPropertyString($Name, $DefaultValue)
    {
        $this->RegisterProperty($Name, $DefaultValue, 3);
    }

    protected function RegisterAttributeBoolean($Name, $DefaultValue)
    {
        $this->RegisterAttribute($Name, $DefaultValue, 0);
    }

    protected function RegisterAttributeInteger($Name, $DefaultValue)
    {
        $this->RegisterAttribute($Name, $DefaultValue, 1);
    }

    protected function RegisterAttributeFloat($Name, $DefaultValue)
    {
        $this->RegisterAttribute($Name, $DefaultValue, 2);
    }

    protected function RegisterAttributeString($Name, $DefaultValue)
    {
        $this->RegisterAttribute($Name, $DefaultValue, 3);
    }

    protected function RegisterTimer($Ident, $Milliseconds, $ScriptText)
    {
        //How and why do we want to test this?
    }

    protected function SetTimerInterval($Ident, $Milliseconds)
    {
        //How and why do we want to test this?
    }

    protected function RegisterScript($Ident, $Name, $Content = '', $Position = 0)
    {
        //How and why do we want to test this?
    }

    protected function RegisterVariableBoolean($Ident, $Name, $Profile = '', $Position = 0)
    {
        return $this->RegisterVariable($Ident, $Name, 0, $Profile, $Position);
    }

    protected function RegisterVariableInteger($Ident, $Name, $Profile = '', $Position = 0)
    {
        return $this->RegisterVariable($Ident, $Name, 1, $Profile, $Position);
    }

    protected function RegisterVariableFloat($Ident, $Name, $Profile = '', $Position = 0)
    {
        return $this->RegisterVariable($Ident, $Name, 2, $Profile, $Position);
    }

    protected function RegisterVariableString($Ident, $Name, $Profile = '', $Position = 0)
    {
        return $this->RegisterVariable($Ident, $Name, 3, $Profile, $Position);
    }

    protected function UnregisterVariable($Ident)
    {
        $vid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);
        if ($vid === false) {
            return;
        }
        if (!IPS_VariableExists($vid)) {
            return;
        } //bail out
        IPS_DeleteVariable($vid);
    }

    protected function MaintainVariable($Ident, $Name, $Type, $Profile, $Position, $Keep)
    {
        if ($Keep) {
            $this->RegisterVariable($Ident, $Name, $Type, $Profile, $Position);
        } else {
            $this->UnregisterVariable($Ident);
        }
    }

    protected function EnableAction($Ident)
    {
        IPS\VariableManager::setVariableAction(IPS_GetObjectIDByIdent($Ident, $this->InstanceID), $this->InstanceID);
    }

    protected function DisableAction($Ident)
    {
        IPS\VariableManager::setVariableAction(IPS_GetObjectIDByIdent($Ident, $this->InstanceID), 0);
    }

    protected function MaintainAction($Ident, $Keep)
    {
        if ($Keep) {
            $this->EnableAction($Ident);
        } else {
            $this->DisableAction($Ident);
        }
    }

    protected function ReadPropertyBoolean($Name)
    {
        if (!isset($this->properties[$Name])) {
            throw new Exception(sprintf('Property %s not found', $Name));
        }

        if ($this->properties[$Name]['Type'] != 0) {
            throw new Exception(sprintf('Property %s is not of type Boolean', $Name));
        }

        return $this->properties[$Name]['Current'];
    }

    protected function ReadPropertyInteger($Name)
    {
        if (!isset($this->properties[$Name])) {
            throw new Exception(sprintf('Property %s not found', $Name));
        }

        if ($this->properties[$Name]['Type'] != 1) {
            throw new Exception(sprintf('Property %s is not of type Integer', $Name));
        }

        return $this->properties[$Name]['Current'];
    }

    protected function ReadPropertyFloat($Name)
    {
        if (!isset($this->properties[$Name])) {
            throw new Exception(sprintf('Property %s not found', $Name));
        }

        if ($this->properties[$Name]['Type'] != 2) {
            throw new Exception(sprintf('Property %s is not of type Float', $Name));
        }

        return $this->properties[$Name]['Current'];
    }

    protected function ReadPropertyString($Name)
    {
        if (!isset($this->properties[$Name])) {
            throw new Exception(sprintf('Property %s not found', $Name));
        }

        if ($this->properties[$Name]['Type'] != 3) {
            throw new Exception(sprintf('Property %s is not of type String', $Name));
        }

        return $this->properties[$Name]['Current'];
    }

    protected function ReadAttributeBoolean($Name)
    {
        if (!isset($this->attributes[$Name])) {
            throw new Exception(sprintf('Attribute %s not found', $Name));
        }

        if ($this->attributes[$Name]['Type'] != 0) {
            throw new Exception(sprintf('Attribute %s is not of type Boolean', $Name));
        }

        return $this->attributes[$Name]['Current'];
    }

    protected function ReadAttributeInteger($Name)
    {
        if (!isset($this->attributes[$Name])) {
            throw new Exception(sprintf('Attribute %s not found', $Name));
        }

        if ($this->attributes[$Name]['Type'] != 1) {
            throw new Exception(sprintf('Attribute %s is not of type Integer', $Name));
        }

        return $this->attributes[$Name]['Current'];
    }

    protected function ReadAttributeFloat($Name)
    {
        if (!isset($this->attributes[$Name])) {
            throw new Exception(sprintf('Attribute %s not found', $Name));
        }

        if ($this->attributes[$Name]['Type'] != 2) {
            throw new Exception(sprintf('Attribute %s is not of type Float', $Name));
        }

        return $this->attributes[$Name]['Current'];
    }

    protected function ReadAttributeString($Name)
    {
        if (!isset($this->attributes[$Name])) {
            throw new Exception(sprintf('Attribute %s not found', $Name));
        }

        if ($this->attributes[$Name]['Type'] != 3) {
            throw new Exception(sprintf('Attribute %s is not of type String', $Name));
        }

        return $this->attributes[$Name]['Current'];
    }

    protected function WriteAttributeBoolean($Name, bool $Value)
    {
        if (!isset($this->attributes[$Name])) {
            throw new Exception(sprintf('Attribute %s not found', $Name));
        }

        if ($this->attributes[$Name]['Type'] != 0) {
            throw new Exception(sprintf('Attribute %s is not of type Boolean', $Name));
        }

        $this->attributes[$Name]['Current'] = $Value;
    }

    protected function WriteAttributeInteger($Name, int $Value)
    {
        if (!isset($this->attributes[$Name])) {
            throw new Exception(sprintf('Attribute %s not found', $Name));
        }

        if ($this->attributes[$Name]['Type'] != 1) {
            throw new Exception(sprintf('Attribute %s is not of type Integer', $Name));
        }

        $this->attributes[$Name]['Current'] = $Value;
    }

    protected function WriteAttributeFloat($Name, float $Value)
    {
        if (!isset($this->attributes[$Name])) {
            throw new Exception(sprintf('Attribute %s not found', $Name));
        }

        if ($this->attributes[$Name]['Type'] != 2) {
            throw new Exception(sprintf('Attribute %s is not of type Float', $Name));
        }

        $this->attributes[$Name]['Current'] = $Value;
    }

    protected function WriteAttributeString($Name, string $Value)
    {
        if (!isset($this->attributes[$Name])) {
            throw new Exception(sprintf('Attribute %s not found', $Name));
        }

        if ($this->attributes[$Name]['Type'] != 3) {
            throw new Exception(sprintf('Attribute %s is not of type String', $Name));
        }

        $this->attributes[$Name]['Current'] = $Value;
    }

    protected function SendDataToParent($Data)
    {
        //FIXME: We could validate something here
        $connectionID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        $interface = IPS\InstanceManager::getInstanceInterface($connectionID);
        // check if forwardDataFilter applys
        if (preg_match('/' . $interface->GetForwardDataFilter() . '/', $Data) == 1) {
            return $interface->ForwardData($Data);
        }
    }

    protected function SendDataToChildren($Data)
    {
        //FIXME: We could validate something here
        $ids = IPS_GetInstanceList();
        $return = [];
        foreach ($ids as $id) {
            if (IPS_GetInstance($id)['ConnectionID'] == $this->InstanceID) {
                $interface = IPS\InstanceManager::getInstanceInterface($id);
                // check if receiveDataFilter applys
                if (preg_match('/' . $interface->GetReceiveDataFilter() . '/', $Data) == 1) {
                    $singleReturn = $interface->ReceiveData($Data);
                    if ($singleReturn != '') {
                        $return[] = $singleReturn;
                    }
                }
            }
        }
        return $return;
    }

    protected function ConnectParent($ModuleID)
    {
        if (IPS_GetInstance($this->InstanceID)['ConnectionID'] == 0) {
            $ids = IPS_GetInstanceListByModuleID($ModuleID);
            if (count($ids) > 0) {
                IPS_ConnectInstance($this->InstanceID, $ids[0]);
                return;
            }

            //Let this function create our parent
            $this->RequireParent($ModuleID);
        }
    }

    protected function RequireParent($ModuleID)
    {
        if (IPS_GetInstance($this->InstanceID)['ConnectionID'] == 0) {
            $id = IPS_CreateInstance($ModuleID);
            $module = IPS_GetModule($ModuleID);
            IPS_SetName($id, $module['ModuleName']);
            IPS_ConnectInstance($this->InstanceID, $id);
        }
    }

    protected function ForceParent($ModuleID)
    {
        //Cleanup parent, if not correct
        $connectionID = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($connectionID != 0) {
            $instance = IPS_GetInstance($connectionID);
            if ($instance['ModuleInfo']['ModuleID'] != $ModuleID) {
                IPS_DisconnectInstance($this->InstanceID);

                //Only clean up instance, if no other instance is connected
                $ids = IPS_GetInstanceList();
                $inUse = false;
                foreach ($ids as $id) {
                    if (IPS_GetInstance($id)['ConnectionID'] == $connectionID) {
                        $inUse = true;
                        break;
                    }
                }
                if (!$inUse) {
                    IPS_DeleteInstance($connectionID);
                }
            }
        }

        //Let this function create our parent
        $this->RequireParent($ModuleID);
    }

    protected function SetStatus($Status)
    {
        IPS\InstanceManager::setStatus($this->InstanceID, $Status);
    }

    protected function GetStatus()
    {
        return IPS\InstanceManager::getStatus($this->InstanceID);
    }

    protected function SetSummary($Summary)
    {
        IPS\ObjectManager::setSummary($this->InstanceID, $Summary);
    }

    protected function SetBuffer($Name, $Data)
    {
        $this->buffer[$Name] = $Data;
    }

    protected function GetBufferList()
    {
        return array_keys($this->buffer);
    }

    protected function GetBuffer($Name)
    {
        if (isset($this->buffer[$Name])) {
            return $this->buffer[$Name];
        } else {
            return '';
        }
    }

    protected function SendDebug($Message, $Data, $Format)
    {
        IPS_SendDebug($this->InstanceID, $Message, $Data, $Format);
    }

    protected function GetMessageList()
    {
        // TODO: Some implementation here would be nice, but this suffices for the time being
        return [];
    }

    protected function RegisterMessage($SenderID, $Message)
    {
    }

    protected function UnregisterMessage($SenderID, $Message)
    {
    }

    protected function SetReceiveDataFilter($RequiredRegexMatch)
    {
        $this->receiveDataFilter = $RequiredRegexMatch;
    }

    protected function SetForwardDataFilter($RequiredRegexMatch)
    {
        $this->forwardDataFilter = $RequiredRegexMatch;
    }

    protected function RegisterReference(int $ID)
    {
        if (!in_array($ID, $this->references)) {
            $this->references[] = $ID;
        }
    }

    protected function UnregisterReference(int $ID)
    {
        if (in_array($ID, $this->references)) {
            array_splice($this->references, array_search($ID, $this->references), 1);
        }
    }

    protected function ReloadForm()
    {
    }

    protected function UpdateFormField($Field, $Parameter, $Value)
    {
    }

    protected function GetValue(string $Ident)
    {
        return GetValue(IPS_GetObjectIDByIdent($Ident, $this->InstanceID));
    }

    protected function SetValue(string $Ident, $Value)
    {
        return SetValue(IPS_GetObjectIDByIdent($Ident, $this->InstanceID), $Value);
    }

    protected function LogMessage($Message, $Type)
    {
    }

    private function RegisterProperty($Name, $DefaultValue, $Type)
    {
        $this->properties[$Name] = [
            'Type'    => $Type,
            'Default' => $DefaultValue,
            'Current' => $DefaultValue,
            'Pending' => $DefaultValue
        ];
    }

    private function RegisterAttribute($Name, $DefaultValue, $Type)
    {
        $this->attributes[$Name] = [
            'Type'    => $Type,
            'Default' => $DefaultValue,
            'Current' => $DefaultValue
        ];
    }

    private function RegisterVariable($Ident, $Name, $Type, $Profile, $Position)
    {
        if ($Profile != '') {
            //prefer system profiles
            if (IPS_VariableProfileExists('~' . $Profile)) {
                $Profile = '~' . $Profile;
            }
            if (!IPS_VariableProfileExists($Profile)) {
                throw new Exception('Profile with name ' . $Profile . ' does not exist');
            }
        }

        //search for already available variables with proper ident
        $vid = @IPS_GetObjectIDByIdent($Ident, $this->InstanceID);

        //properly update variableID
        if ($vid === false) {
            $vid = 0;
        }

        //we have a variable with the proper ident. check if it fits
        if ($vid > 0) {
            //check if we really have a variable
            if (!IPS_VariableExists($vid)) {
                throw new Exception('Ident with name ' . $Ident . ' is used for wrong object type');
            } //bail out
            //check for type mismatch
            if (IPS_GetVariable($vid)['VariableType'] != $Type) {
                //mismatch detected. delete this one. we will create a new below
                IPS_DeleteVariable($vid);
                //this will ensure, that a new one is created
                $vid = 0;
            }
        }

        //we need to create one
        if ($vid == 0) {
            $vid = IPS_CreateVariable($Type);

            //configure it
            IPS_SetParent($vid, $this->InstanceID);
            IPS_SetIdent($vid, $Ident);
            IPS_SetName($vid, $Name);
            IPS_SetPosition($vid, $Position);
            //IPS_SetReadOnly($vid, true);
        }

        //update variable profile. profiles may be changed in module development.
        //this update does not affect any custom profile choices
        IPS\VariableManager::setVariableProfile($vid, $Profile);

        return $vid;
    }
}
