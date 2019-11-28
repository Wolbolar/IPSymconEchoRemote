<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/EchoBufferHelper.php';
require_once __DIR__ . '/../libs/EchoDebugHelper.php';

class AmazonEchoConfigurator extends IPSModule
{
    use EchoBufferHelper, EchoDebugHelper;

    private const DEVICETYPES = [
        'A2E0SNTXJVT7WK' => ['name' => 'Fire TV'],
        'ADVBD696BHNV5'  => ['name' => 'Fire TV Stick (1.Gen)'],
        'A2GFL5ZMWNE0PX' => ['name' => 'Fire TV Stick 4K (1.Gen)'],
        'AKPGW064GI9HE'  => ['name' => 'Fire TV Stick 4K'],
        'A2LWARUGJLBYEW' => ['name' => 'Fire TV Stick (2.Gen)'],
        'A21Z3CGI8UIP0F' => ['name' => 'Denon&Marantz (HEOS)'],
        'AVE5HX13UR5NO'  => ['name' => 'Zero Touch (Logitech)'],
        'AKOAGQTKAS9YB'  => ['name' => 'Amazon Echo Connect'],
        'A3GZUE7F9MEB4U' => ['name' => 'Sony WH-100XM3'],
        'A2J0R2SD7G9LPA' => ['name' => 'Lenovo P10'],
        'A2825NDLA7WDZV' => ['name' => 'App'],
        'AB72C64C86AW2'  => ['name' => 'Echo'],
        'A3S5BH2HU6VAYF' => ['name' => 'Echo Dot (2.Gen)'],
        'A32DOYMUN6DTXA' => ['name' => 'Echo Dot (3.Gen)'],
        'A1RABVCI4QCIKC' => ['name' => 'Echo Dot (3.Gen)'],
        'AILBSA2LNTOYL'  => ['name' => 'Reverb App'],
        'A15ERDAKK5HQQG' => ['name' => 'Sonos'],
        'A1NL4BVLQ4L3N3' => ['name' => 'Echo Show'],
        'A4ZP7ZC4PI6TO'  => ['name' => 'Echo Show 5'],
        'A30YDR2MK8HMRV' => ['name' => 'Echo Dot (3.Gen with Clock)'],
        'A1J16TEDOYCZTN' => ['name' => 'Amazon Tablet'],
        'A112LJ20W14H95' => ['name' => 'Media Display'],
        'A3L0T0VL9A921N' => ['name' => 'Tablet'],
        'A3R9S4ZZECZ6YL' => ['name' => 'Tablet'],
        'A1DL2DVDQVK3Q'  => ['name' => 'App'],
        'A1RTAM01W29CUP' => ['name' => 'PC App'],
        'A1H0CMF1XM0ZP4' => ['name' => 'Bose Soundtouch'],
        'A1WAR447VT003J' => ['name' => 'Yamaha AVR MusicCast'],
        'A10A33FOX2NUBK' => ['name' => 'Echo Spot'],
        'A7WXQPH584YP'   => ['name' => 'Echo (2.Gen)'],
        'A2M35JJZWCQOMZ' => ['name' => 'Echo Plus'],
        'A2IVLV5VM2W81'  => ['name' => 'Mobile Voice iOS'],
        'A2TF17PFR55MTB' => ['name' => 'Mobile Voice Android'],
        'A1JJ0KFC4ZPNJ3' => ['name' => 'Echo Input'],
        'A3V3VA38K169FO' => ['name' => 'Fire Tablet'],
        'A3C9PE6TNYLTCH' => ['name' => 'Multiroom Musik-Gruppe']];

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //the following Properties can be set in the configuration form
        $this->RegisterPropertyInteger('targetCategoryID', $this->GetDefaultTargetCategory());

