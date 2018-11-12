<?php

//<editor-fold desc="declarations">
declare(strict_types=1);

require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';
require_once __DIR__ . '/../libs/ConstHelper.php';
//</editor-fold>

// Modul für Amazon Echo Remote

class EchoRemote extends IPSModule
{

    private const STATUS_INST_DEVICETYPE_IS_EMPTY = 210; // devicetype must not be empty.
    private const STATUS_INST_DEVICENUMBER_IS_EMPTY = 211; // devicenumber must not be empty

    //<editor-fold desc="class declarartions">
    private const HEADER = '
<head>
<meta charset="utf-8">
<title>Echo Info</title>
<style type="text/css">
.echo_mediaplayer {
	/*font-family: "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", "Verdana", "sans-serif";
	background-color: hsla(0,0%,100%,0.00);
	color: hsla(0,0%,100%,1.00);
	text-shadow: 1px 1px 3px hsla(0,0%,0%,1.00);*/
}
.echo_cover {
	display: block;
	float: left;
	padding: 8px;
}
.echo_mediaplayer .echo_cover #echocover {
	-webkit-box-shadow: 2px 2px 5px hsla(0,0%,0%,1.00);
	box-shadow: 2px 2px 5px hsla(0,0%,0%,1.00);
}
.echo_description {
	vertical-align: bottom;
	float: none;
	padding: 60px 11px 11px;
	margin-top: 0;
}
.echo_title {
	font-size: xx-large;
}
.echo_subtitle1 {
	font-size: large;
}
.echo_subtitle2 {
	font-size: large;
}
.shopping_item {
	font-size: large;
}
</style>
</head>
';

    //</editor-fold>

    private $customerID = '';

    private $ParentID   = 0;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.

        $this->RegisterPropertyString('Devicetype', '');
        $this->RegisterPropertyString('Devicenumber', '');
        $this->RegisterPropertyString(
            'TuneInStations', '[{"position":1,"station":"Hit Radio FFH","station_id":"s17490"},
            {"position":2,"station":"FFH Lounge","station_id":"s84483"},
            {"position":3,"station":"FFH Rock","station_id":"s84489"},
            {"position":4,"station":"FFH Die 80er","station_id":"s84481"},
            {"position":5,"station":"FFH iTunes Top 40","station_id":"s84486"},
            {"position":6,"station":"FFH Eurodance","station_id":"s84487"},
            {"position":7,"station":"FFH Soundtrack","station_id":"s97088"},
            {"position":8,"station":"FFH Die 90er","station_id":"s97089"},
            {"position":9,"station":"FFH Schlagerkult","station_id":"s84482"},
            {"position":10,"station":"FFH Leider Geil","station_id":"s254526"},
            {"position":11,"station":"The Wave - relaxing radio","station_id":"s140647"},
            {"position":12,"station":"hr3","station_id":"s57109"},
            {"position":13,"station":"harmony.fm","station_id":"s140555"},
            {"position":14,"station":"SWR3","station_id":"s24896"},
            {"position":15,"station":"Deluxe Lounge Radio","station_id":"s125250"},
            {"position":16,"station":"Lounge-Radio.com","station_id":"s17364"},
            {"position":17,"station":"Bayern 3","station_id":"s255334"},
            {"position":18,"station":"planet radio","station_id":"s2726"},
            {"position":19,"station":"YOU FM","station_id":"s24878"},
            {"position":20,"station":"1LIVE diggi","station_id":"s45087"},
            {"position":21,"station":"Fritz vom rbb","station_id":"s25005"},
            {"position":22,"station":"Hitradio \u00d63","station_id":"s8007"},
            {"position":23,"station":"radio ffn","station_id":"s8954"},
            {"position":24,"station":"N-JOY","station_id":"s25531"},
            {"position":25,"station":"bigFM","station_id":"s84203"},
            {"position":26,"station":"Deutschlandfunk","station_id":"s42828"},
            {"position":27,"station":"NDR 2","station_id":"s17492"},
            {"position":28,"station":"DASDING","station_id":"s20295"},
            {"position":29,"station":"sunshine live","station_id":"s10637"},
            {"position":30,"station":"MDR JUMP","station_id":"s6634"},
            {"position":31,"station":"Costa Del Mar","station_id":"s187256"},
            {"position":32,"station":"Antenne Bayern","station_id":"s139505"}]'
        );

        //        $this->RegisterPropertyString('TuneInStations', '');
        $this->RegisterPropertyInteger('updateinterval', 0);
        $this->RegisterPropertyBoolean('ExtendedInfo', false);
        $this->RegisterPropertyBoolean('AlarmInfo', false);
        $this->RegisterPropertyBoolean('ShoppingList', false);
        $this->RegisterPropertyBoolean('TaskList', false);
		$this->RegisterPropertyBoolean('Mute', false);

        $this->SetBuffer('CoverURL', '');
		$this->SetBuffer('Volume', '');
        $this->RegisterTimer('EchoUpdate', 0, 'EchoRemote_UpdateStatus(' . $this->InstanceID . ');');
        $this->RegisterTimer('EchoAlarm', 0, 'EchoRemote_RaiseAlarm(' . $this->InstanceID . ');');

        $this->ConnectParent('{C7F853A4-60D2-99CD-A198-2C9025E2E312}');

        //we will wait until the kernel is ready
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);

    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        if (!$this->ValidateConfiguration()) {
            return;
        }

        $this->RegisterParent();

        $this->RegisterVariables();

        //todo: nach der Testphase wieder entfernen
        $this->LogMessage('ApplyChanges durchgeführt', KL_MESSAGE);
    }

    private function HasActiveParent(): bool
    {
        if (($this->ParentID > 0) && (IPS_GetInstance($this->ParentID)['InstanceStatus'] === IS_ACTIVE)) {
            return true;
        }

        //todo: nach der Testphase wieder entfernen
        $this->LogMessage(
            'Parent (ConnectionID: ' . @IPS_GetInstance($this->InstanceID)['ConnectionID'] . ') ist nicht aktiv! (InstanceStatus = '
            . IPS_GetInstance($this->ParentID)['InstanceStatus'] . ')', KL_MESSAGE
        );
        return false;
    }

    /**
     * Ermittelt den Parent und verwaltet die Einträge des Parent im MessageSink
     * Ermöglicht es das Statusänderungen des Parent empfangen werden können.
     *
     * @access protected
     * @return int ID des Parent.
     */
    protected function RegisterParent(): int
    {
        $OldParentId = $this->ParentID;
        $ParentId    = @IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($ParentId !== $OldParentId) {
            if ($OldParentId > 0) {
                $this->UnregisterMessage($OldParentId, IM_CHANGESTATUS);
            }
            if ($ParentId > 0) {
                $this->RegisterMessage($ParentId, IM_CHANGESTATUS);
            } else {
                $ParentId = 0;
            }
            $this->ParentID = $ParentId;
        }
        return $ParentId;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {

        switch ($Message) {
            case IM_CHANGESTATUS:
                if ($Data[0] === IS_ACTIVE) {
                    $this->ApplyChanges();
                }
                break;

            case IPS_KERNELMESSAGE:
                if ($Data[0] === KR_READY) {
                    $this->ApplyChanges();
                }
                break;

            default:
                break;
        }
    }

    private function ValidateConfiguration(): bool
    {

        $this->SetTimerInterval('EchoAlarm', 0);
        if ($this->ReadPropertyString('Devicetype') === '') {
            $this->SetStatus(self::STATUS_INST_DEVICETYPE_IS_EMPTY);
        } elseif ($this->ReadPropertyString('Devicenumber') === '') {
            $this->SetStatus(self::STATUS_INST_DEVICENUMBER_IS_EMPTY);
        } else {
            $this->SetStatus(IS_ACTIVE);
            $this->SetEchoInterval();

            return true;
        }

        return false;
    }

    private function RegisterVariables(): void
    {
        if (!$this->HasActiveParent()) {
            return;
        }

        $device_info = $this->GetDeviceInfo();

        if (!$device_info) {
            return;
        }

        $this->SendDebug(__FUNCTION__, 'Device Info: ' . print_r($device_info, true), 0);
        $caps = $device_info['capabilities'];

        //Remote Variable
        $this->RegisterProfileAssociation(
            'Echo.Remote', 'Move', '', '', 0, 5, 0, 0, vtInteger, [
                             [0, $this->Translate('Rewind 30s'), 'HollowDoubleArrowLeft', -1],
                             [1, $this->Translate('Previous'), 'HollowLargeArrowLeft', -1],
                             [2, $this->Translate('Pause/Stop'), 'Sleep', -1],
                             [3, $this->Translate('Play'), 'Script', -1],
                             [4, $this->Translate('Next'), 'HollowLargeArrowRight', -1],
                             [5, $this->Translate('Forward 30s'), 'HollowDoubleArrowRight', -1]]
        );
        $this->RegisterVariableInteger('EchoRemote', 'Remote', 'Echo.Remote', 1);
        $this->EnableAction('EchoRemote');

        //Shuffle Variable
        if (in_array('AMAZON_MUSIC', $caps, true)) {
            $this->RegisterVariableBoolean('EchoShuffle', 'Shuffle', '~Switch', 2);
            IPS_SetIcon($this->GetIDForIdent('EchoShuffle'), 'Shuffle');
            $this->EnableAction('EchoShuffle');
        }

        //Repeat Variable
        if (in_array('AMAZON_MUSIC', $caps, true)) {
            $this->RegisterVariableBoolean('EchoRepeat', 'Repeat', '~Switch', 3);
            IPS_SetIcon($this->GetIDForIdent('EchoRepeat'), 'Repeat');
            $this->EnableAction('EchoRepeat');
        }

        //Repeat Variable
        if (in_array('VOLUME_SETTING', $caps, true)) {
            $this->RegisterVariableInteger('EchoVolume', 'Volume', '~Intensity.100', 4);
            $this->EnableAction('EchoVolume');
        }


        //Info Variable
        $this->RegisterVariableString('EchoInfo', 'Info', '~HTMLBox', 5);

        //Actions and TTS Variables
        if (in_array('FLASH_BRIEFING', $caps, true)) {
            $this->RegisterProfileAssociation(
                'Echo.Actions', 'Move', '', '', 0, 5, 0, 0, vtInteger, [
                                  [0, $this->Translate('Weather'), '', -1],
                                  [1, $this->Translate('Traffic'), '', -1],
                                  [2, $this->Translate('Flash Briefing'), '', -1],
                                  [3, $this->Translate('Good morning'), '', -1],
                                  [4, $this->Translate('Sing a song'), '', -1],
                                  [5, $this->Translate('Tell a story'), '', -1]]
            );
            $this->RegisterVariableInteger('EchoActions', 'Actions', 'Echo.Actions', 6);
            $this->EnableAction('EchoActions');

            $this->RegisterVariableString('EchoTTS', 'Text to Speech', '', 7);
            $this->EnableAction('EchoTTS');
        }


        //TuneIn Variable
        if (in_array('TUNE_IN', $caps, true)) {
            $devicenumber = $this->ReadPropertyString('Devicenumber');
            if ($devicenumber !== '') {
                $associations = [];
                foreach (json_decode($this->ReadPropertyString('TuneInStations'), true) as $tuneInStation) {
                    $associations[] = [$tuneInStation['position'], $tuneInStation['station'], '', -1];
                }
                $profileName = 'Echo.TuneInStation.' . $devicenumber;
                $this->RegisterProfileAssociation($profileName, 'Music', '', '', 0, 0, 0, 0, vtInteger, $associations);
                $this->RegisterVariableInteger('EchoTuneInRemote_' . $devicenumber, 'TuneIn Radio', $profileName, 5);
                $this->EnableAction('EchoTuneInRemote_' . $devicenumber);
            }
        }

        //Extended Info
        if ($this->ReadPropertyBoolean('ExtendedInfo')) {
            $this->RegisterVariableString('Title', $this->Translate('Title'), '', 8);
            $this->RegisterVariableString('Subtitle_1', $this->Translate('Subtitle 1'), '', 9);
            $this->RegisterVariableString('Subtitle_2', $this->Translate('Subtitle 2'), '', 10);
            $this->CreateMediaImage('MediaImageCover', 11);
        }

		//Mute
		if ($this->ReadPropertyBoolean('Mute')) {
			//Mute Variable
			$this->RegisterProfileAssociation(
				'Echo.Remote.Mute', 'Speaker', '', '', 0, 1, 0, 0, vtBoolean, [
					[false, $this->Translate('Unmute'), 'Speaker', -1],
					[true, $this->Translate('Mute'), 'Speaker', -1]]
			);
			$this->RegisterVariableBoolean('Mute', $this->Translate('Mute'), 'Echo.Remote.Mute', 13);
			$this->EnableAction('Mute');
		}

        //support of alarm
        if ($this->ReadPropertyBoolean('AlarmInfo')) {
            $this->RegisterVariableInteger('nextAlarmTime', $this->Translate('next Alarm'), '~UnixTimestamp', 12);
            $this->RegisterVariableInteger('lastAlarmTime', $this->Translate('last Alarm'), '~UnixTimestamp', 13);
        }

        //support of ShoppingList
        if ($this->ReadPropertyBoolean('ShoppingList')) {
            $this->RegisterVariableString('ShoppingList', $this->Translate('ShoppingList'), '~HTMLBox', 12);
        }

        //support of TaskList
        if ($this->ReadPropertyBoolean('TaskList')) {
            $this->RegisterVariableString('TaskList', $this->Translate('TaskList'), '~HTMLBox', 12);
        }


    }

    private function GetDeviceInfo()
    {
        $this->SendDebug(__FUNCTION__, 'started', 0);

        //fetch all devices
        $result = $this->SendData('GetDevices');

        if ($result['http_code'] !== 200) {
            return false;
        }


        $devices_arr = json_decode($result['body'], true)['devices'];

        //search device with my type and serial number
        $myDevice = null;
        foreach ($devices_arr as $key => $device) {
            if (($device['deviceType'] === $this->GetDevicetype()) && ($device['serialNumber'] === $this->GetDevicenumber())) {
                return $device;
                break;
            }
        }

        return false;

    }

    /** Sends Request to IO and get response.
     *
     * @param string      $method
     * @param array|null  $getfields
     * @param array|null  $postfields
     * @param null|string $url
     *
     * @param null        $optpost
     * @param null        $automation
     *
     * @param null        $additionalData
     *
     * @return mixed
     */
    private function SendData(string $method, array $getfields = null, array $postfields = null, $url = null, $optpost = null, $automation = null,
                              $additionalData = null)
    {
        $this->SendDebug(
            __FUNCTION__,
            'Method: ' . $method . ', Getfields: ' . json_encode($getfields) . ', Postfields: ' . json_encode($postfields) . ', URL: ' . $url
            . ', Option Post: ' . (int) $optpost . ', Automation: ' . json_encode($automation), 0
        );

        $Data['DataID'] = '{8E187D67-F330-2B1D-8C6E-B37896D7AE3E}';

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
        if ($optpost !== null) {
            $Data['Buffer']['optpost'] = $optpost;
        }
        if ($automation !== null) {
            $Data['Buffer']['automation'] = $automation;
        }
        if ($additionalData !== null) {
            $Data['Buffer']['additionalData'] = $additionalData;
        }

        $ResultJSON = $this->SendDataToParent(json_encode($Data));
        if ($ResultJSON) {
            $this->SendDebug(__FUNCTION__, 'Result: ' . json_encode($ResultJSON), 0);

            return json_decode($ResultJSON, true); //returns an array of http_code, body and header
        }

        return false;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    public function RequestAction($Ident, $Value)
    {
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $this->SendDebug('Echo Remote:', 'Request Action trigger device ' . $devicenumber . ' by Ident ' . $Ident, 0);
        if ($Ident === 'EchoRemote') {
            switch ($Value) {
                case 0: // Rewind30s
                    $this->Rewind30s();
                    break;
                case 1: // Previous
                    $this->Previous();
                    break;
                case 2: // Pause / Stop
                    $this->Pause();
                    break;
                case 3: // Play
                    $this->Play();
                    break;
                case 4: // Next
                    $this->Next();
                    break;
                case 5: // Forward30s
                    $this->Forward30s();
                    break;
            }
        }
        if ($Ident === 'EchoShuffle') {
            $this->Shuffle($Value);
        }
        if ($Ident === 'EchoRepeat') {
            $this->Repeat($Value);
        }
        if ($Ident === 'EchoVolume') {
            $this->SetVolume($Value);
        }
        if ($Ident === 'EchoTuneInRemote_' . $devicenumber) {
            $stationid = $this->GetTuneInStationID($Value);
            $this->TuneIn($stationid);
        }
        if ($Ident === 'EchoActions') {
            switch ($Value) {
                case 0: // Weather
                    $this->Weather();
                    break;
                case 1: // Traffic
                    $this->Traffic();
                    break;
                case 2: // Flashbriefing
                    $this->FlashBriefing();
                    break;
                case 3: // Good Morning
                    $this->GoodMorning();
                    break;
                case 4: // Sing a song
                    $this->SingASong();
                    break;
                case 5: // tell a story
                    $this->TellStory();
                    break;
            }
        }
        if ($Ident === 'EchoTTS') {
            $this->TextToSpeech($Value);
        }
		if ($Ident === 'Mute') {
			if($Value)
			{
				$this->Mute(true);
			}
			else
			{
				$this->Mute(false);
			}

		}
    }

    /** register profiles
     *
     *
     * @param $Name
     * @param $Icon
     * @param $Prefix
     * @param $Suffix
     * @param $MinValue
     * @param $MaxValue
     * @param $StepSize
     * @param $Digits
     * @param $Vartype
     */
    private function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype): void
    {

        if (IPS_VariableProfileExists($Name)) {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] !== $Vartype) {
                $this->SendDebug('Profile', 'Variable profile type does not match for profile ' . $Name, 0);
            }
        } else {
            IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string
            $this->SendDebug('Variablenprofil angelegt', $Name, 0);
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues(
            $Name, $MinValue, $MaxValue, $StepSize
        ); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
        $this->SendDebug(
            'Variablenprofil konfiguriert',
            'Name: ' . $Name . ', Icon: ' . $Icon . ', Prefix: ' . $Prefix . ', $Suffix: ' . $Suffix . ', Digits: ' . $Digits . ', MinValue: '
            . $MinValue . ', MaxValue: ' . $MaxValue . ', StepSize: ' . $StepSize, 0
        );
    }

    /** register profile association
     *
     * @param $Name
     * @param $Icon
     * @param $Prefix
     * @param $Suffix
     * @param $MinValue
     * @param $MaxValue
     * @param $Stepsize
     * @param $Digits
     * @param $Vartype
     * @param $Associations
     */
    private function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype,
                                                $Associations): void
    {
        if (is_array($Associations) && count($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        }
        $this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

        if (is_array($Associations)) {
            //zunächst werden alte Assoziationen gelöscht
            //bool IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
            foreach (IPS_GetVariableProfile($Name)['Associations'] as $Association) {
                IPS_SetVariableProfileAssociation($Name, $Association['Value'], '', '', -1);
            }

            //dann werden die aktuellen eingetragen
            foreach ($Associations as $Association) {
                IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
            }
        } else {
            $Associations = $this->$Associations;
            foreach ($Associations as $code => $association) {
                IPS_SetVariableProfileAssociation($Name, $code, $this->Translate($association), $Icon, -1);
            }
        }
    }

    private function SetEchoInterval(): void
    {
        $echointerval = $this->ReadPropertyInteger('updateinterval');
        $interval     = $echointerval * 1000;
        $this->SetTimerInterval('EchoUpdate', $interval);
    }

    private function Covername(): string
    {
        $name    = IPS_GetName($this->InstanceID);
        $search  = ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', ' '];
        $replace = ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '_'];
        return 'echocover' . str_replace($search, $replace, $name);
    }

    private function CreateMediaImage(string $ident, int $position): void
    {
        $covername = $this->Covername();
        $picurl    = $this->GetBuffer('CoverURL'); // Cover URL
        $ImageFile = IPS_GetKernelDir() . 'media' . DIRECTORY_SEPARATOR . $covername . '.png';  // Image-Datei

        $MediaID = @$this->GetIDForIdent($ident);
        if ($MediaID === false) {
            if ($picurl) {
                $Content = base64_encode(file_get_contents($picurl)); // Bild Base64 codieren
                // convert to png
                imagepng(imagecreatefromstring(file_get_contents($picurl)), $ImageFile); // save PNG
            } else {
                // set transparent image
                $Content =
                    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='; // Transparent png 1x1 Base64
                $data    = base64_decode($Content);
                file_put_contents($ImageFile, $data); // save PNG
            }
            $MediaID = IPS_CreateMedia(1);                  // Image im MedienPool anlegen
            IPS_SetParent($MediaID, $this->InstanceID); // Medienobjekt einsortieren unter der Sonos Instanz
            IPS_SetIdent($MediaID, $ident);
            IPS_SetPosition($MediaID, $position);
            IPS_SetMediaCached($MediaID, true);
            // Das Cachen für das Mediaobjekt wird aktiviert.
            // Beim ersten Zugriff wird dieses von der Festplatte ausgelesen
            // und zukünftig nur noch im Arbeitsspeicher verarbeitet.
            IPS_SetMediaFile($MediaID, $ImageFile, false);    // Image im MedienPool mit Image-Datei verbinden
            IPS_SetName($MediaID, 'Cover'); // Medienobjekt benennen
            //IPS_SetInfo($MediaID, $name);
            IPS_SetMediaContent($MediaID, $Content);  // Base64 codiertes Bild ablegen
            IPS_SendMediaEvent($MediaID); //aktualisieren
        }
    }

    /** GetTuneInStationID
     *
     * @param $preset
     *
     * @return string
     */
    private function GetTuneInStationID(int $preset): string
    {
        $list_json = $this->ReadPropertyString('TuneInStations');
        $list      = json_decode($list_json, true);
        $stationid = '';
        foreach ($list as $station) {
            if ($preset === $station['position']) {
                $station_name = $station['station'];
                $stationid    = $station['station_id'];
                $this->SendDebug(__FUNCTION__, 'station name: ' . $station_name, 0);
                $this->SendDebug(__FUNCTION__, 'station id: ' . $stationid, 0);
            }
        }
        return $stationid;
    }

    /** GetTuneInStationPreset
     *
     * @param $guideId
     *
     * @return bool|int
     */
    private function GetTuneInStationPresetPosition(string $guideId)
    {
        $presetPosition = false;
        $list_json      = $this->ReadPropertyString('TuneInStations');
        $list           = json_decode($list_json, true);
        foreach ($list as $station) {
            if ($guideId === $station['station_id']) {
                $presetPosition = $station['position'];
                $station_name   = $station['station'];
                $stationid      = $station['station_id'];
                $this->SendDebug(__FUNCTION__, 'preset position: ' . $presetPosition, 0);
                $this->SendDebug(__FUNCTION__, 'station name: ' . $station_name, 0);
                $this->SendDebug(__FUNCTION__, 'station id: ' . $stationid, 0);
                break;
            }
        }
        return $presetPosition;
    }


    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
     *
     *
     */

    public function RaiseAlarm(): void
    {
        //Alarmzeit setzen
        $oldAlarmTime = $this->GetValue('nextAlarmTime');
        $this->SetValue('lastAlarmTime', $oldAlarmTime);
        $this->SendDebug(__FUNCTION__, 'lastAlarmTime set to ' . $oldAlarmTime . ' (' . date(DATE_RSS, $oldAlarmTime) . ')', 0);

        //Timer deaktivieren
        $this->SetTimerInterval('EchoAlarm', 0);

        //alte Zeit wird nicht gelöscht, da Alexa den Wecker erst deaktiviert, wenn er abgelaufen ist
    }

    /** Rewind 30s
     *
     * @return array|string
     */
    public function Rewind30s()
    {
        $result = $this->PlayCommand('RewindCommand');
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoRemote', 0);
            return true;
        }
        return false;
    }

    /** Previous
     *
     * @return array|string
     */
    public function Previous()
    {
        $result = $this->PlayCommand('PreviousCommand');
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoRemote', 1);
            return true;
        }
        return false;
    }

    /** Pause
     *
     * @return array|string
     */
    public function Pause()
    {
        $result = $this->PlayCommand('PauseCommand');
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoRemote', 2);
            return true;
        }
        return false;
    }

    /** Play
     *
     * @return array|string
     */
    public function Play()
    {
        $result = $this->PlayCommand('PlayCommand');
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoRemote', 3);
            return true;
        }
        return false;
    }

    /** Next
     *
     * @return array|string
     */
    public function Next()
    {
        $result = $this->PlayCommand('NextCommand');
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoRemote', 4);
            return true;
        }
        return false;
    }

    /** JumpToMediaId
     *
     * @param string $mediaID
     *
     * @return array|string
     */
    public function JumpToMediaId(string $mediaID)
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'type'    => 'JumpCommand',
            'mediaId' => $mediaID];

        $result = $this->SendData('NpCommand', $getfields, $postfields);

        return ($result['http_code'] === 200);
    }

    /** Forward 30s
     *
     * @return array|string
     */
    public function Forward30s()
    {
        $result = $this->PlayCommand('ForwardCommand');
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoRemote', 5);
            return true;
        }
        return false;
    }

    /** VolumeUp
     *
     * @return array|string
     */
    public function VolumeUp()
    {
        return $this->SetVolume((int) $this->GetValue('EchoVolume') + 1);
    }

    /** VolumeDown
     *
     * @return array|string
     */
    public function VolumeDown()
    {
        return $this->SetVolume((int) $this->GetValue('EchoVolume') - 1);
    }

    /** IncreaseVolume
     *
     * @param int $increment
     *
     * @return array|string
     */
    public function IncreaseVolume(int $increment)
    {
        return $this->SetVolume((int) $this->GetValue('EchoVolume') + $increment);
    }

    /** DecreaseVolume
     *
     * @param int $increment
     *
     * @return array|string
     */
    public function DecreaseVolume(int $increment)
    {
        return $this->SetVolume($this->GetValue('EchoVolume') - $increment);
    }

    /** SetVolume
     *
     * @param int $volume
     *
     * @return array|string
     */
    public function SetVolume(int $volume) // integer 0 bis 100
    {
        if ($volume > 100) {
            $volume = 100;
        }
        if ($volume < 0) {
            $volume = 0;
        }

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'type'        => 'VolumeLevelCommand',
            'volumeLevel' => $volume];

        $result = $this->SendData('NpCommand', $getfields, $postfields);
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoVolume', $volume);
            return true;
        }
        return false;
    }

	/** Mute / unmute
	 *
	 * @param bool $mute
	 *
	 * @return array|string
	 */
	public function Mute(bool $mute)
	{
		if ($mute) {
			$this->SetBuffer("Volume", "0");
			$volume = 0;
		}
		if (!$mute) {
			$last_volume = $this->GetBuffer("Volume");
			if($last_volume == "")
			{
				$volume = 30;
			}
			else{
				$volume = $last_volume;
			}

		}

		$getfields = [
			'deviceSerialNumber' => $this->GetDevicenumber(),
			'deviceType'         => $this->GetDevicetype()];

		$postfields = [
			'type'        => 'VolumeLevelCommand',
			'volumeLevel' => $volume];

		$result = $this->SendData('NpCommand', $getfields, $postfields);
		if ($result['http_code'] === 200) {
			$this->SetValue('EchoVolume', $volume);
			return true;
		}
		return false;
	}

    /** Get Player Status Information
     *
     * @return array|string
     */
    public function GetPlayerInformation()
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype(),
            'screenWidth'        => 1680 //to get url of big picture
        ];

        $result = $this->SendData('NpPlayer', $getfields);
        if ($result['http_code'] === 200) {
            //$this->SetValue("EchoVolume", $volume);
            return json_decode($result['body'], true);
        }
        return false;

    }

    /** Get Player Status Information
     *
     * @return array|string
     */
    public function GetQueueInformation()
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $result = $this->SendData('NpQueue', $getfields);
        if ($result['http_code'] === 200) {
            //$this->SetValue("EchoVolume", $volume);
            return json_decode($result['body'], true);
        }
        return false;

    }

    /** Shuffle
     *
     * @param bool $value
     *
     * @return array|string
     */
    public function Shuffle(bool $value)
    {
        $this->SendDebug('Echo Remote:', 'Request Action Shuffle', 0);

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'type'    => 'ShuffleCommand',
            'shuffle' => $value ? 'true' : 'false'];

        $result = $this->SendData('NpCommand', $getfields, $postfields);
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoShuffle', $value);
            return true;
        }
        return false;
    }

    /** Repeat
     *
     * @param bool $value
     *
     * @return array|string
     */
    public function Repeat(bool $value)
    {
        $this->SendDebug('Echo Remote:', 'Request Action Repeat', 0);

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'type'   => 'RepeatCommand',
            'repeat' => $value ? 'true' : 'false'];

        $result = $this->SendData('NpCommand', $getfields, $postfields);
        if ($result['http_code'] === 200) {
            $this->SetValue('EchoRepeat', $value);
            return true;
        }
        return false;
    }

    /** PlayCommand
     *
     * @param $commandType
     *
     * @return array|string
     */
    private function PlayCommand(string $commandType)
    {
        $this->SendDebug(__FUNCTION__, 'CommandType: ' . $commandType, 0);

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'type' => $commandType];

        return $this->SendData('NpCommand', $getfields, $postfields);
    }

    /** play TuneIn radio station
     *
     * @param string $guideId
     *
     * @return bool
     */
    public function TuneIn(string $guideId): bool
    {

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'contentType'          => 'station',
            'guideId'              => $guideId,
            'mediaOwnerCustomerId' => $this->GetCustomerID()];


        $result = $this->SendData('TuneinQueueandplay', $getfields, $postfields);

        $presetPosition = $this->GetTuneInStationPresetPosition($guideId);
        if ($presetPosition) {
            $this->SetValue('EchoTuneInRemote_' . $this->ReadPropertyString('Devicenumber'), $presetPosition);
        }
        if ($result['http_code'] === 200) {
            sleep(4); //warten, bis das Umschalten erfolgt ist
            return $this->UpdateStatus();
        }
        return false;
    }

    /** GetMediaState
     *
     * @return mixed
     */
    public function GetMediaState()
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $result = (array) $this->SendData('MediaState', $getfields);


        //$url = 'https://{AlexaURL}/api/media/state?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype()
        //       . '&queueId=0e7d86f5-d5a4-4a3a-933e-5910c15d9d4f&shuffling=false&firstIndex=1&lastIndex=1&screenWidth=1920&_=1495289082979';


        if ($result['http_code'] === 200) {
            return json_decode($result['body'], true);
        }

        return false;
    }

    /** GetNoticications
     *
     * @return mixed
     */
    public function GetNotifications()
    {
        $result = (array) $this->SendData('Notifications');

        if ($result['http_code'] === 200) {
            return json_decode($result['body'], true)['notifications'];
        }

        return false;
    }

    /** GetToDos
     *
     * @param string $type      : one of 'SHOPPING_ITEM' or 'TASK'
     * @param bool   $completed true: completed todos are returned
     *                          false: not completed todos are returned
     *                          null: all todos are returned
     *
     * @return bool
     */
    public function GetToDos(string $type, bool $completed = null): ?array
    {
        $getfields = [
            'type' => $type, //SHOPPING_ITEM or TASK,
            'size' => 500];

        if ($completed !== null) {
            $getfields['completed'] = $completed ? 'true' : 'false';
        }

        $result = (array) $this->SendData('ToDos', $getfields);

        if ($result['http_code'] === 200) {
            return json_decode($result['body'], true)['values'];
        }

        return false;
    }

    /** Play TuneIn station by present
     *
     * @param int $preset
     *
     * @return bool
     */
    public function TuneInPreset(int $preset): ?bool
    {
        $station = $this->GetTuneInStationID($preset);
        if ($station !== '') {
            return $this->TuneIn($station);
        }

        trigger_error('unknown preset: ' . $preset);
        return false;

    }

    /** TextToSpeech
     *
     * @param string $tts
     *
     * @return array|string
     */
    public function TextToSpeech(string $tts): bool
    {
        return $this->PlaySequenceCmd('Alexa.Speak', $tts);
    }

    /**
     *
     * Weather Forcast
     */
    public function Weather(): bool
    {
        return $this->PlaySequenceCmd('Alexa.Weather.Play');
    }

    /**
     *
     * Traffic
     */
    public function Traffic(): bool
    {
        return $this->PlaySequenceCmd('Alexa.Traffic.Play');
    }

    /**
     *
     * Flash briefing
     */
    public function FlashBriefing(): bool
    {
        return $this->PlaySequenceCmd('Alexa.FlashBriefing.Play');
    }

    /**
     *
     * Goodmorning
     */
    public function GoodMorning(): bool
    {
        return $this->PlaySequenceCmd('Alexa.GoodMorning.Play');
    }

    /**
     *
     * Sing a song
     */
    public function SingASong(): bool
    {
        return $this->PlaySequenceCmd('Alexa.SingASong.Play');
    }

    /**
     *
     * Tell a story
     */
    public function TellStory(): bool
    {
        return $this->PlaySequenceCmd('Alexa.TellStory.Play');
    }

    /** PlaySequenceCmd
     *
     * @param string $SEQUENCECMD
     * @param string $tts
     *
     * @return bool
     */
    private function PlaySequenceCmd(string $SEQUENCECMD, string $tts = null): bool
    {

        $postfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype(),
            'customerId'         => $this->GetCustomerID(),
            'type'               => $SEQUENCECMD];

        if ($tts !== null) {
            $postfields['textToSpeak'] = $tts;
        }

        $result = (array) $this->SendData('BehaviorsPreview', null, $postfields);

        return ($result['http_code'] === 200);


    }

    private function GetAutomation($utterance, $automations)
    {
        foreach ($automations as $automation) {
            foreach ($automation['triggers'] as $trigger) {
                if ($trigger['payload']['utterance'] === $utterance) {
                    return $automation;
                }
            }
        }

        return false;
    }

    /**
     *
     * @param string $utterance
     *
     * @return bool
     */
    public function StartAlexaRoutine(string $utterance): bool
    {

        //get all Automations
        $result = (array) $this->SendData('BehaviorsAutomations');

        if ($result['http_code'] !== 200) {
            return false;
        }

        $automations = json_decode($result['body'], true);

        //search Automation of utterance
        $automation = $this->GetAutomation($utterance, $automations);
        if ($automation) {
            //play automation
            $postfields = [
                'deviceSerialNumber' => $this->GetDevicenumber(),
                'deviceType'         => $this->GetDevicetype()];

            $result = (array) $this->SendData('BehaviorsPreviewAutomation', null, $postfields, null, null, $automation);
            return ($result['http_code'] === 200);
        }

        return false;
    }

    /** List all echo devices with connected Bluetooth devices
     *
     * @return mixed
     */
    private function ListBluetooth()
    {
        $result = (array) $this->SendData('Bluetooth');

        if ($result['http_code'] === 200) {
            $data = json_decode($result['body'], true);
            return $data['bluetoothStates'];
        }

        return false;
    }

    /** List paired bluetooth devices
     *
     * @return string
     */
    public function ListPairedBluetoothDevices(): ?string
    {
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $devices = $this->ListBluetooth();
        if ($devices) {
            $pairedDeviceList = '';
            foreach ($devices as $key => $device) {
                if ($devicenumber === $device['deviceSerialNumber']) {
                    $pairedDeviceList = $device['pairedDeviceList'];
                }
            }
            return $pairedDeviceList;

        }

        return null;
    }

    public function ConnectBluetooth(string $bluetooth_address): bool
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = ['bluetoothDeviceAddress' => $bluetooth_address];
        $result     = (array) $this->SendData('BluetoothPairSink', $getfields, $postfields);

        return ($result['http_code'] === 200);
    }

    public function DisconnectBluetooth(): bool
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $result = (array) $this->SendData('BluetoothDisconnectSink', $getfields);

        return ($result['http_code'] === 200);
    }

    /** Get State Tune In
     *
     * @return bool
     */
    public function UpdateStatus(): bool
    {

        if (!$result = $this->GetPlayerInformation()) {
            return false;
        }

        $playerInfo = $result['playerInfo'];

        switch ($playerInfo['state']) {
            case 'PLAYING':
                $this->SetValue('EchoRemote', 3);
                break;

            case null:
            case 'PAUSED':
            case 'IDLE':
                $this->SetValue('EchoRemote', 2);
                break;

            default:
                trigger_error('Instanz #'.$this->InstanceID.' - Unexpected state: ' . $playerInfo['state']);
        }

        if (isset($playerInfo['mainArt']['url'])) {
            $imageurl = $playerInfo['mainArt']['url'];
        } else {
            $imageurl = null;
        }
        $this->SetStatePage(
            $imageurl, $playerInfo['infoText']['title'], $playerInfo['infoText']['subText1'], $playerInfo['infoText']['subText2']
        );

        if (isset($playerInfo['transport']['repeat'])) {
            switch ($playerInfo['transport']['repeat']) {
                case null:
                    break;
                case 'HIDDEN':
                case 'ENABLED':
                case 'DISABLED':
                    $this->SetValue('EchoRepeat', false);
                    break;

                case 'SELECTED':
                    $this->SetValue('EchoRepeat', true);
                    break;

                default:
                    trigger_error('Instanz #'.$this->InstanceID.' - Unexpected repeat value: ' . $playerInfo['transport']['repeat']);
            }
        }

        if (isset($playerInfo['transport']['shuffle'])) {
            switch ($playerInfo['transport']['shuffle']) {
                case null:
                    break;
                case 'HIDDEN':
                case 'ENABLED':
                case 'DISABLED':
                    $this->SetValue('EchoShuffle', false);
                    break;

                case 'SELECTED':
                    $this->SetValue('EchoShuffle', true);
                    break;

                default:
                    trigger_error('Instanz #'.$this->InstanceID.' - Unexpected shuffle value: ' . $playerInfo['transport']['shuffle']);
            }
        }

        if ($playerInfo['volume']['volume'] !== null) {
            $this->SetValue('EchoVolume', $playerInfo['volume']['volume']);
        }

        //update Alarm
        if ($this->ReadPropertyBoolean('AlarmInfo')) {
            $notifications = $this->GetNotifications();
            if ($notifications === false) {
                return false;
            }

            $this->SetAlarm($notifications);
        }

        //update ShoppingList
        if ($this->ReadPropertyBoolean('ShoppingList')) {
            $shoppingList = (array) $this->GetToDos('SHOPPING_ITEM', false);
            if ($shoppingList === false) {
                return false;
            }

            $html = $this->GetListPage($shoppingList);
            //neuen Wert setzen.
            if ($html !== $this->GetValue('ShoppingList')) {
                $this->SetValue('ShoppingList', $html);
            }
        }

        //update TaskList
        if ($this->ReadPropertyBoolean('TaskList')) {
            $taskList = (array) $this->GetToDos('TASK', false);
            if ($taskList === false) {
                return false;
            }

            $html = $this->GetListPage($taskList);
            //neuen Wert setzen.
            if ($html !== $this->GetValue('TaskList')) {
                $this->SetValue('TaskList', $html);
            }
        }

        return true;
    }

    private function SetStatePage(string $imageurl = null, string $title = null, string $subtitle_1 = null, string $subtitle_2 = null): void
    {
        $html = '<!doctype html>
<html>' . self::HEADER . '
<body>
<main class="echo_mediaplayer1">
  <section class="echo_cover"><img src="' . $imageurl . '" width="145" height="145" id="echocover"></section>
  <section class="echo_description">
    <div class="echo_title">' . $title . '</div>
    <div class="echo_subtitle1">' . $subtitle_1 . '</div>
    <div class="echo_subtitle2">' . $subtitle_2 . '</div>
  </section>
</main>
</body>
</html>';
        $this->SetValue('EchoInfo', $html);

        if ($this->ReadPropertyBoolean('ExtendedInfo')) {
            $this->SetValue('Title', $title);
            $this->SetValue('Subtitle_1', $subtitle_1);
            $this->SetValue('Subtitle_2', $subtitle_2);
            $this->SetBuffer('CoverURL', $imageurl);
            if ($imageurl !== null) {
                $this->RefreshCover($imageurl);
            }
        }
    }

    private function RefreshCover(string $imageurl): void
    {
        $Content = base64_encode(file_get_contents($imageurl)); // Bild Base64 codieren
        IPS_SetMediaContent($this->GetIDForIdent('MediaImageCover'), $Content);  // Base64 codiertes Bild ablegen
        IPS_SendMediaEvent($this->GetIDForIdent('MediaImageCover')); //aktualisieren
    }

    private function SetAlarm(array $notifications): void
    {
        $alarmTime = 0;
        $nextAlarm = $this->GetValue('nextAlarmTime');

        // if the alarm time has already passed, it is set to 0
        if ($nextAlarm < (time() - 2 * 60)) {
            $nextAlarm = 0;
        }

        foreach ($notifications as $notification) {
            if (($notification['deviceSerialNumber'] === IPS_GetProperty($this->InstanceID, 'Devicenumber')) && ($notification['type'] === 'Alarm')
                && ($notification['status'] === 'ON')) {

                $alarmTime = strtotime($notification['originalDate'] . ' ' . $notification['originalTime']);

                if ($nextAlarm === 0) {
                    $nextAlarm = $alarmTime;
                } else {
                    $nextAlarm = min($nextAlarm, $alarmTime);
                }
            }
        }

        if ($alarmTime === 0) {
            $nextAlarm        = 0;
            $timerIntervalSec = 0;
        } else {
            $timerIntervalSec = $nextAlarm - time();
        }

        if ($nextAlarm !== $this->GetValue('nextAlarmTime')) {
            //neuen Wert und Timer setzen.
            $this->SetValue('nextAlarmTime', $nextAlarm);
            $this->SendDebug(__FUNCTION__, 'nextAlarmTime set to ' . $nextAlarm . ' (' . date(DATE_RSS, $nextAlarm) . ')', 0);

            $this->SetTimerInterval('EchoAlarm', $timerIntervalSec * 1000);
            $this->SendDebug(__FUNCTION__, 'Timer EchoAlarm is set to ' . $timerIntervalSec . 's', 0);
        }

    }

    private function GetListPage(array $Items): string
    {

        $html = '<!doctype html>
<html>' . self::HEADER . '
<body>
<main class="echo_mediaplayer1">
<table class="shopping_item">';
        foreach ($Items as $Item) {
            $html .= '<tr><td>' . $Item['text'] . '</td></tr>';
        }
        $html .= '
</table>
</main>
</body>
</html>';

        return $html;
    }

    public function PlayAlbum(string $album, string $artist, bool $shuffle = false): bool
    {
        return $this->PlayCloudplayer($shuffle, ['albumArtistName' => $artist, 'albumName' => $album]);
    }

    public function PlaySong(string $track_id): bool
    {
        return $this->PlayCloudplayer(false, ['trackId' => $track_id, 'playQueuePrime' => true]);
    }

    public function PlayPlaylist(string $playlist_id, bool $shuffle = false): bool
    {
        return $this->PlayCloudplayer($shuffle, ['playlistId' => $playlist_id, 'playQueuePrime' => true]);
    }

    private function PlayCloudplayer(bool $shuffle, array $postfields): bool
    {
        $getfields = [
            'deviceSerialNumber'   => $this->GetDevicenumber(),
            'deviceType'           => $this->GetDevicetype(),
            'mediaOwnerCustomerId' => $this->GetCustomerID(),
            'shuffle'              => $shuffle ? 'true' : 'false'];

        $return = (array) $this->SendData('CloudplayerQueueandplay', $getfields, $postfields);

        return ($return['http_code'] === 200);

    }

    public function GetLastActivities(int $count)
    {

        $getfields = [
            'size'      => $count,
            'startTime' => '',
            'offset'    => 1];
        $result    = (array) $this->SendData('Activities', $getfields);

        if ($result['http_code'] === 200) {
            return json_decode($result['body'], true);
        }

        return false;

    }

    /** AmazonMusic
     *
     * @param string $seedId
     * @param string $stationName
     *
     * @return mixed
     */
    public function PlayAmazonMusic(string $seedId, string $stationName)
    {
        $url        = 'https://{AlexaURL}/api/gotham/queue-and-play?';
        $getfields  = [
            'deviceSerialNumber'   => $this->GetDevicenumber(),
            'deviceType'           => $this->GetDevicetype(),
            'mediaOwnerCustomerId' => $this->GetCustomerID()];
        $postfields = ['seed' => json_encode(['type' => 'KEY', 'seedId' => $seedId]), 'stationName' => $stationName, 'seedType' => 'KEY'];
        return ($this->SendData('CustomCommand', $getfields, $postfields, $url)['http_code'] === 200);
    }

    public function GetAmazonPrimeStationSectionList(array $filterSections, array $filterCategories, array $stationItems)
    {

        $getfields = [
            'deviceSerialNumber'   => $this->GetDevicenumber(),
            'deviceType'           => $this->GetDevicetype(),
            'mediaOwnerCustomerId' => $this->GetCustomerID()];
        $result    = (array) $this->SendData(
            'PrimeSections', $getfields, null, null, null, null,
            ['filterSections' => $filterSections, 'filterCategories' => $filterCategories, 'stationItems' => $stationItems]
        );

        if ($result['http_code'] === 200) {
            return json_decode($result['body'], true);
        }

        return false;
    }

    public function CustomCommand(string $url, string $postfields = null, bool $optpost = null)
    {
        $search = [
            '{DeviceSerialNumber}',
            '{DeviceType}',
            '{MediaOwnerCustomerID}',
            urlencode('{DeviceSerialNumber}'),
            urlencode('{DeviceSerialNumber}'),
            urlencode('{MediaOwnerCustomerID}')];

        $replace = [
            $this->GetDevicenumber(),
            $this->GetDevicetype(),
            $this->GetCustomerID(),
            $this->GetDevicenumber(),
            $this->GetDevicetype(),
            $this->GetCustomerID()];

        $url = str_replace($search, $replace, $url);

        if ($postfields !== null) {
            $this->SendDebug('CustomCommand', 'URL: ' . $url . ' (no postdata)', 0);
        } else {
            $postfields = str_replace($search, $replace, $postfields);
            $postfields = json_decode($postfields, true);
        }

        return $this->SendData('CustomCommand', null, $postfields, $url, $optpost);
    }

    //<editor-fold desc="not used functions">
    //**************************************************************************************************************************
    //**************************************************************************************************************************

    // die folgenden Funktionen sind noch im Test:
    private function SearchMusicTuneIn(string $query)
    {
        //todo: anpassen und public machen
        $url = 'https://layla.amazon.de/api/tunein/search?query=' . $query . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        return $this->SendData('CustomCommand', null, null, $url);
    }

    /** Gets own songs in the library
     *
     * @return string
     */
    private function GetTracks(): string
    {
        //todo: anpassen und public machen

        // https://alexa.amazon.de/api/cloudplayer/tracks?deviceSerialNumber=G000MW0474740DB4&deviceType=A1NL4BVLQ4L3N3&nextToken=&size=50&mediaOwnerCustomerId=A1R8LY5RFF7KD1&_=1532078220976
        $url = 'https://{AlexaURL}/api/cloudplayer/tracks?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype()
               . '&mediaOwnerCustomerId=' . $this->GetCustomerID() . '&nextToken=&size=50';
        return $this->SendData('CustomCommand', null, null, $url);
    }

    private function ShowLibraryTracks(string $type)
    {
        //todo: anpassen und public machen
        $size   = 50;
        $offset = '';
        $url    =
            'https://{AlexaURL}/api/cloudplayer/playlists/' . $type . '-V0-OBJECTID?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType='
            . $this->GetDevicetype() . '&size=' . $size . '&offset=' . $offset . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        return $this->SendData('CustomCommand', null, null, $url);
    }

    private function GetPrimeStations(string $primeid)
    {
        //todo: anpassen und public machen
        $url =
            'https://{AlexaURL}/api/prime/' . $primeid . '?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype()
            . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        return $this->SendData('CustomCommand', null, null, $url);
    }

    private function GetPrimePlaylist(string $nodeid)
    {
        //todo: anpassen und public machen
        $url =
            'https://{AlexaURL}/api/prime/prime-playlists-by-browse-node?browseNodeId=' . $nodeid . '&deviceSerialNumber=' . $this->GetDevicenumber()
            . '&deviceType=' . $this->GetDevicetype() . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        return $this->SendData('CustomCommand', null, null, $url);
    }
    //</editor-fold>

    //<editor-fold desc="not supported functions">
    // die folgenden Funktionen sind noch nicht unterstützt:

    /*
    public function GetAlbums()
    {
        $url = 'https://{AlexaURL}/api/cloudplayer/tracks?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype() . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        return $this->SendData_old("GetTracks", $url);
    }
    */

    /*
    public function GetCurrentQueue()
    {

    }

    public function Audible(string $book)
    {
        $this->SendDebug("Echo Remote:", "Set Station to " . $station, 0);
        $urltype = "tunein";
        $cookie = $this->ReadPropertyString('TuneInCookie');
        $header = $this->GetHeader($cookie);
        $postfields = '';
        $this->SendEcho($postfields, $header, $urltype, $station);
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $Ident = "EchoTuneInRemote_" . $devicenumber;
        $stationvalue = $this->GetTuneInStationPreset($station);
        if ($stationvalue > 0) {
            $this->SetValue($Ident, $stationvalue);
        }
    }

    public function Kindle(string $book)
    {
        $this->SendDebug("Echo Remote:", "Set Station to " . $station, 0);
        $urltype = "tunein";
        $cookie = $this->ReadPropertyString('TuneInCookie');
        $header = $this->GetHeader($cookie);
        $postfields = '';
        $this->SendEcho($postfields, $header, $urltype, $station);
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $Ident = "EchoTuneInRemote_" . $devicenumber;
        $stationvalue = $this->GetTuneInStationPreset($station);
        if ($stationvalue > 0) {
            $this->SetValue($Ident, $stationvalue);
        }
    }



    public function PrimePlaylist(string $asin)
    {
        $url = 'https://{AlexaURL}/api/prime/prime-playlist-queue-and-play?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype() . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        return $this->SendData_old("PrimePlaylist", $url, ['asin' => $asin]);
    }


    */
    //</editor-fold>

    /** GetDevicetype
     *
     * @return string
     */
    private function GetDevicetype(): string
    {
        return $this->ReadPropertyString('Devicetype');
    }

    /** GetDevicenumber
     *
     * @return string
     */
    private function GetDevicenumber(): string
    {
        return $this->ReadPropertyString('Devicenumber');
    }

    /** GetCustomerID
     *
     * @return string
     */
    private function GetCustomerID(): string
    {
        if ($this->customerID === '') {
            $ParentID = @IPS_GetInstance($this->InstanceID)['ConnectionID'];

            $result = (array) $this->SendData('GetCustomerID');
            if ($result['http_code'] === 200) {
                $this->customerID = $result['body'];
            } else {
                $this->customerID = '';
                trigger_error('CustomerID nicht gesetzt. Parent: ' . $ParentID);
            }

        }

        return $this->customerID;
    }

    //<editor-fold desc="configuration form">
    /***********************************************************
     * Configuration Form
     ***********************************************************/
    /** @noinspection PhpMissingParentCallCommonInspection
     * build configuration form
     *
     * @return string
     */
    public function GetConfigurationForm():string
    {
        return json_encode(
            [
                'elements' => $this->FormHead(),
                'actions'  => $this->FormActions(),
                'status'   => $this->FormStatus()]
        );
    }

    /**
     * return form configurations on configuration step
     *
     * @return array
     */
    private function FormHead(): array
    {
        return [
            [
                'name'    => 'Devicetype',
                'type'    => 'ValidationTextBox',
                'caption' => 'device type'],
            [
                'name'    => 'Devicenumber',
                'type'    => 'ValidationTextBox',
                'caption' => 'device number'],
            [
                'name'    => 'updateinterval',
                'type'    => 'NumberSpinner',
                'caption' => 'update interval',
                'suffix'  => 'seconds'],
            [
                'name'    => 'ExtendedInfo',
                'type'    => 'CheckBox',
                'caption' => 'setup variables for extended info (title, subtitle_1, subtitle_2, cover)'],
			[
				'name'    => 'Mute',
				'type'    => 'CheckBox',
				'caption' => 'setup variable for mute'],
            [
                'name'    => 'AlarmInfo',
                'type'    => 'CheckBox',
                'caption' => 'setup variables for alarm info (nextAlarmTime, lastAlarmTime)'],
            [
                'name'    => 'ShoppingList',
                'type'    => 'CheckBox',
                'caption' => 'setup variable for a shopping list'],
            [
                'name'    => 'TaskList',
                'type'    => 'CheckBox',
                'caption' => 'setup variable for a task list'],
            [
                'type'     => 'List',
                'name'     => 'TuneInStations',
                'caption'  => 'TuneIn stations',
                'rowCount' => 20,
                'add'      => true,
                'delete'   => true,
                'sort'     => [
                    'column'    => 'position',
                    'direction' => 'ascending'],
                'columns'  => [
                    [
                        'name'    => 'position',
                        'caption' => 'Station',
                        'width'   => '100px',
                        'save'    => true,
                        'visible' => true,
                        'add'     => 0,
                        'edit'    => [
                            'type' => 'NumberSpinner']],
                    [
                        'name'    => 'station',
                        'caption' => 'Station Name',
                        'width'   => '200px',
                        'save'    => true,
                        'add'     => '',
                        'edit'    => [
                            'type' => 'ValidationTextBox']],
                    [
                        'name'    => 'station_id',
                        'caption' => 'Station ID',
                        'width'   => 'auto',
                        'save'    => true,
                        'add'     => '',
                        'edit'    => [
                            'type' => 'ValidationTextBox'],
                        'visible' => true]]]];
    }


    /**
     * return form actions by token
     *
     * @return array
     */
    private function FormActions(): array
    {
        $form = [
            [
                'type'    => 'Label',
                'caption' => 'Play Radio:'],
            [
                'type'    => 'Button',
                'caption' => 'FFH Lounge',
                'onClick' => "if (EchoRemote_TuneIn(\$id, 's84483')){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'    => 'Label',
                'caption' => 'Remote Control:'],
            [
                'type'    => 'Button',
                'caption' => 'Play',
                'onClick' => "if (EchoRemote_Play(\$id)){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'    => 'Button',
                'caption' => 'Pause',
                'onClick' => "if (EchoRemote_Pause(\$id)){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'    => 'Label',
                'caption' => 'Modify Volume:'],
            [
                'type'    => 'Button',
                'caption' => 'Decrease Volume',
                'onClick' => "if (EchoRemote_DecreaseVolume(\$id, 3)){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'    => 'Button',
                'caption' => 'Increase Volume',
                'onClick' => "if (EchoRemote_IncreaseVolume(\$id, 3)){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'    => 'Label',
                'caption' => 'Voice Output:'],
            [
                'type'    => 'Button',
                'caption' => 'Speak Text',
                'onClick' => "if (EchoRemote_TextToSpeech(\$id, 'Wer hätte das gedacht. Das ist ein toller Erfolg!')){echo 'Ok';} else {echo 'Error';}"]];

        return $form;
    }

    /**
     * return from status
     *
     * @return array
     */
    private function FormStatus(): array
    {
        $form = [
            [
                'code'    => 210,
                'icon'    => 'error',
                'caption' => 'devicetype field must not be empty.'],
            [
                'code'    => 211,
                'icon'    => 'error',
                'caption' => 'devicenumber field must not be empty.']];

        return $form;
    }

    //</editor-fold>
}