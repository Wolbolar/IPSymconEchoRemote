<?
declare(strict_types=1);

require_once __DIR__ . '/../libs/BufferHelper.php';
require_once __DIR__ . '/../libs/DebugHelper.php';
require_once __DIR__ . '/../libs/ConstHelper.php';


// Modul für Amazon Echo Remote

class EchoRemote extends IPSModule
{

    const STATUS_INST_DEVICETYPE_IS_EMPTY = 210; // devicetype must not be empty.
    const STATUS_INST_DEVICENUMBER_IS_EMPTY = 211; // devicenumber must not be empty

    private $customerID = '';

    private $ParentID   = 0;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.

        $this->RegisterPropertyString("Devicetype", "");
        $this->RegisterPropertyString("DeviceFamily", "");
        $this->RegisterPropertyString("Devicenumber", "");
        $this->RegisterPropertyString("DeviceAccountID", "");
        $this->RegisterPropertyString(
            "TuneInStations", '[{"position":1,"station":"Hit Radio FFH","station_id":"s17490"},
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
            {"position":2,"station":"FFH Lounge","station_id":"s84483"},
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
            {"position":3,"station":"FFH Rock","station_id":"s84489"},
            {"position":30,"station":"MDR JUMP","station_id":"s6634"},
            {"position":31,"station":"Costa Del Mar","station_id":"s187256"},
            {"position":32,"station":"Antenne Bayern","station_id":"s139505"},
            {"position":4,"station":"FFH Die 80er","station_id":"s84481"},
            {"position":5,"station":"FFH iTunes Top 40","station_id":"s84486"},
            {"position":6,"station":"FFH Eurodance","station_id":"s84487"},
            {"position":7,"station":"FFH Soundtrack","station_id":"s97088"},
            {"position":8,"station":"FFH Die 90er","station_id":"s97089"},
            {"position":9,"station":"FFH Schlagerkult","station_id":"s84482"}]'
        );
        $this->RegisterPropertyInteger("updateinterval", 0);
        $this->RegisterPropertyBoolean("ExtendedInfo", false);

        $this->SetBuffer('CoverURL', '');
        $this->RegisterTimer('EchoUpdate', 0, 'EchoRemote_UpdateStatus(' . $this->InstanceID . ');');

        $this->ConnectParent("{C7F853A4-60D2-99CD-A198-2C9025E2E312}");

        //we will wait until the kernel is ready
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);

    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() != KR_READY) {
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

    private function HasActiveParent()
    {
        if ($this->ParentID > 0) {
            if (IPS_GetInstance($this->ParentID)['InstanceStatus'] == IS_ACTIVE) {
                return true;
            }
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
    protected function RegisterParent()
    {
        $OldParentId = $this->ParentID;
        $ParentId    = @IPS_GetInstance($this->InstanceID)['ConnectionID'];
        if ($ParentId <> $OldParentId) {
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


    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {

        switch ($Message) {
            case IM_CHANGESTATUS:
                if ($Data[0] == IS_ACTIVE) {
                    $this->ApplyChanges();
                }
                break;

            case IPS_KERNELMESSAGE:
                if ($Data[0] == KR_READY) {
                    $this->ApplyChanges();
                }

            default:
                break;
        }
    }

    private function ValidateConfiguration()
    {

        if ($this->ReadPropertyString('Devicetype') == "") {
            $this->SetStatus(self::STATUS_INST_DEVICETYPE_IS_EMPTY);
        } elseif ($this->ReadPropertyString('Devicenumber') == "") {
            $this->SetStatus(self::STATUS_INST_DEVICENUMBER_IS_EMPTY);
        } else {
            $this->SetStatus(IS_ACTIVE);
            $this->SetEchoInterval();

            return true;
        }

        return false;
    }

    private function RegisterVariables()
    {
        if (!$this->HasActiveParent()) {
            return;
        }

        $device_info = $this->GetDeviceInfo();

        if ($device_info == false) {
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
        $this->RegisterVariableInteger("EchoRemote", "Remote", "Echo.Remote", 1);
        $this->EnableAction("EchoRemote");

        //Shuffle Variable
        if (in_array('AMAZON_MUSIC', $caps)) {
            $this->RegisterVariableBoolean("EchoShuffle", "Shuffle", "~Switch", 2);
            IPS_SetIcon($this->GetIDForIdent("EchoShuffle"), "Shuffle");
            $this->EnableAction("EchoShuffle");
        }

        //Repeat Variable
        if (in_array('AMAZON_MUSIC', $caps)) {
            $this->RegisterVariableBoolean("EchoRepeat", "Repeat", "~Switch", 3);
            IPS_SetIcon($this->GetIDForIdent("EchoRepeat"), "Repeat");
            $this->EnableAction("EchoRepeat");
        }

        //Repeat Variable
        if (in_array('VOLUME_SETTING', $caps)) {
            $this->RegisterVariableInteger("EchoVolume", "Volume", "~Intensity.100", 4);
            $this->EnableAction("EchoVolume");
        }


        //Info Variable
        $this->RegisterVariableString("EchoInfo", "Info", "~HTMLBox", 5);

        //Actions and TTS Variables
        if (in_array('FLASH_BRIEFING', $caps)) {
            $this->RegisterProfileAssociation(
                'Echo.Actions', 'Move', '', '', 0, 5, 0, 0, vtInteger, [
                                  [0, $this->Translate('Weather'), '', -1],
                                  [1, $this->Translate('Traffic'), '', -1],
                                  [2, $this->Translate('Flash Briefing'), '', -1],
                                  [3, $this->Translate('Good morning'), '', -1],
                                  [4, $this->Translate('Sing a song'), '', -1],
                                  [5, $this->Translate('Tell a story'), '', -1]]
            );
            $this->RegisterVariableInteger("EchoActions", "Actions", "Echo.Actions", 6);
            $this->EnableAction("EchoActions");

            $this->RegisterVariableString("EchoTTS", "Text to Speech", "", 7);
            $this->EnableAction("EchoTTS");
        }


        //TuneIn Variable
        if (in_array('TUNE_IN', $caps)) {
            $devicenumber = $this->ReadPropertyString('Devicenumber');
            if ($devicenumber != '') {
                $tuneinstations = $this->GetTuneInStations();
                $this->RegisterProfileAssociation(
                    'Echo.TuneInStation.' . $devicenumber, 'Music', '', '', 1, 32, 0, 0, vtInteger, [
                                                             [1, $tuneinstations[1]["name"], "", -1],
                                                             [2, $tuneinstations[2]["name"], "", -1],
                                                             [3, $tuneinstations[3]["name"], "", -1],
                                                             [4, $tuneinstations[4]["name"], "", -1],
                                                             [5, $tuneinstations[5]["name"], "", -1],
                                                             [6, $tuneinstations[6]["name"], "", -1],
                                                             [7, $tuneinstations[7]["name"], "", -1],
                                                             [8, $tuneinstations[8]["name"], "", -1],
                                                             [9, $tuneinstations[9]["name"], "", -1],
                                                             [10, $tuneinstations[10]["name"], "", -1],
                                                             [11, $tuneinstations[11]["name"], "", -1],
                                                             [12, $tuneinstations[12]["name"], "", -1],
                                                             [13, $tuneinstations[13]["name"], "", -1],
                                                             [14, $tuneinstations[14]["name"], "", -1],
                                                             [15, $tuneinstations[15]["name"], "", -1],
                                                             [16, $tuneinstations[16]["name"], "", -1],
                                                             [17, $tuneinstations[17]["name"], "", -1],
                                                             [18, $tuneinstations[18]["name"], "", -1],
                                                             [19, $tuneinstations[19]["name"], "", -1],
                                                             [20, $tuneinstations[20]["name"], "", -1],
                                                             [21, $tuneinstations[21]["name"], "", -1],
                                                             [22, $tuneinstations[22]["name"], "", -1],
                                                             [23, $tuneinstations[23]["name"], "", -1],
                                                             [24, $tuneinstations[24]["name"], "", -1],
                                                             [25, $tuneinstations[25]["name"], "", -1],
                                                             [26, $tuneinstations[26]["name"], "", -1],
                                                             [27, $tuneinstations[27]["name"], "", -1],
                                                             [28, $tuneinstations[28]["name"], "", -1],
                                                             [29, $tuneinstations[29]["name"], "", -1],
                                                             [30, $tuneinstations[30]["name"], "", -1],
                                                             [31, $tuneinstations[31]["name"], "", -1],
                                                             [32, $tuneinstations[32]["name"], "", -1]]
                );
                $this->RegisterVariableInteger("EchoTuneInRemote_" . $devicenumber, "TuneIn Radio", "Echo.TuneInStation." . $devicenumber, 5);
                $this->EnableAction("EchoTuneInRemote_" . $devicenumber);
            }
        }

        if ($this->ReadPropertyBoolean("ExtendedInfo")) {
            $this->RegisterVariableString("Title", $this->Translate("Title"), "", 8);
            $this->RegisterVariableString("Subtitle_1", $this->Translate("Subtitle 1"), "", 9);
            $this->RegisterVariableString("Subtitle_2", $this->Translate("Subtitle 2"), "", 10);
            $this->CreateMediaImage("MediaImageCover", 11);

        }

    }

    private function SetEchoInterval()
    {
        $echointerval = $this->ReadPropertyInteger("updateinterval");
        $interval     = $echointerval * 1000;
        $this->SetTimerInterval("EchoUpdate", $interval);
    }

    private function Covername()
    {
        $name      = IPS_GetName($this->InstanceID);
        $search    = ["ä", "ö", "ü", "Ä", "Ö", "Ü", "ß", " "];
        $replace   = ["ae", "oe", "ue", "Ae", "Oe", "Ue", "ss", "_"];
        $covername = "echocover" . (str_replace($search, $replace, $name));
        return $covername;
    }

    private function CreateMediaImage(string $ident, int $position)
    {
        $covername = $this->Covername();
        $picurl    = $this->GetBuffer("CoverURL"); // Cover URL
        $ImageFile = IPS_GetKernelDir() . "media" . DIRECTORY_SEPARATOR . $covername . ".png";  // Image-Datei

        $MediaID = @$this->GetIDForIdent($ident);
        if ($MediaID === false) {
            if ($picurl) {
                $Content = base64_encode(file_get_contents($picurl)); // Bild Base64 codieren
                // convert to png
                imagepng(imagecreatefromstring(file_get_contents($picurl)), $ImageFile); // save PNG
            } else {
                // set transparent image
                $Content =
                    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII="; // Transparent png 1x1 Base64
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

    /** GetTuneInStations
     *
     * @return array
     */
    private function GetTuneInStations()
    {
        $tuneinstations = [];
        $list_json      = $this->ReadPropertyString("TuneInStations");
        $list           = json_decode($list_json, true);
        foreach ($list as $station) {

            $present                               = $station["position"];
            $station_name                          = $station["station"];
            $stationid                             = $station["station_id"];
            $tuneinstations[$present]["name"]      = $station_name;
            $tuneinstations[$present]["stationid"] = $stationid;
        }
        return $tuneinstations;
    }

    /** GetTuneInStationID
     *
     * @param $present
     *
     * @return string
     */
    private function GetTuneInStationID(int $present)
    {
        $list_json = $this->ReadPropertyString("TuneInStations");
        $list      = json_decode($list_json, true);
        $stationid = "";
        foreach ($list as $station) {
            if ($present == $station["position"]) {
                $station_name = $station["station"];
                $stationid    = $station["station_id"];
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
    private function GetTuneInStationPreset(string $guideId)
    {
        $stationpreset = false;
        $list_json     = $this->ReadPropertyString("TuneInStations");
        $list          = json_decode($list_json, true);
        foreach ($list as $station) {
            if ($guideId == $station["station_id"]) {
                $stationpreset = $station["position"];
                $station_name  = $station["station"];
                $stationid     = $station["station_id"];
                $this->SendDebug(__FUNCTION__, 'present: ' . $stationpreset, 0);
                $this->SendDebug(__FUNCTION__, 'station name: ' . $station_name, 0);
                $this->SendDebug(__FUNCTION__, 'station id: ' . $stationid, 0);
            }
        }
        return $stationpreset;
    }


    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
     *
     *
     */

    /** Rewind 30s
     *
     * @return array|string
     */
    public function Rewind30s()
    {
        $result = $this->PlayCommand('RewindCommand');
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoRemote", 0);
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
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoRemote", 1);
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
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoRemote", 2);
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
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoRemote", 3);
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
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoRemote", 4);
            return true;
        }
        return false;
    }


    /** Forward 30s
     *
     * @return array|string
     */
    public function Forward30s()
    {
        $result = $this->PlayCommand('ForwardCommand');
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoRemote", 5);
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
        return $this->SetVolume((int) $this->GetValue("EchoVolume") + 1);
    }

    /** VolumeDown
     *
     * @return array|string
     */
    public function VolumeDown()
    {
        return $this->SetVolume((int) $this->GetValue("EchoVolume") - 1);
    }

    /** IncreaseVolume
     *
     * @param int $increment
     *
     * @return array|string
     */
    public function IncreaseVolume(int $increment)
    {
        return $this->SetVolume((int) $this->GetValue("EchoVolume") + $increment);
    }

    /** DecreaseVolume
     *
     * @param int $increment
     *
     * @return array|string
     */
    public function DecreaseVolume(int $increment)
    {
        return $this->SetVolume($this->GetValue("EchoVolume") - $increment);
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
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoVolume", $volume);
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
        if ($result['http_code'] == 200) {
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
        $this->SendDebug("Echo Remote:", "Request Action Shuffle", 0);

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'type'    => 'ShuffleCommand',
            'shuffle' => ($value ? 'true' : 'false')];

        $result = $this->SendData('NpCommand', $getfields, $postfields);
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoShuffle", $value);
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
        $this->SendDebug("Echo Remote:", "Request Action Repeat", 0);

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'type'   => 'RepeatCommand',
            'repeat' => ($value ? 'true' : 'false')];

        $result = $this->SendData('NpCommand', $getfields, $postfields);
        if ($result['http_code'] == 200) {
            $this->SetValue("EchoRepeat", $value);
            return true;
        }
        return false;
    }

    private function GetDeviceInfo()
    {
        $this->SendDebug(__FUNCTION__, 'started', 0);

        //fetch all devices
        $result = $this->SendData('GetDevices');

        if ($result['http_code'] != 200) {
            return false;
        }


        $devices_arr = json_decode($result['body'], true)["devices"];

        //search device with my type and serial number
        $myDevice = null;
        foreach ($devices_arr as $key => $device) {
            if (($device['deviceType'] == $this->GetDevicetype()) && ($device['serialNumber'] == $this->GetDevicenumber())) {
                return $device;
                break;
            }
        }

        return false;

    }

    /** PlayCommand
     *
     * @param $COMMAND
     *
     * @return array|string
     */
    private function PlayCommand(string $COMMAND)
    {
        $this->SendDebug(__FUNCTION__, 'Command: ' . $COMMAND, 0);

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'type' => $COMMAND];

        $result = $this->SendData('NpCommand', $getfields, $postfields);

        return $result;
    }


    /** play TuneIn radio station
     *
     * @param string $guideId
     *
     * @return bool
     */
    public function TuneIn(string $guideId)
    {

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = [
            'contentType'          => 'station',
            'guideId'              => $guideId,
            'mediaOwnerCustomerId' => $this->GetCustomerID()];


        $result = $this->SendData('TuneinQueueandplay', $getfields, $postfields);

        $stationvalue = $this->GetTuneInStationPreset($guideId);
        if ($stationvalue > 0) {
            $devicenumber = $this->ReadPropertyString('Devicenumber');
            $Ident        = "EchoTuneInRemote_" . $devicenumber;
            $this->SetValue($Ident, $stationvalue);
        }
        if ($result['http_code'] == 200) {
            return true;
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


        if ($result['http_code'] == 200) {
            return json_decode($result['body'], true);
        } else {
            return false;
        }
    }


    /** Play TuneIn station by present
     *
     * @param int $preset
     *
     * @return bool
     */
    public function TuneInPreset(int $preset)
    {
        $station = $this->GetTuneInStationID($preset);
        if ($station != '') {
            return $this->TuneIn($station);
        } else {
            trigger_error('unknown preset: ' . $preset);
            return false;
        }

    }

    /** GetDevicetype
     *
     * @return string
     */
    private function GetDevicetype()
    {
        $devicetype = $this->ReadPropertyString("Devicetype");
        return $devicetype;
    }

    /** GetDevicenumber
     *
     * @return string
     */
    private function GetDevicenumber()
    {
        $devicenumber = $this->ReadPropertyString("Devicenumber");
        return $devicenumber;
    }

    /** GetCustomerID
     *
     * @return string
     */
    private function GetCustomerID()
    {
        if ($this->customerID == '') {
            $ParentID = @IPS_GetInstance($this->InstanceID)['ConnectionID'];


            $result = (array) $this->SendData('GetCustomerID');
            if ($result['http_code'] == 200) {
                $this->customerID = $result['body'];
            } else {
                $this->customerID = '';
            }
            if ($this->customerID == '') {
                trigger_error('CustomerID nicht gesetzt. Parent: ' . $ParentID);
            }
        }
        return $this->customerID;
    }


    /** PlaySequenceCmd
     *
     * @param string $SEQUENCECMD
     * @param string $tts
     *
     * @return string
     */
    private function PlaySequenceCmd(string $SEQUENCECMD, string $tts = null)
    {

        $postfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype(),
            'customerId'         => $this->GetCustomerID(),
            'type'               => $SEQUENCECMD];

        if (isset($tts)) {
            $postfields['textToSpeak'] = $tts;
        }

        $result = (array) $this->SendData('BehaviorsPreview', null, $postfields);

        return ($result['http_code'] == 200);


    }


    /** TextToSpeech
     *
     * @param string $tts
     *
     * @return array|string
     */
    public function TextToSpeech(string $tts)
    {
        return $this->PlaySequenceCmd('Alexa.Speak', $tts);
    }

    /**
     * Weather Forcast
     */
    public function Weather()
    {
        return $this->PlaySequenceCmd('Alexa.Weather.Play');
    }

    /**
     * Traffic
     */
    public function Traffic()
    {
        return $this->PlaySequenceCmd('Alexa.Traffic.Play');
    }

    /**
     * Flash briefing
     */
    public function FlashBriefing()
    {
        return $this->PlaySequenceCmd('Alexa.FlashBriefing.Play');
    }

    /**
     * Goodmorning
     */
    public function GoodMorning()
    {
        return $this->PlaySequenceCmd('Alexa.GoodMorning.Play');
    }

    /**
     * Sing a song
     */
    public function SingASong()
    {
        return $this->PlaySequenceCmd('Alexa.SingASong.Play');
    }

    /**
     * Tell a story
     */
    public function TellStory()
    {
        return $this->PlaySequenceCmd('Alexa.TellStory.Play');
    }

    private function GetAutomation($utterance, $automations)
    {
        foreach ($automations as $automation) {
            foreach ($automation['triggers'] as $trigger) {
                if ($trigger['payload']['utterance'] == $utterance) {
                    return $automation;
                }
            }
        }

        return false;

    }

    public function StartAlexaRoutine(string $utterance)
    {

        //get all Automations
        $result = (array) $this->SendData('BehaviorsAutomations');

        if ($result['http_code'] != 200) {
            return false;
        }

        $automations = json_decode($result['body'], true);

        //search Automation of utterance
        if ($automation = $this->GetAutomation($utterance, $automations)) {
            //play automation
            $postfields = [
                'deviceSerialNumber' => $this->GetDevicenumber(),
                'deviceType'         => $this->GetDevicetype()];

            $result = (array) $this->SendData('BehaviorsPreviewAutomation', null, $postfields, null, null, $automation);
            return ($result['http_code'] == 200);
        }

        return false;
    }

    private function DeleteMultiroom(string $devicenumber)
    {
        //todo: anpassen und public machen
        $url = "https://{AlexaURL}/api/lemur/tail/" . $devicenumber;
        // Todo add DELETE
        return $this->SendData('DeleteMultiroom', null, null, $url);
    }

    private function CreateMultiroom()
    {
        //todo: anpassen und public machen
        $url        = "https://{AlexaURL}/api/lemur/tail";
        $postfields = [
            'dsn'        => $this->GetDevicenumber(),
            'deviceType' => $this->GetDevicetype()];
        return $this->SendData('CustomCommand', null, $postfields, $url); //todo
    }

    /** List all echo devices with connected Bluetooth devices
     *
     * @return mixed
     */
    private function ListBluetooth()
    {
        $result = (array) $this->SendData('Bluetooth');

        if ($result['http_code'] == 200) {
            $data    = json_decode($result['body'], true);
            $devices = $data["bluetoothStates"];
            return $devices;
        } else {
            return false;
        }
    }

    /** List paired bluetooth devices
     *
     * @return string
     */
    public function ListPairedBluetoothDevices()
    {
        //todo: anpassen und public machen
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        if ($devices = $this->ListBluetooth()) {
            $pairedDeviceList = "";
            foreach ($devices as $key => $device) {
                if ($devicenumber == $device["deviceSerialNumber"]) {
                    $pairedDeviceList = $device["pairedDeviceList"];
                }
            }
            return $pairedDeviceList;

        } else {
            return false;
        }
    }

    public function ConnectBluetooth(string $bluetooth_address)
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = ['bluetoothDeviceAddress' => $bluetooth_address];
        $result     = (array) $this->SendData('BluetoothPairSink', $getfields, $postfields);

        return ($result['http_code'] == 200);
    }

    public function DisconnectBluetooth()
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $result = (array) $this->SendData('BluetoothDisconnectSink', $getfields);

        return ($result['http_code'] == 200);
    }

    private function SearchMusicTuneIn(string $query)
    {
        //todo: anpassen und public machen
        $url = 'https://layla.amazon.de/api/tunein/search?query=' . $query . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        return $this->SendData('CustomCommand', null, null, $url);
    }

    private function SetStatePageRadio(string $imageurl, string $title, string $radioStationName, string $radiostationslogan)
    {
        $this->SetStatePage($imageurl, $title, $radioStationName, $radiostationslogan);
    }

    private function SetStatePageAlbum(string $imageurl, string $title, string $album, string $artist)
    {
        $this->SetStatePage($imageurl, $title, $album, $artist);
    }

    private function SetStatePage(string $imageurl = null, string $title = null, string $subtitle_1 = null, string $subtitle_2 = null)
    {
        $html = '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Echo Info</title>
<style type="text/css">
.echo_mediaplayer {
	font-family: "Lucida Grande", "Lucida Sans Unicode", "Lucida Sans", "DejaVu Sans", Verdana, sans-serif;
	background-color: hsla(0,0%,100%,0.00);
	color: hsla(0,0%,100%,1.00);
	text-shadow: 1px 1px 3px hsla(0,0%,0%,1.00);
}
.echo_cover {
	display: block;
	float: left;
	padding-left: 8px;
	padding-top: 8px;
	padding-right: 8px;
	padding-bottom: 8px;
}
.echo_mediaplayer .echo_cover #echocover {
	-webkit-box-shadow: 2px 2px 5px hsla(0,0%,0%,1.00);
	box-shadow: 2px 2px 5px hsla(0,0%,0%,1.00);
}
.echo_description {
	vertical-align: bottom;
	float: none;
	padding-top: 60px;
	padding-right: 11px;
	padding-bottom: 11px;
	padding-left: 11px;
	margin-top: 0;
}
.echo_title {
	font-size: xx-large;
}
.echo_subtitle1 {
	font-size: large;
}
.echo_subtiltle2 {
	font-size: large;
}
</style>
<!--The following script tag downloads a font from the Adobe Edge Web Fonts server for use within the web page. We recommend that you do not modify it.-->
<script>var __adobewebfontsappname__="dreamweaver"</script>
<script src="http://use.edgefonts.net/source-sans-pro:n6:default;acme:n4:default;bilbo:n4:default.js" type="text/javascript"></script>

</head>

<body>
<main class="echo_mediaplayer">
  <section class="echo_cover"><img src="' . $imageurl . '" width="145" height="145" id="echocover"></section>
  <section class="echo_description">
    <div class="echo_title">' . $title . '</div>
    <div class="echo_subtitle1">' . $subtitle_1 . '</div>
    <div class="echo_subtiltle2">' . $subtitle_2 . '</div>
  </section>
</main>
</body>
</html>';
        $this->SetValue("EchoInfo", $html);
        $extended_info = $this->ReadPropertyBoolean("ExtendedInfo");
        if ($extended_info) {
            $this->SetValue("Title", $title);
            $this->SetValue("Subtitle_1", $subtitle_1);
            $this->SetValue("Subtitle_2", $subtitle_2);
            $this->SetBuffer("CoverURL", $imageurl);
            $this->RefreshCover($imageurl);
        }
    }

    private function RefreshCover(string $imageurl)
    {
        $Content = base64_encode(file_get_contents($imageurl)); // Bild Base64 codieren
        IPS_SetMediaContent($this->GetIDForIdent("MediaImageCover"), $Content);  // Base64 codiertes Bild ablegen
        IPS_SendMediaEvent($this->GetIDForIdent("MediaImageCover")); //aktualisieren
    }

    private function GetPropertyValues(array $requestedProperties, $StateReturn)
    {
        $state = [];
        foreach ($requestedProperties as $property) {
            if (property_exists($StateReturn, $property)) {
                $Data             = $StateReturn->{$property};
                $state[$property] = $Data;
                if (is_array($Data)) {
                    foreach ($Data as $Key => $DebugData) {
                        if (is_array($DebugData)) {
                            $this->SendDebug($property . ':' . $Key, json_encode($DebugData), 0);
                        } else {
                            $this->SendDebug($property . ":", json_encode($DebugData), 0);
                        }
                    }
                } else {
                    $this->SendDebug($property . ":", $StateReturn->{$property}, 0);
                }
            }
        }
        return $state;
    }

    /** Get State Tune In
     *
     * @return string
     */
    private function GetState() //todo: zu löschen? ersetzt durch UpdateStatus()
    {

        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $result = (array) $this->SendData('MediaState', $getfields);


        //$url = 'https://{AlexaURL}/api/media/state?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype()
        //       . '&queueId=0e7d86f5-d5a4-4a3a-933e-5910c15d9d4f&shuffling=false&firstIndex=1&lastIndex=1&screenWidth=1920&_=1495289082979';


        $returnObj = json_decode($result['body']);

        // get all relevant properties from the return
        $values = $this->GetPropertyValues(
            [
                'clientId',
                'contentId',
                'contentType',
                'currentState',
                'imageURL',
                'isDisliked',
                'isLiked',
                'looping',
                'programId',
                'progressSeconds',
                'muted',
                'providerId',
                'queue',
                'queueId',
                'queueSize',
                'radioStationId',
                'radioVariety',
                'referenceId',
                'service',
                'shuffling',
                'timeLastShuffled',
                'volume'], $returnObj
        );
        /*
        $queue = $values['queue'][0];
        $queue_values = $this->GetPropertyValues(['album', 'albumAsin', 'artist', 'asin', 'cardImageURL', 'contentId', 'contentType', 'durationSeconds', 'feedbackDisabled', 'historicalId',
            'imageURL', 'index', 'isAd', 'isDisliked', 'isFreeWithPrime', 'isLiked', 'programId', 'programName', 'providerId', 'queueId', 'radioStationCallSign', 'radioStationId', 'radioStationLocation',
            'radioStationName', 'radioStationSlogan', 'referenceId', 'service', 'startTime', 'title', 'trackId', 'trackStatus'], $queue);
        */
        if ($values['queueSize'] > 0) {
            switch ($values['contentType']) {
                case 'LIVE_STATION':
                    $valuesLiveStation = $this->GetPropertyValues(
                        ['radioStationId', 'radioStationSlogan', 'radioStationName', 'radioStationLocation', 'imageURL', 'title'],
                        $returnObj->queue[0]
                    );
                    break;

                case 'TRACKS':
                    $valuesTracks = $this->GetPropertyValues(['artist', 'album', 'title', 'imageURL'], $returnObj->queue[0]);
                    break;

                default:
                    trigger_error('Not (yet) supported contentType: ' . $values['contentType']);
            }
        }

        switch ($values['currentState']) {
            case 'PLAYING':
                $this->SetValue("EchoRemote", 3);
                break;

            case 'PAUSED':
                $this->SetValue("EchoRemote", 2);
                break;

            default:
                trigger_error('Unexpected currentState: ' . $values['currentState']);
        }

        if (isset($valuesLiveStation)) {
            $this->SetStatePageRadio(
                $valuesLiveStation['imageURL'], $valuesLiveStation['title'], $valuesLiveStation['radioStationName'],
                $valuesLiveStation['radioStationSlogan']
            );
        }

        if (isset($valuesTracks)) {
            $this->SetStatePageAlbum($valuesTracks['imageURL'], $valuesTracks['title'], $valuesTracks['album'], $valuesTracks['artist']);
        }

        if (isset($values['looping'])) {
            $this->SetValue("EchoRepeat", $values['looping']);
        }

        if (isset($values['shuffling'])) {
            $this->SetValue("EchoShuffle", $values['shuffling']);
        }

        if (isset($values['volume'])) {
            $this->SetValue("EchoVolume", $values['volume']);
        }

        if ($result['http_code'] == 200) {
            return json_decode($result['body'], true);
        } else {
            return false;
        }
    }

    /** Get State Tune In
     *
     * @return string
     */
    public function UpdateStatus()
    {

        if (!$result = $this->GetPlayerInformation()) {
            return false;
        }

        $playerInfo = $result['playerInfo'];

        switch ($playerInfo['state']) {
            case 'PLAYING':
                $this->SetValue("EchoRemote", 3);
                break;

            case null:
            case 'PAUSED':
            case 'IDLE':
                $this->SetValue("EchoRemote", 2);
                break;

            default:
                trigger_error('Unexpected state: ' . $playerInfo['state']);
        }

        if (isset($playerInfo['mainArt']['url'])) {
            $imageurl = $playerInfo['mainArt']['url'];
        } else {
            $imageurl = null;
        }
        $this->SetStatePage(
            $imageurl, $playerInfo['infoText']['title'], $playerInfo['infoText']['subText1'], $playerInfo['infoText']['subText2']
        );

        switch ($playerInfo['transport']['repeat']) {
            case null:
            case 'HIDDEN':
            case 'ENABLED':
                $this->SetValue("EchoRepeat", false);
                break;

            case 'SELECTED':
                $this->SetValue("EchoRepeat", true);
                break;

            default:
                trigger_error('Unexpected repeat value: ' . $playerInfo['transport']['repeat']);
        }

        switch ($playerInfo['transport']['shuffle']) {
            case null:
            case 'HIDDEN':
            case 'ENABLED':
                $this->SetValue("EchoShuffle", false);
                break;

            case 'SELECTED':
                $this->SetValue("EchoShuffle", true);
                break;

            default:
                trigger_error('Unexpected shuffle value: ' . $playerInfo['transport']['shuffle']);
        }

        $this->SetValue("EchoVolume", $playerInfo['volume']['volume']);

        return true;
    }

    /** Plays imported music via trackid
     *
     * @param string $trackid
     *
     * @return string
     */
    private function ImportedMusic(string $trackid)
    {
        //todo: zu löschen? Hinweis: ist identisch mit 'PlaySong'!
        return $this->PlayCloudplayer(false, ['trackId' => $trackid, 'playQueuePrime' => true]);
    }

    private function PlayCloudplayer(bool $shuffle, array $postfields)
    {
        $getfields = [
            'deviceSerialNumber'   => $this->GetDevicenumber(),
            'deviceType'           => $this->GetDevicetype(),
            'mediaOwnerCustomerId' => $this->GetCustomerID(),
            'shuffle'              => $shuffle ? 'true' : 'false'];

        $return = (array) $this->SendData('CloudplayerQueueandplay', $getfields, $postfields);

        return ($return['http_code'] == 200);

    }

    //**************************************************************************************************************************
    //**************************************************************************************************************************

    // die folgenden Funktionen sind noch im Test:

    /** Gets own songs in the library
     *
     * @return string
     */
    private function GetTracks()
    {
        //todo: anpassen und public machen

        // https://alexa.amazon.de/api/cloudplayer/tracks?deviceSerialNumber=G000MW0474740DB4&deviceType=A1NL4BVLQ4L3N3&nextToken=&size=50&mediaOwnerCustomerId=A1R8LY5RFF7KD1&_=1532078220976
        $url = 'https://{AlexaURL}/api/cloudplayer/tracks?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype()
               . '&mediaOwnerCustomerId=' . $this->GetCustomerID() . '&nextToken=&size=50';
        return $this->SendData('CustomCommand', null, null, $url);
    }

    public function CustomCommand(string $url, string $postfields = null, bool $optpost = null)
    {
        $url = str_replace('{DeviceSerialNumber}', $this->GetDevicenumber(), $url);
        $url = str_replace('{DeviceType}', $this->GetDevicetype(), $url);
        $url = str_replace('{MediaOwnerCustomerID}', $this->GetCustomerID(), $url);
        $url = str_replace(urlencode('{DeviceSerialNumber}'), $this->GetDevicenumber(), $url);
        $url = str_replace(urlencode('{DeviceType}'), $this->GetDevicetype(), $url);
        $url = str_replace(urlencode('{MediaOwnerCustomerID}'), $this->GetCustomerID(), $url);
        if (is_null($postfields)) {
            $this->SendDebug("CustomCommand", "URL: " . $url . " (no postdata)", 0);
        } else {
            $postfields = str_replace('{DeviceSerialNumber}', $this->GetDevicenumber(), $postfields);
            $postfields = str_replace('{DeviceType}', $this->GetDevicetype(), $postfields);
            $postfields = str_replace('{MediaOwnerCustomerID}', $this->GetCustomerID(), $postfields);
            $postfields = str_replace(urlencode('{DeviceSerialNumber}'), $this->GetDevicenumber(), $postfields);
            $postfields = str_replace(urlencode('{DeviceType}'), $this->GetDevicetype(), $postfields);
            $postfields = str_replace(urlencode('{MediaOwnerCustomerID}'), $this->GetCustomerID(), $postfields);
            $postfields = json_decode($postfields, true);
        }
        return $this->SendData("CustomCommand", null, $postfields, $url, $optpost);
    }

    private function ShowLibraryTracks(string $type)
    {
        //todo: anpassen und public machen
        $size   = 50;
        $offset = "";
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

    public function PlayAlbum(string $album, string $artist, bool $shuffle = false)
    {
        return $this->PlayCloudplayer($shuffle, ['albumArtistName' => $artist, 'albumName' => $album,]);
    }

    public function PlaySong(string $track_id)
    {
        return $this->PlayCloudplayer(false, ['trackId' => $track_id, 'playQueuePrime' => true]);
    }

    public function PlayPlaylist(string $playlist_id, bool $shuffle = false)
    {
        return $this->PlayCloudplayer($shuffle, ['playlistId' => $playlist_id, 'playQueuePrime' => true]);
    }

    public function GetLastActivities(int $count)
    {

        $getfields = [
            'size'      => $count,
            'startTime' => '',
            'offset'    => 1];
        $result    = (array) $this->SendData('Activities', $getfields);

        if ($result['http_code'] == 200) {
            return json_decode($result['body'], true);
        } else {
            return false;
        }

    }


    private function AmazonMusic(string $seedid, string $stationname)
    {
        //todo: anpassen und public machen?? Wie kann man die Funktion nutzen?
        $url        = 'https://layla.amazon.de/api/gotham/queue-and-play?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType='
                      . $this->GetDevicetype() . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        $postfields = json_decode('{"seed":"{"type":"KEY","seedId":"' . $seedid . '"}","stationName":"' . $stationname . '","seedType":"KEY"}');
        return $this->SendData('CustomCommand', null, $postfields, $url);
    }

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
        $csrf = $this->ReadPropertyString('TuneInCSRF');
        $cookie = $this->ReadPropertyString('TuneInCookie');
        $header = $this->GetHeader($csrf, $cookie);
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
        $csrf = $this->ReadPropertyString('TuneInCSRF');
        $cookie = $this->ReadPropertyString('TuneInCookie');
        $header = $this->GetHeader($csrf, $cookie);
        $postfields = '';
        $this->SendEcho($postfields, $header, $urltype, $station);
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $Ident = "EchoTuneInRemote_" . $devicenumber;
        $stationvalue = $this->GetTuneInStationPreset($station);
        if ($stationvalue > 0) {
            $this->SetValue($Ident, $stationvalue);
        }
    }



    public function PrimeStation(string $seedid)
    {
        $url = 'https://{AlexaURL}/api/gotham/queue-and-play?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype() . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        $postfields = '{"seed":"{"type":"KEY","seedId":"' . $seedid . '"}","stationName":"none","seedType":"KEY"}';
        return $this->SendData_old("PrimeStation", $url, json_decode($postfields)); //todo
    }

    public function PrimePlaylist(string $asin)
    {
        $url = 'https://{AlexaURL}/api/prime/prime-playlist-queue-and-play?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype() . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
        return $this->SendData_old("PrimePlaylist", $url, ['asin' => $asin]);
    }

    public function PlayPrimeHistoricalQueue(string $historicalid)
    {
        $url = 'https://{AlexaURL}/api/media/play-historical-queue';
        $postfields = '{"deviceType":"' . $this->GetDevicetype() . '","deviceSerialNumber":"' . $this->GetDevicenumber() . '","mediaOwnerCustomerId":"' . $this->GetCustomerID() . '","queueId":"' . $historicalid . '","service":null,"trackSource":"TRACK"}';
        return $this->SendData_old("PlayPrimeHistoricalQueue", $url, json_decode($postfields)); //todo
    }


    */

    /** Sends Request to IO and get response.
     *
     * @param string      $method
     * @param array|null  $getfields
     * @param array|null  $postfields
     * @param null|string $url
     *
     * @return mixed
     */
    private function SendData(string $method, array $getfields = null, array $postfields = null, $url = null, $optpost = null, $automation = null)
    {
        $this->SendDebug(
            __FUNCTION__,
            'Method: ' . $method . ', Getfields: ' . json_encode($getfields) . ', Postfields: ' . json_encode($postfields) . ', URL: ' . $url
            . ', Option Post: ' . (int) $optpost . ', Automation: ' . json_encode($automation), 0
        );

        $Data['DataID'] = '{8E187D67-F330-2B1D-8C6E-B37896D7AE3E}';

        $Data['Buffer'] = ['method' => $method];

        if (isset($getfields)) {
            $Data['Buffer']['getfields'] = $getfields;
        }
        if (isset($postfields)) {
            $Data['Buffer']['postfields'] = $postfields;
        }
        if (isset($url)) {
            $Data['Buffer']['url'] = $url;
        }
        if (isset($optpost)) {
            $Data['Buffer']['optpost'] = $optpost;
        }
        if (isset($automation)) {
            $Data['Buffer']['automation'] = $automation;
        }

        $ResultJSON = $this->SendDataToParent(json_encode($Data));
        $this->SendDebug(__FUNCTION__, 'Result: ' . $ResultJSON, 0);

        return json_decode($ResultJSON, true); //returns an array of http_code, body and header
    }

    public function RequestAction($Ident, $Value)
    {
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $this->SendDebug("Echo Remote:", "Request Action trigger device " . $devicenumber . " by Ident " . $Ident, 0);
        if ($Ident == "EchoRemote") {
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
        if ($Ident == "EchoShuffle") {
            $this->Shuffle($Value);
        }
        if ($Ident == "EchoRepeat") {
            $this->Repeat($Value);
        }
        if ($Ident == "EchoVolume") {
            $this->SetVolume($Value);
        }
        if ($Ident == "EchoTuneInRemote_" . $devicenumber) {
            $stationid = $this->GetTuneInStationID($Value);
            $this->TuneIn($stationid);
        }
        if ($Ident == "EchoActions") {
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
        if ($Ident == "EchoTTS") {
            $this->TextToSpeech($Value);
        }
    }

    /**
     * register profiles
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
    private function RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits, $Vartype)
    {

        if (!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, $Vartype); // 0 boolean, 1 int, 2 float, 3 string,
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if ($profile['ProfileType'] != $Vartype) {
                $this->SendDebug("Profile", 'Variable profile type does not match for profile ' . $Name, 0);
            }
        }

        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues(
            $Name, $MinValue, $MaxValue, $StepSize
        ); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
    }

    /**
     * register profile association
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
    private function RegisterProfileAssociation($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype, $Associations)
    {
        if (is_array($Associations) && sizeof($Associations) === 0) {
            $MinValue = 0;
            $MaxValue = 0;
        }
        $this->RegisterProfile($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Vartype);

        if (is_array($Associations)) {
            foreach ($Associations AS $Association) {
                IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
            }
        } else {
            $Associations = $this->$Associations;
            foreach ($Associations AS $code => $association) {
                IPS_SetVariableProfileAssociation($Name, $code, $this->Translate($association), $Icon, -1);
            }
        }
    }

    /***********************************************************
     * Configuration Form
     ***********************************************************/

    /**
     * build configuration form
     *
     * @return string
     */
    public function GetConfigurationForm()
    {
        // return current form
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
    protected function FormHead()
    {
        $form = [
            [
                'type'  => 'Label',
                'label' => 'device type:'],
            [
                'name'    => 'Devicetype',
                'type'    => 'ValidationTextBox',
                'caption' => 'device type'],
            [
                'type'  => 'Label',
                'label' => 'device number:'],
            [
                'name'    => 'Devicenumber',
                'type'    => 'ValidationTextBox',
                'caption' => 'device number'],
            [
                'type'  => 'Label',
                'label' => 'Echo update interval:'],
            [
                'name'    => 'updateinterval',
                'type'    => 'IntervalBox',
                'caption' => 'seconds'],
            [
                'type'  => 'Label',
                'label' => 'setup variables for extended info (title, album, cover, radiostation):'],
            [
                'name'    => 'ExtendedInfo',
                'type'    => 'CheckBox',
                'caption' => 'extended info'],
            [
                'type'     => 'List',
                'name'     => 'TuneInStations',
                'caption'  => 'TuneIn stations',
                'rowCount' => 32,
                'add'      => false,
                'delete'   => false,
                'sort'     => [
                    'column'    => 'position',
                    'direction' => 'ascending'],
                'columns'  => [
                    [
                        'name'    => 'position',
                        'label'   => 'Position',
                        'width'   => '95px',
                        'save'    => true,
                        'visible' => true],
                    [
                        'name'  => 'station',
                        'label' => 'TuneIn Station',
                        'width' => '350px',
                        'save'  => true,
                        'edit'  => [
                            'type' => 'ValidationTextBox']],
                    [
                        'name'    => 'station_id',
                        'label'   => 'Station ID',
                        'width'   => '250px',
                        'save'    => true,
                        'edit'    => [
                            'type' => 'ValidationTextBox'],
                        'visible' => true]],
                'values'   => $this->GetTuneInList()]];
        return $form;
    }

    private function GetTuneInList()
    {
        return [
            ['position' => 1, 'station_id' => 's17490', 'station' => 'Hit Radio FFH'],
            ['position' => 2, 'station_id' => 's84483', 'station' => 'FFH Lounge'],
            ['position' => 3, 'station_id' => 's84489', 'station' => 'FFH Rock'],
            ['position' => 4, 'station_id' => 's84481', 'station' => 'FFH Die 80er'],
            ['position' => 5, 'station_id' => 's84486', 'station' => 'FFH iTunes Top 40'],
            ['position' => 6, 'station_id' => 's84487', 'station' => 'FFH Eurodance'],
            ['position' => 7, 'station_id' => 's97088', 'station' => 'FFH Soundtrack'],
            ['position' => 8, 'station_id' => 's97089', 'station' => 'FFH Die 90er'],
            ['position' => 9, 'station_id' => 's84482', 'station' => 'FFH Schlagerkult'],
            ['position' => 10, 'station_id' => 's254526', 'station' => 'FFH iTunes Top 40'],
            ['position' => 11, 'station_id' => 's140647', 'station' => 'The Wave - relaxing radio'],
            ['position' => 12, 'station_id' => 's57109', 'station' => 'hr3'],
            ['position' => 13, 'station_id' => 's140555', 'station' => 'harmony.fm'],
            ['position' => 14, 'station_id' => 's24896', 'station' => 'SWR3'],
            ['position' => 15, 'station_id' => 's125250', 'station' => 'Deluxe Lounge Radio'],
            ['position' => 16, 'station_id' => 's17364', 'station' => 'Lounge-Radio.com'],
            ['position' => 17, 'station_id' => 's255334', 'station' => 'Bayern 3'],
            ['position' => 18, 'station_id' => 's2726', 'station' => 'planet radio'],
            ['position' => 19, 'station_id' => 's24878', 'station' => 'YOU FM'],
            ['position' => 20, 'station_id' => 's45087', 'station' => '1LIVE diggi'],
            ['position' => 21, 'station_id' => 's25005', 'station' => 'Fritz vom rbb'],
            ['position' => 22, 'station_id' => 's8007', 'station' => 'Hitradio Ö3'],
            ['position' => 23, 'station_id' => 's8954', 'station' => 'radio ffn'],
            ['position' => 24, 'station_id' => 's25531', 'station' => 'N-JOY'],
            ['position' => 25, 'station_id' => 's84203', 'station' => 'bigFM'],
            ['position' => 26, 'station_id' => 's42828', 'station' => 'Deutschlandfunk'],
            ['position' => 27, 'station_id' => 's17492', 'station' => 'NDR 2'],
            ['position' => 28, 'station_id' => 's20295', 'station' => 'DASDING'],
            ['position' => 29, 'station_id' => 's10637', 'station' => 'sunshine live'],
            ['position' => 30, 'station_id' => 's6634', 'station' => 'MDR JUMP'],
            ['position' => 31, 'station_id' => 's187256', 'station' => 'Costa Del Mar'],
            ['position' => 32, 'station_id' => 's139505', 'station' => 'Antenne Bayern'],];
    }

    /**
     * return form actions by token
     *
     * @return array
     */
    protected function FormActions()
    {
        $form = [
            [
                'type'  => 'Label',
                'label' => 'Play Radio:'],
            [
                'type'    => 'Button',
                'label'   => 'FFH Lounge',
                'onClick' => "if (EchoRemote_TuneIn(\$id, 's84483')){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'  => 'Label',
                'label' => 'Remote Control:'],
            [
                'type'    => 'Button',
                'label'   => 'Play',
                'onClick' => "if (EchoRemote_Play(\$id)){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'    => 'Button',
                'label'   => 'Pause',
                'onClick' => "if (EchoRemote_Pause(\$id)){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'  => 'Label',
                'label' => 'Modify Volume:'],
            [
                'type'    => 'Button',
                'label'   => 'Decrease Volume',
                'onClick' => "if (EchoRemote_DecreaseVolume(\$id, 3)){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'    => 'Button',
                'label'   => 'Increase Volume',
                'onClick' => "if (EchoRemote_IncreaseVolume(\$id, 3)){echo 'Ok';} else {echo 'Error';}"],
            [
                'type'  => 'Label',
                'label' => 'Voice Output:'],
            [
                'type'    => 'Button',
                'label'   => 'Speak Text',
                'onClick' => "if (EchoRemote_TextToSpeech(\$id, 'Wer hätte das gedacht. Das ist ein toller Erfolg!')){echo 'Ok';} else {echo 'Error';}"]];

        return $form;
    }

    /**
     * return from status
     *
     * @return array
     */
    protected function FormStatus()
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
}