        // initiate buffer
        $this->SetBuffer($this->InstanceID . '-alexa_devices', '');
        $this->ConnectParent('{C7F853A4-60D2-99CD-A198-2C9025E2E312}');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->RegisterReference($this->ReadPropertyInteger('targetCategoryID'));
    }

    private function GetDefaultTargetCategory(): int
    {
        $echoDevices = IPS_GetInstanceListByModuleID('{496AB8B5-396A-40E4-AF41-32F4C48AC90D}');
        if (isset($echoDevices[0])) {
            $parentId = IPS_GetParent($echoDevices[0]);
            if (IPS_GetObject($parentId)['ObjectType'] === 0) { //Category
                $defaultCategory = $parentId;
            } else {
                $defaultCategory = 0;
            }
        } else {
            $defaultCategory = 0;
        }

        return $defaultCategory;

    }

    /** Get Config Echo
     *
     * @return array
     */
    private function Get_ListConfiguration(): array
    {
        $EchoRemoteInstanceIDList = IPS_GetInstanceListByModuleID('{496AB8B5-396A-40E4-AF41-32F4C48AC90D}'); // Echo Remote Devices

        $devices_info = $this->SendData('GetDevices');
        if ($devices_info['http_code'] === 200) {
            $devices_JSON = $devices_info['body'];
            $this->SendDebug('Response IO:', $devices_JSON, 0);
            $this->SetBuffer($this->InstanceID . '-alexa_devices', $devices_JSON);
            if ($devices_JSON) {
                $devices = json_decode($devices_JSON, true)['devices'];
                $this->SendDebug('Echo Devices:', json_encode($devices), 0);
            }
        } else {
            $devices = null;
        }

        if (empty($devices)) {
            return [];
        }

        //prepare config list
        $config_list = [];

        foreach ($devices as $key => $device) {
            $instanceID = 0;

            $accountName = $device['accountName'];
            $this->SendDebug('Echo Device', 'account name: ' . $accountName, 0);

            $deviceAccountId = $device['deviceAccountId'];
            $this->SendDebug('Echo Device', 'device account id: ' . $deviceAccountId, 0);

            $deviceFamily = $device['deviceFamily'];
            $this->SendDebug('Echo Device', 'device family: ' . $deviceFamily, 0);

            $deviceType = $device['deviceType'];
            if (array_key_exists($deviceType, self::DEVICETYPES)) {
                $device_type_name = self::DEVICETYPES[$deviceType]['name'];
            } else {
                $device_type_name = 'unknown: ' . $deviceType;
                $this->LogMessage('Unknown DeviceType: ' . $deviceType, KL_WARNING);
            }
            $this->SendDebug('Echo Device', 'device type: ' . $deviceType . ', device type name: ' . $device_type_name, 0);

            $serialNumber = $device['serialNumber'];
            $this->SendDebug('Echo Device', 'serial number: ' . $serialNumber, 0);

            $MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
            foreach ($EchoRemoteInstanceIDList as $EchoRemoteInstanceID) {
                if (($serialNumber === IPS_GetProperty($EchoRemoteInstanceID, 'Devicenumber'))
                    && (IPS_GetInstance($EchoRemoteInstanceID)['ConnectionID'] === $MyParent)) {
                    $instanceID = $EchoRemoteInstanceID;
                }
            }

            $config_list[] = [
                'instanceID'      => $instanceID,
                'name'            => $accountName,
                'devicetype'      => $device_type_name,
                'devicefamily'    => $this->Translate($deviceFamily),
                'devicenumber'    => $serialNumber,
                'deviceaccountid' => $deviceAccountId,
                'create'          => [
                    'moduleID'      => '{496AB8B5-396A-40E4-AF41-32F4C48AC90D}',
                    'configuration' => [
                        'Devicetype'   => $deviceType,
                        'Devicenumber' => $serialNumber],
                    'location'      => $this->getPathOfCategory($this->ReadPropertyInteger('targetCategoryID'))]];

        }

        return $config_list;
    }

    private function getPathOfCategory(int $categoryId): array
    {
        if ($categoryId === 0) {
            return [];
        }

        $path[]   = IPS_GetName($categoryId);
        $parentId = IPS_GetObject($categoryId)['ParentID'];

        while ($parentId > 0) {
            $path[]   = IPS_GetName($parentId);
            $parentId = IPS_GetObject($parentId)['ParentID'];
        }

        return array_reverse($path);
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    protected function RegisterReference($ID)
    {
        if (method_exists('IPSModule', 'RegisterReference ')) {
            parent::RegisterReference($ID);
        }
    }

    /**
     * Interne Funktion des SDK.
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function GetConfigurationForm(): string
    {

        $Form['elements'][] = [
            'type'    => 'SelectCategory',
            'name'    => 'targetCategoryID',
            'caption' => 'Target Category'];
        $Form['actions'][]  = [
            'type'     => 'Configurator',
            'name'     => 'AmazonEchoConfiguration',
            'rowCount' => 20,
            'add'      => false,
            'delete'   => true,
            'sort'     => [
                'column'    => 'name',
                'direction' => 'ascending'],
            'columns'  => [
                ['caption' => 'device name', 'name' => 'name', 'width' => 'auto'],
                ['caption' => 'device type', 'name' => 'devicetype', 'width' => '250px'],
                ['caption' => 'device family', 'name' => 'devicefamily', 'width' => '350px'],
                ['caption' => 'device number', 'name' => 'devicenumber', 'width' => '250px'],
                ['caption' => 'device account id', 'name' => 'deviceaccountid', 'width' => '250px']],
            'values'   => $this->Get_ListConfiguration()];

        $jsonForm = json_encode($Form);
        $this->SendDebug('FORM', $jsonForm, 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return $jsonForm;
    }

    /** Sends Request to IO and get response.
     *
     * @param string      $method
     * @param array|null  $getfields
     * @param array|null  $postfields
     * @param null|string $url
     *
     * @return mixed
     */
    private function SendData(string $method, array $getfields = null, array $postfields = null, string $url = null)
    {
        $this->SendDebug(
            __FUNCTION__, 'Method: ' . $method . ', Getfields: ' . json_encode($getfields) . ', Postfields: ' . json_encode($postfields), 0
        );

        $Data['DataID'] = '{2BD76048-32BD-7D8B-AB6C-626D5C6D7253}';

        $Data['Buffer'] = ['method' => $method];

        if ($getfields !== null) {
            $Data['Buffer']['getfields'] = $getfields;
        }
        if ($postfields !== null) {
            $Data['Buffer']['postfields'] = $postfields;
        }
        if ($url !== null) {
            $Data['Buffer']['url'] = $url;
        }

        $ResultJSON = $this->SendDataToParent(json_encode($Data));
        $this->SendDebug(__FUNCTION__, 'Result: ' . $ResultJSON, 0);

        return json_decode($ResultJSON, true); //returns an array of http_code, body and header
    }
}
