<?php
/** @noinspection ALL */
declare(strict_types=1);

require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';
require_once __DIR__ . '/../libs/ConstHelper.php';

class AmazonEchoConfigurator extends IPSModule
{

    use DebugHelper;

    private const DEVICETYPES = [
        'A2E0SNTXJVT7WK' => ['name' => 'Fire TV'],
        'ADVBD696BHNV5' => ['name' => 'Fire TV Stick (1.Gen)'],
        'A2LWARUGJLBYEW' => ['name' => 'Fire TV Stick (2.Gen)'],
        'A2825NDLA7WDZV' => ['name' => 'App'],
        'AB72C64C86AW2'  => ['name' => 'Echo'],
        'A3S5BH2HU6VAYF' => ['name' => 'Echo Dot (2.Gen)'],
        'AILBSA2LNTOYL'  => ['name' => 'Reverb App'],
        'A15ERDAKK5HQQG' => ['name' => 'Sonos'],
        'A1NL4BVLQ4L3N3' => ['name' => 'Echo Show'],
        'A1DL2DVDQVK3Q'  => ['name' => 'App'],
        'A10A33FOX2NUBK' => ['name' => 'Echo Spot'],
        'A7WXQPH584YP'    => ['name' => 'Echo (2.Gen)'],
        'A2M35JJZWCQOMZ'    => ['name' => 'Echo Plus'],
        'A2IVLV5VM2W81'    => ['name' => 'Mobile Voice iOS'],
        'A2TF17PFR55MTB'    => ['name' => 'Mobile Voice Android'],
        'A3C9PE6TNYLTCH' => ['name' => 'Multiroom Musik-Gruppe']];

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // initiate buffer
        $this->SetBuffer($this->InstanceID . '-alexa_devices', '');
        $this->ConnectParent('{C7F853A4-60D2-99CD-A198-2C9025E2E312}');
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
            $instanceID  = 0;

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
                if ((IPS_GetInstance($EchoRemoteInstanceID)['ConnectionID'] === $MyParent)
                    && ($serialNumber === IPS_GetProperty($EchoRemoteInstanceID, 'Devicenumber'))) {
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
                'location'        => [
                    $this->Translate('Devices'),
                    'Amazon Echo',
                    'Amazon Remote'],
                'create'          => [
                    'moduleID'      => '{496AB8B5-396A-40E4-AF41-32F4C48AC90D}',
                    'configuration' => [
                        'Devicetype'   => $deviceType,
                        'Devicenumber' => $serialNumber]]];
        }

        return $config_list;
    }
    /**
     * Interne Funktion des SDK.
     *
     * @noinspection PhpMissingParentCallCommonInspection
     */

    public function GetConfigurationForm()
    {
        $Form   = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $Form['actions'][0]['values'] = $this->Get_ListConfiguration();

        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);

        return json_encode($Form);
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
