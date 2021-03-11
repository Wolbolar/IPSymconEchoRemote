<?php

//<editor-fold desc="declarations">
declare(strict_types=1);

require_once __DIR__ . '/../libs/EchoBufferHelper.php';
require_once __DIR__ . '/../libs/EchoDebugHelper.php';
//</editor-fold>

// Modul für Amazon Echo Remote

class EchoRemote extends IPSModule
{
    use EchoBufferHelper;
    use EchoDebugHelper;
    private const STATUS_INST_DEVICETYPE_IS_EMPTY = 210; // devicetype must not be empty.
    private const STATUS_INST_DEVICENUMBER_IS_EMPTY = 211; // devicenumber must not be empty

    private $customerID = '';
    private $update_counter = 0;

    private $ParentID = 0;
    private int $position = 0;

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
            {"position":32,"station":"Antenne Bayern","station_id":"s139505"},
            {"position":33,"station":"1 Live","station_id":"s25260"}]'
        );

        //        $this->RegisterPropertyString('TuneInStations', '');
        $this->RegisterPropertyInteger('updateinterval', 0);
        $this->RegisterPropertyBoolean('DND', false);
        $this->RegisterPropertyBoolean('ExtendedInfo', false);
        $this->RegisterPropertyBoolean('AlarmInfo', false);
        $this->RegisterPropertyBoolean('ShoppingList', false);
        $this->RegisterPropertyBoolean('TaskList', false);
        $this->RegisterPropertyBoolean('Mute', true);
        $this->RegisterPropertyBoolean('Title', false);
        $this->RegisterPropertyBoolean('Cover', false);
        $this->RegisterPropertyBoolean('Subtitle1', false);
        $this->RegisterPropertyBoolean('Subtitle2', false);
        $this->RegisterPropertyInteger('TitleColor', 0);
        $this->RegisterPropertyInteger('TitleSize', 0);
        $this->RegisterPropertyInteger('Subtitle1Color', 0);
        $this->RegisterPropertyInteger('Subtitle1Size', 0);
        $this->RegisterPropertyInteger('Subtitle2Color', 0);
        $this->RegisterPropertyInteger('Subtitle2Size', 0);

        $this->SetBuffer('CoverURL', '');
        $this->SetBuffer('Volume', '');
        $this->RegisterTimer('EchoUpdate', 0, 'EchoRemote_UpdateStatus(' . $this->InstanceID . ');');
        $this->RegisterTimer('EchoAlarm', 0, 'EchoRemote_RaiseAlarm(' . $this->InstanceID . ');');
        $this->RegisterAttributeInteger('creationTimestamp', 0);
        $this->RegisterAttributeString('routines', '[]');
        $this->RegisterPropertyBoolean('routines_wf', false);

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
        //Apply filter
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $this->SetReceiveDataFilter('.*' . $devicenumber . '.*');
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

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $this->SendDebug('Receive Data', $JSONString, 0);
        $payload = $data->Buffer;
        $creationTimestamp = $payload->creationTimestamp;
        $this->SendDebug('Creation Timestamp', $creationTimestamp, 0);
        $last_timestamp = $this->ReadAttributeInteger('creationTimestamp');
        if ($last_timestamp != $creationTimestamp) {
            $this->WriteAttributeInteger('creationTimestamp', $creationTimestamp);
            $summary = $payload->summary;
            $timestamp = time();
            if(@$this->GetIDForIdent('last_action') > 0)
            {
                $this->SetValue('last_action', $timestamp);
            }
            if(@$this->GetIDForIdent('summary') > 0)
            {
                $this->SetValue('summary', $summary);
            }
        }
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
                    $this->SetValue('EchoActions', $Value);
                    $this->Weather();
                    break;
                case 1: // Traffic
                    $this->SetValue('EchoActions', $Value);
                    $this->Traffic();
                    break;
                case 2: // Flashbriefing
                    $this->SetValue('EchoActions', $Value);
                    $this->FlashBriefing();
                    break;
                case 3: // Good Morning
                    $this->SetValue('EchoActions', $Value);
                    $this->GoodMorning();
                    break;
                case 4: // Sing a song
                    $this->SetValue('EchoActions', $Value);
                    $this->SingASong();
                    break;
                case 5: // tell a story
                    $this->SetValue('EchoActions', $Value);
                    $this->TellStory();
                    break;
                case 6: // tell a joke
                    $this->SetValue('EchoActions', $Value);
                    $this->TellJoke();
                    break;
                case 7: // tell a funfact
                    $this->SetValue('EchoActions', $Value);
                    $this->TellFunFact();
                    break;
                case 8: // stop all actions
                    $this->SetValue('EchoActions', $Value);
                    $this->StopDeviceActions();
                    break;
            }
        }
        if ($Ident === 'EchoTTS') {
            $this->TextToSpeech($Value);
        }
        if ($Ident === 'Mute') {
            if ($Value) {
                $this->Mute(false);
            } else {
                $this->Mute(true);
            }
        }
        if ($Ident === 'DND') {
            if ($Value) {
                $this->DoNotDisturb(true);
            } else {
                $this->DoNotDisturb(false);
            }
        }
        if ($Ident === 'Automation') {
            $this->StartAlexaRoutineByKey($Value);
        }
    }

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
     * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:.
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

        return $result['http_code'] === 200;
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
    public function Mute(bool $mute): bool
    {
        $volume = 0;
        $this->SendDebug('Echo Remote:', 'Mute: ' . json_encode($mute), 0);
        if ($mute) {
            $this->SetValue('Mute', false);
        } else {
            $this->SetValue('Mute', true);
        }

        if ($mute) {
            $current_volume = $this->GetValue('EchoVolume');
            /** @noinspection UnnecessaryCastingInspection */
            $this->SetBuffer('Volume', (string) $current_volume);
            $this->SendDebug('Echo Remote:', 'Volume Buffer ' . $current_volume, 0);
            $volume = 0;
        }
        if (!$mute) {
            $last_volume = $this->GetBuffer('Volume');
            if ($last_volume === '') {
                $volume = 30;
                $this->SetBuffer('Volume', '30');
                $this->SendDebug('Echo Remote:', 'Volume Buffer 30', 0);
            } else {
                $volume = (int) $last_volume;
                $this->SendDebug('Echo Remote:', 'Volume Buffer ' . $last_volume, 0);
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
        if(!empty($result))
        {
            if ($result['http_code'] === 200) {
                //$this->SetValue("EchoVolume", $volume);
                return json_decode($result['body'], true);
            }
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

        $result = $this->SendData('MediaState', $getfields);

        //$url = 'https://{AlexaURL}/api/media/state?deviceSerialNumber=' . $this->GetDevicenumber() . '&deviceType=' . $this->GetDevicetype()
        //       . '&queueId=0e7d86f5-d5a4-4a3a-933e-5910c15d9d4f&shuffling=false&firstIndex=1&lastIndex=1&screenWidth=1920&_=1495289082979';

        if (isset($result['http_code']) && ($result['http_code'] === 200)) {
            return json_decode($result['body'], true);
        }

        return false;
    }

    /** GetNoticications
     *
     * @return mixed
     */
    public function GetNotifications(): ?array
    {
        $result = $this->SendData('Notifications');

        if (isset($result['http_code']) && ($result['http_code'] === 200)) {
            return json_decode($result['body'], true)['notifications'];
        }

        return null;
    }

    /** GetToDos
     *
     * @param string $type      : one of 'SHOPPING_ITEM' or 'TASK'
     * @param bool   $completed true: completed todos are returned
     *                          false: not completed todos are returned
     *                          null: all todos are returned
     *
     * @return array|null
     */
    public function GetToDos(string $type, bool $completed = null): ?array
    {
        $getfields = [
            'type' => $type, //SHOPPING_ITEM or TASK,
            'size' => 500];

        if ($completed !== null) {
            $getfields['completed'] = $completed ? 'true' : 'false';
        }

        $result = $this->SendData('ToDos', $getfields);

        if (isset($result['http_code']) && ($result['http_code'] === 200)) {
            return json_decode($result['body'], true)['values'];
        }

        return null;
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

    /** Send a text Command to an echo device
     *
     * @return bool
     */
    public function TextCommand($command): bool
    {
        return $this->PlaySequenceCmd('Alexa.TextCommand', $command);
    }

    /** Announcement
     *
     * @param string $tts
     *
     * @return array|string
     */
    public function Announcement(string $tts): bool
    {
        return $this->PlaySequenceCmd('AlexaAnnouncement', '<speak>' . $tts . '</speak>');
    }

    /**
     * Weather Forcast.
     */
    public function Weather(): bool
    {
        return $this->PlaySequenceCmd('Alexa.Weather.Play');
    }

    /**
     * Traffic.
     */
    public function Traffic(): bool
    {
        return $this->PlaySequenceCmd('Alexa.Traffic.Play');
    }

    /**
     * Flash briefing.
     */
    public function FlashBriefing(): bool
    {
        return $this->PlaySequenceCmd('Alexa.FlashBriefing.Play');
    }

    /**
     * Goodmorning.
     */
    public function GoodMorning(): bool
    {
        return $this->PlaySequenceCmd('Alexa.GoodMorning.Play');
    }

    /**
     * Sing a song.
     */
    public function SingASong(): bool
    {
        return $this->PlaySequenceCmd('Alexa.SingASong.Play');
    }

    /**
     * Tell a story.
     */
    public function TellStory(): bool
    {
        return $this->PlaySequenceCmd('Alexa.TellStory.Play');
    }

    /**
     * Tell a funfact.
     */
    public function TellFunFact(): bool
    {
        return $this->PlaySequenceCmd('Alexa.FunFact.Play');
    }

    /**
     * Tell a joke.
     */
    public function TellJoke(): bool
    {
        return $this->PlaySequenceCmd('Alexa.Joke.Play');
    }

    /**
     * Stop all current actions on device.
     */
    public function StopDeviceActions(): bool
    {
        return $this->PlaySequenceCmd('Alexa.DeviceControls.Stop');
    }

    /**
     * Clean Up need Amazon Music Unlimited.
     */
    public function CleanUp(): bool
    {
        return $this->PlaySequenceCmd('Alexa.CleanUp.Play');
    }

    /**
     * Calendar Today.
     */
    public function CalendarToday(): bool
    {
        return $this->PlaySequenceCmd('Alexa.Calendar.PlayToday');
    }

    /**
     * Calendar Tomorrow.
     */
    public function CalendarTomorrow(): bool
    {
        return $this->PlaySequenceCmd('Alexa.Calendar.PlayTomorrow');
    }

    /**
     * Calendar Next.
     */
    public function CalendarNext(): bool
    {
        return $this->PlaySequenceCmd('Alexa.Calendar.PlayNext');
    }

    /** Get state do not disturb
     * @return array|mixed|null
     */
    public function GetDoNotDisturbState()
    {
        $this->SendDebug(__FUNCTION__, 'started', 0);

        $result = $this->SendData('GetDNDState');
        $deviceSerialNumber = $this->ReadPropertyString('Devicenumber');
        if ($result['http_code'] == 200) {
            $doNotDisturbDeviceStatusList = json_decode($result['body'], true);
            $dnd_devices = $doNotDisturbDeviceStatusList['doNotDisturbDeviceStatusList'];
            foreach($dnd_devices as $dnd_device)
            {
                if($deviceSerialNumber == $dnd_device['deviceSerialNumber']){
                    $dnd = $dnd_device['enabled'];
                    $this->SendDebug('do not disturb state', strval($dnd), 0);
                    if(@$this->GetIDForIdent('DND') > 0)
                    {
                        $this->SetValue('DND', $dnd);
                    }
                }
            }
            return $result['body'];
        }
        return $result;
    }

    /** Get all automations
     *
     * @return array
     */
    public function GetAllAutomations()
    {
        //get all Automations
        $result = (array) $this->SendData('BehaviorsAutomations');

        if ($result['http_code'] !== 200) {
            return [];
        }
        return json_decode($result['body'], true);
    }

    private function GetAutomationsList()
    {
        $automations = $this->GetAllAutomations();
        $list = [];
        if(!empty($automations))
        {
            foreach ($automations as $key => $automation) {

                $routine_id = $key;
                $automationId = $automation['automationId'];
                $routine_name = $automation['name'];
                $routine_utterance = '';
                if(isset($automation['triggers'][0]['payload']['utterance']))
                {
                    $routine_utterance = $automation['triggers'][0]['payload']['utterance'];
                }
                if(is_null($routine_name))
                {
                    $routine_name = '';
                }

                $list[] = [
                    'routine_id'        => $routine_id,
                    'automationId'      => $automationId,
                    'routine_name'      => $routine_name,
                    'routine_utterance' => $routine_utterance,
                ];
            }
        }
        return $list;
    }

    /** Echo Show Display off
     *
     * @return bool
     */
    public function DisplayOff(): bool
    {
        return $this->PlaySequenceCmd('Alexa.TextCommand', '{DISPLAY_OFF}');
    }

    /** Echo Show Display on
     *
     * @return bool
     */
    public function DisplayOn(): bool
    {
        return $this->PlaySequenceCmd('Alexa.TextCommand', '{DISPLAY_ON}');
    }

    /** Show Alarm Clock
     *
     * @return bool
     */
    public function ShowAlarmClock(): bool
    {
        return $this->PlaySequenceCmd('Alexa.TextCommand', '{SHOW_ALARM_CLOCK}');
    }

    /** Set do not disturb
     *
     * @param $state
     * @return bool
     */
    public function DoNotDisturb($state): bool
    {
        $postfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype(),
            'enabled'               => $state];

        $result = (array) $this->SendData('DoNotDisturb', null, $postfields);
        IPS_Sleep(200);
        $this->GetDoNotDisturbState();

        return $result['http_code'] === 200;
    }

    /** Start Alexa Routine by utterance
     * @param string $utterance
     *
     * @return bool
     */
    public function StartAlexaRoutine(string $utterance): bool
    {
        $automations = $this->GetAllAutomations();
        if(!empty($automations))
        {
            //search Automation of utterance
            $automation = $this->GetAutomation($utterance, $automations);
            if ($automation) {
                //play automation
                $postfields = [
                    'deviceSerialNumber' => $this->GetDevicenumber(),
                    'deviceType'         => $this->GetDevicetype()];

                $result = (array) $this->SendData('BehaviorsPreviewAutomation', null, $postfields, null, null, $automation);
                return $result['http_code'] === 200;
            }
        }
        return false;
    }

    /** Start Alexa routine by routine name
     * @param int $routine_key
     *
     * @return bool
     */
    private function StartAlexaRoutineByKey(int $routine_key): bool
    {
        $automations = $this->GetAllAutomations();
        if(!empty($automations))
        {
            //search Automation of utterance
            $automation = $this->GetAutomationByKey($routine_key, $automations);
            if ($automation) {
                //play automation
                $postfields = [
                    'deviceSerialNumber' => $this->GetDevicenumber(),
                    'deviceType'         => $this->GetDevicetype()];

                $result = (array) $this->SendData('BehaviorsPreviewAutomation', null, $postfields, null, null, $automation);
                return $result['http_code'] === 200;
            }
        }
        return false;
    }

    /** Start Alexa routine by routine name
     * @param string $routine_name
     *
     * @return bool
     */
    public function StartAlexaRoutineByName(string $routine_name): bool
    {
        $automations = $this->GetAllAutomations();
        if(!empty($automations))
        {
            //search Automation of utterance
            $automation = $this->GetAutomationByName($routine_name, $automations);
            if ($automation) {
                //play automation
                $postfields = [
                    'deviceSerialNumber' => $this->GetDevicenumber(),
                    'deviceType'         => $this->GetDevicetype()];

                $result = (array) $this->SendData('BehaviorsPreviewAutomation', null, $postfields, null, null, $automation);
                return $result['http_code'] === 200;
            }
        }
        return false;
    }

    /** List paired bluetooth devices
     *
     * @return array|null
     */
    public function ListPairedBluetoothDevices(): ?array
    {
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $devices = $this->ListBluetooth();
        if ($devices) {
            foreach ($devices as $key => $device) {
                if ($devicenumber === $device['deviceSerialNumber']) {
                    return $device['pairedDeviceList'];
                }
            }
        }

        return null;
    }

    public function ConnectBluetooth(string $bluetooth_address): bool
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $postfields = ['bluetoothDeviceAddress' => $bluetooth_address];
        $result = (array) $this->SendData('BluetoothPairSink', $getfields, $postfields);

        return $result['http_code'] === 200;
    }

    public function DisconnectBluetooth(): bool
    {
        $getfields = [
            'deviceSerialNumber' => $this->GetDevicenumber(),
            'deviceType'         => $this->GetDevicetype()];

        $result = (array) $this->SendData('BluetoothDisconnectSink', $getfields);

        return $result['http_code'] === 200;
    }

    /** Get State Tune In
     *
     * @return bool
     */
    public function UpdateStatus(): bool
    {
        $this->update_counter = $this->update_counter + 1;
        if (!$result = $this->GetPlayerInformation()) {
            return false;
        }
        $this->GetDoNotDisturbState();
        $playerInfo = $result['playerInfo'];
        $this->SendDebug('Playerinfo', json_encode($playerInfo), 0);
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
                trigger_error('Instanz #' . $this->InstanceID . ' - Unexpected state: ' . $playerInfo['state']);
        }

        $imageurl = $playerInfo['mainArt']['url'] ?? null;
        $infotext = $playerInfo['infoText'];
        if (is_null($infotext)) {
            $this->SendDebug('Playerinfo Infotext', 'no information found', 0);
        } else {
            $this->SetStatePage(
                $imageurl, $playerInfo['infoText']['title'], $playerInfo['infoText']['subText1'], $playerInfo['infoText']['subText2']
            );
        }

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
                    trigger_error('Instanz #' . $this->InstanceID . ' - Unexpected repeat value: ' . $playerInfo['transport']['repeat']);
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
                    trigger_error('Instanz #' . $this->InstanceID . ' - Unexpected shuffle value: ' . $playerInfo['transport']['shuffle']);
            }
        }
        $volume = $playerInfo['volume'];
        if (is_null($volume)) {
            $this->SendDebug('Playerinfo Volume', 'no volume information found', 0);
        } else {
            if ($playerInfo['volume']['volume'] !== null) {
                $this->SetValue('EchoVolume', $playerInfo['volume']['volume']);
            }
        }

        //update Alarm
        if ($this->update_counter > 20) {
            $this->update_counter = 0;
            if ($this->ReadPropertyBoolean('AlarmInfo')) {
                $notifications = $this->GetNotifications();
                if ($notifications === null) {
                    return false;
                }

                $this->SetAlarm($notifications);
            }
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

    public function PlayAlbum(string $album, string $artist, /** @noinspection ParameterDefaultValueIsNotNullInspection */
                              bool $shuffle = false): bool
    {
        return $this->PlayCloudplayer($shuffle, ['albumArtistName' => $artist, 'albumName' => $album]);
    }

    public function PlaySong(string $track_id): bool
    {
        return $this->PlayCloudplayer(false, ['trackId' => $track_id, 'playQueuePrime' => true]);
    }

    public function PlayPlaylist(string $playlist_id, /** @noinspection ParameterDefaultValueIsNotNullInspection */ bool $shuffle = false): bool
    {
        return $this->PlayCloudplayer($shuffle, ['playlistId' => $playlist_id, 'playQueuePrime' => true]);
    }

    public function GetLastActivities(int $count)
    {
        $getfields = [
            'size'      => $count,
            'startTime' => '',
            'offset'    => 1];
        $result = (array) $this->SendData('Activities', $getfields);

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
        $url = 'https://{AlexaURL}/api/gotham/queue-and-play?';
        $getfields = [
            'deviceSerialNumber'   => $this->GetDevicenumber(),
            'deviceType'           => $this->GetDevicetype(),
            'mediaOwnerCustomerId' => $this->GetCustomerID()];
        $postfields = ['seed' => json_encode(['type' => 'KEY', 'seedId' => $seedId]), 'stationName' => $stationName, 'seedType' => 'KEY'];
        return $this->SendData('CustomCommand', $getfields, $postfields, $url)['http_code'] === 200;
    }

    public function PlayAmazonPrimePlaylist(string $asin): bool
    {
        $url = 'https://{AlexaURL}/api/prime/prime-playlist-queue-and-play?';
        $getfields = [
            'deviceSerialNumber'   => $this->GetDevicenumber(),
            'deviceType'           => $this->GetDevicetype(),
            'mediaOwnerCustomerId' => $this->GetCustomerID()];
        $postfields = ['asin' => $asin];
        return $this->SendData('CustomCommand', $getfields, $postfields, $url)['http_code'] === 200;
    }

    public function GetAmazonPrimeStationSectionList(string $filterSections, string $filterCategories, string $stationItems)
    {
        $filterSections = json_decode($filterSections, true);
        $filterCategories = json_decode($filterCategories, true);
        $stationItems = json_decode($stationItems, true);
        $getfields = [
            'deviceSerialNumber'   => $this->GetDevicenumber(),
            'deviceType'           => $this->GetDevicetype(),
            'mediaOwnerCustomerId' => $this->GetCustomerID()];
        $result = (array) $this->SendData(
            'PrimeSections', $getfields, null, null, null, null,
            ['filterSections' => $filterSections, 'filterCategories' => $filterCategories, 'stationItems' => $stationItems]
        );

        if ($result['http_code'] === 200) {
            return json_decode($result['body'], true);
        }

        return false;
    }

    public function SendDelete(string $url)
    {
        return $this->SendData('SendDelete', null, null, $url);
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

        if ($postfields === null) {
            $this->SendDebug('CustomCommand', 'URL: ' . $url . ' (no postdata)', 0);
        } else {
            $postfields = str_replace($search, $replace, $postfields);
            $postfields = json_decode($postfields, true);
        }

        return $this->SendData('CustomCommand', null, $postfields, $url, $optpost);
    }

    //<editor-fold desc="configuration form">
    /*
     * Configuration Form
     */

    /** @noinspection PhpMissingParentCallCommonInspection
     * build configuration form
     *
     * @return string
     */
    public function GetConfigurationForm(): string
    {
        return json_encode(
            [
                'elements' => $this->FormHead(),
                'actions'  => $this->FormActions(),
                'status'   => $this->FormStatus()]
        );
    }

    protected function HasActiveParent(): bool
    {
        return ($this->ParentID > 0) && (IPS_GetInstance($this->ParentID)['InstanceStatus'] === IS_ACTIVE);
    }

    /**
     * Ermittelt den Parent und verwaltet die Einträge des Parent im MessageSink
     * Ermöglicht es das Statusänderungen des Parent empfangen werden können.
     *
     * @return int ID des Parent.
     */
    protected function RegisterParent(): int
    {
        $OldParentId = $this->ParentID;
        $ParentId = @IPS_GetInstance($this->InstanceID)['ConnectionID'];
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

    /**
     * return incremented position
     * @return int
     */
    private function _getPosition()
    {
        $this->position++;
        return $this->position;
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
            'Echo.Remote', 'Move', '', '', 0, 5, 0, 0, VARIABLETYPE_INTEGER, [
                [0, $this->Translate('Rewind 30s'), 'HollowDoubleArrowLeft', -1],
                [1, $this->Translate('Previous'), 'HollowLargeArrowLeft', -1],
                [2, $this->Translate('Pause/Stop'), 'Sleep', -1],
                [3, $this->Translate('Play'), 'Script', -1],
                [4, $this->Translate('Next'), 'HollowLargeArrowRight', -1],
                [5, $this->Translate('Forward 30s'), 'HollowDoubleArrowRight', -1]]
        );
        $this->RegisterVariableInteger('EchoRemote', $this->Translate('Remote'), 'Echo.Remote', $this->_getPosition());
        $this->EnableAction('EchoRemote');

        //Shuffle Variable
        if (in_array('AMAZON_MUSIC', $caps, true)) {
            $this->RegisterVariableBoolean('EchoShuffle', $this->Translate('Shuffle'), '~Switch', $this->_getPosition());
            IPS_SetIcon($this->GetIDForIdent('EchoShuffle'), 'Shuffle');
            $this->EnableAction('EchoShuffle');
        }

        //Repeat Variable
        if (in_array('AMAZON_MUSIC', $caps, true)) {
            $this->RegisterVariableBoolean('EchoRepeat', $this->Translate('Repeat'), '~Switch', $this->_getPosition());
            IPS_SetIcon($this->GetIDForIdent('EchoRepeat'), 'Repeat');
            $this->EnableAction('EchoRepeat');
        }

        //Volume Variable
        if (in_array('VOLUME_SETTING', $caps, true)) {
            $this->RegisterVariableInteger('EchoVolume', $this->Translate('Volume'), '~Intensity.100', $this->_getPosition());
            $this->EnableAction('EchoVolume');
        }

        //Info Variable
        $this->RegisterVariableString('EchoInfo', $this->Translate('Info'), '~HTMLBox', $this->_getPosition());

        //Actions and TTS Variables
        if (in_array('FLASH_BRIEFING', $caps, true)) {
            $this->RegisterProfileAssociation(
                'Echo.Actions', 'Move', '', '', 0, 5, 0, 0, VARIABLETYPE_INTEGER, [
                    [0, $this->Translate('Weather'), '', -1],
                    [1, $this->Translate('Traffic'), '', -1],
                    [2, $this->Translate('Flash Briefing'), '', -1],
                    [3, $this->Translate('Good morning'), '', -1],
                    [4, $this->Translate('Sing a song'), '', -1],
                    [5, $this->Translate('Tell a story'), '', -1],
                    [6, $this->Translate('Tell a joke'), '', -1],
                    [7, $this->Translate('Tell a funfact'), '', -1],
                    [8, $this->Translate('Stop all actions'), '', -1]]
            );
            $this->RegisterVariableInteger('EchoActions', $this->Translate('Actions'), 'Echo.Actions', $this->_getPosition());
            $this->EnableAction('EchoActions');

            $this->RegisterVariableString('EchoTTS', $this->Translate('Text to Speech'), '', $this->_getPosition());
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
                $this->RegisterProfileAssociation($profileName, 'Music', '', '', 0, 0, 0, 0, VARIABLETYPE_INTEGER, $associations);
                $this->RegisterVariableInteger('EchoTuneInRemote_' . $devicenumber, 'TuneIn Radio', $profileName, $this->_getPosition());
                $this->EnableAction('EchoTuneInRemote_' . $devicenumber);
            }
        }

        //Extended Info
        if ($this->ReadPropertyBoolean('ExtendedInfo')) {
            $this->RegisterVariableString('Title', $this->Translate('Title'), '', $this->_getPosition());
            $this->RegisterVariableString('Subtitle_1', $this->Translate('Subtitle 1'), '', $this->_getPosition());
            $this->RegisterVariableString('Subtitle_2', $this->Translate('Subtitle 2'), '', $this->_getPosition());
            $this->CreateMediaImage('MediaImageCover', 11);
        }

        // Do not disturb
        if ($this->ReadPropertyBoolean('DND')) {
            $this->RegisterProfileAssociation(
                'Echo.Remote.DND', 'Speaker', '', '', 0, 1, 0, 0, VARIABLETYPE_BOOLEAN, [
                    [false, $this->Translate('Do not disturb off'), 'Speaker', 0x00ff55],
                    [true, $this->Translate('Do not disturb'), 'Speaker', 0xff3300]]
            );
            $this->RegisterVariableBoolean('DND', $this->Translate('Do not disturb'), 'Echo.Remote.DND', $this->_getPosition());
            $this->EnableAction('DND');
        }

        //Mute
        if ($this->ReadPropertyBoolean('Mute')) {
            //Mute Variable
            $this->RegisterProfileAssociation(
                'Echo.Remote.Mute', 'Speaker', '', '', 0, 1, 0, 0, VARIABLETYPE_BOOLEAN, [
                    [false, $this->Translate('Mute'), 'Speaker', 0xff3300],
                    [true, $this->Translate('Unmute'), 'Speaker', 0x00ff55]]
            );
            $this->RegisterVariableBoolean('Mute', $this->Translate('Mute'), 'Echo.Remote.Mute', $this->_getPosition());
            $this->EnableAction('Mute');
        }

        //support of alarm
        if ($this->ReadPropertyBoolean('AlarmInfo')) {
            $this->RegisterVariableInteger('nextAlarmTime', $this->Translate('next Alarm'), '~UnixTimestamp', $this->_getPosition());
            $this->RegisterVariableInteger('lastAlarmTime', $this->Translate('last Alarm'), '~UnixTimestamp', $this->_getPosition());
        }

        //support of ShoppingList
        if ($this->ReadPropertyBoolean('ShoppingList')) {
            $this->RegisterVariableString('ShoppingList', $this->Translate('ShoppingList'), '~HTMLBox', $this->_getPosition());
        }

        //support of TaskList
        if ($this->ReadPropertyBoolean('TaskList')) {
            $this->RegisterVariableString('TaskList', $this->Translate('TaskList'), '~HTMLBox', $this->_getPosition());
        }

        // Cover as HTML image
        if ($this->ReadPropertyBoolean('Cover')) {
            $this->RegisterVariableString('Cover_HTML', $this->Translate('Cover'), '~HTMLBox', $this->_getPosition());
        }

        // Title as HTML
        if ($this->ReadPropertyBoolean('Title')) {
            $this->RegisterVariableString('Title_HTML', $this->Translate('Title'), '~HTMLBox', $this->_getPosition());
        }

        // Subtitle 1 as HTML
        if ($this->ReadPropertyBoolean('Subtitle1')) {
            $this->RegisterVariableString('Subtitle_1_HTML', $this->Translate('Subtitle 1'), '~HTMLBox', $this->_getPosition());
        }

        // Subtitle 2 as HTML
        if ($this->ReadPropertyBoolean('Subtitle2')) {
            $this->RegisterVariableString('Subtitle_2_HTML', $this->Translate('Subtitle 2'), '~HTMLBox', $this->_getPosition());
        }

        if ($this->ReadPropertyBoolean('routines_wf')) {
            $automations = $this->GetAllAutomations();
            // automation variable
            $associations = [];
            $max = count($automations);
            foreach ($automations as $key => $automation) {
                $routine_id = $key;
                // $automationId = $automation['automationId'];
                $routine_name = $automation['name'];
                $routine_utterance = '';
                if(isset($automation['triggers'][0]['payload']['utterance']))
                {
                    $routine_utterance = $automation['triggers'][0]['payload']['utterance'];
                }
                else{
                    $routine_utterance = 'no utterance';
                }
                if(is_null($routine_name))
                {
                    $routine_name = '';
                }
                $association_name = $routine_name;
                if($routine_name == '')
                {
                    $association_name = $routine_utterance;
                }
                $associations[] = [$routine_id, $association_name, '', -1];
            }
            $this->RegisterProfileAssociation('Echo.Remote.Automation', 'Execute', '', '', 0, $max, 0, 0, VARIABLETYPE_INTEGER, $associations);
            $this->RegisterVariableInteger('Automation', 'Automation', 'Echo.Remote.Automation', $this->_getPosition());
            $this->EnableAction('Automation');
        }

        $this->RegisterVariableInteger('last_action', $this->Translate('Last Action'), '~UnixTimestamp', $this->_getPosition());
        $this->RegisterVariableString('summary', $this->Translate('Last Command'), '', $this->_getPosition());
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
     * @param null        $optpost
     * @param null        $automation
     * @param null        $additionalData
     *
     * @return mixed
     */
    private function SendData(string $method, array $getfields = null, array $postfields = null, $url = null, $optpost = null, $automation = null,
                              $additionalData = null): ?array
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

            $ret = json_decode($ResultJSON, true);
            if ($ret) {
                return $ret; //returns an array of http_code, body and header
            }
        }

        IPS_LogMessage(
            __CLASS__ . '::' . __FUNCTION__, sprintf(
                                               '\'%s\' (#%s): SendDataToParent returned with %s. $Data = %s', IPS_GetName($this->InstanceID),
                                               $this->InstanceID, json_encode($ResultJSON), json_encode($Data)
                                           )
        );

        return null;
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
            if ($Vartype === 1 || $Vartype === 2) { // 0 boolean, 1 int, 2 float, 3 string
                foreach (IPS_GetVariableProfile($Name)['Associations'] as $Association) {
                    IPS_SetVariableProfileAssociation($Name, $Association['Value'], '', '', -1);
                }
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
        $interval = $echointerval * 1000;
        $this->SetTimerInterval('EchoUpdate', $interval);
    }

    private function Covername(): string
    {
        $name = IPS_GetName($this->InstanceID);
        $search = ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß', ' '];
        $replace = ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss', '_'];
        return 'echocover' . str_replace($search, $replace, $name);
    }

    private function CreateMediaImage(string $ident, int $position): void
    {
        $covername = $this->Covername();
        $picurl = $this->GetBuffer('CoverURL'); // Cover URL
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
                $data = base64_decode($Content);
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
        $list = json_decode($list_json, true);
        $stationid = '';
        foreach ($list as $station) {
            if ($preset === $station['position']) {
                $station_name = $station['station'];
                $stationid = $station['station_id'];
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
        $list_json = $this->ReadPropertyString('TuneInStations');
        $list = json_decode($list_json, true);
        foreach ($list as $station) {
            if ($guideId === $station['station_id']) {
                $presetPosition = $station['position'];
                $station_name = $station['station'];
                $stationid = $station['station_id'];
                $this->SendDebug(__FUNCTION__, 'preset position: ' . $presetPosition, 0);
                $this->SendDebug(__FUNCTION__, 'station name: ' . $station_name, 0);
                $this->SendDebug(__FUNCTION__, 'station id: ' . $stationid, 0);
                break;
            }
        }
        return $presetPosition;
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

        return $result['http_code'] === 200;
    }

    private function GetAutomation($utterance, $automations)
    {
        foreach ($automations as $automation) {
            foreach ($automation['triggers'] as $trigger) {
                if (isset($trigger['payload']['utterance']) && $trigger['payload']['utterance'] === $utterance) {
                    return $automation;
                }
            }
        }

        return false;
    }

    private function GetAutomationByName($routine_name, $automations)
    {
        foreach ($automations as $automation) {
            if($automation['name'] === $routine_name)
            {
                return $automation;
            }
        }
        return false;
    }

    private function GetAutomationByKey($routine_key, $automations)
    {
        foreach ($automations as $key => $automation) {
            if($key === $routine_key)
            {
                return $automation;
            }
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

    private function GetHeader()
    {
        $header = '
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
.echo_title {' . $this->GetTitleCSS() . '}
.echo_subtitle1 {' . $this->GetSubtitle1CSS() . '}
.echo_subtitle2 {' . $this->GetSubtitle2CSS() . '}
.shopping_item {
	font-size: large;
}
</style>
</head>
';
        return $header;
    }

    private function GetTitleCSS()
    {
        $TitleSize = $this->ReadPropertyInteger('TitleSize') . 'em';
        $TitleColor = $this->GetColor('TitleColor');
        $this->SendDebug('Title Color', $TitleColor, 0);
        if ($TitleSize == '0em') {
            $title_css = 'font-size: xx-large;';
        } else {
            $title_css = 'font-size: ' . $TitleSize . ';
			color: #' . $TitleColor . ';';
        }
        return $title_css;
    }

    private function GetSubtitle1CSS()
    {
        $Subtitle1Size = $this->ReadPropertyInteger('Subtitle1Size') . 'em';
        $Subtitle1Color = $this->GetColor('Subtitle1Color');
        $this->SendDebug('Subtitle Color', $Subtitle1Color, 0);
        if ($Subtitle1Size == '0em') {
            $subtitle1_css = 'font-size: large;';
        } else {
            $subtitle1_css = 'font-size: ' . $Subtitle1Size . ';
			color: #' . $Subtitle1Color . ';';
        }
        return $subtitle1_css;
    }

    private function GetSubtitle2CSS()
    {
        $Subtitle2Size = $this->ReadPropertyInteger('Subtitle2Size') . 'em';
        $Subtitle2Color = $this->GetColor('Subtitle2Color');
        $this->SendDebug('Subtitle Color', $Subtitle2Color, 0);
        if ($Subtitle2Size == '0em') {
            $subtitle2_css = 'font-size: large;';
        } else {
            $subtitle2_css = 'font-size: ' . $Subtitle2Size . ';
			color: #' . $Subtitle2Color . ';';
        }
        return $subtitle2_css;
    }

    private function GetColor($property)
    {
        $color = $this->ReadPropertyInteger($property);
        if ($color == 0) {
            $hex_color = 'ffffff'; // white
        } else {
            $hex_color = dechex($color);
        }
        return $hex_color;
    }

    private function SetStatePage(string $imageurl = null, string $title = null, string $subtitle_1 = null, string $subtitle_2 = null): void
    {
        $html = '<!doctype html>
<html lang="de">' . $this->GetHeader() . '
<body>
<main class="echo_mediaplayer1">
  <section class="echo_cover"><img src="' . $imageurl . '" alt="cover" width="145" height="145" id="echocover"></section>
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

        if ($this->ReadPropertyBoolean('Cover')) {
            $this->SetValue('Cover_HTML', '<img src="' . $imageurl . '" alt="cover" />');
        }

        if ($this->ReadPropertyBoolean('Title')) {
            $this->SetValue('Title_HTML', '<div class="echo_title">' . $title . '</div>');
        }

        if ($this->ReadPropertyBoolean('Subtitle1')) {
            $this->SetValue('Subtitle_1_HTML', '<div class="echo_subtitle1">' . $subtitle_1 . '</div>');
        }

        if ($this->ReadPropertyBoolean('Subtitle2')) {
            $this->SetValue('Subtitle_2_HTML', '<div class="echo_subtitle2">' . $subtitle_2 . '</div>');
        }
    }

    private function RefreshCover(string $imageurl): void
    {
        $Content = base64_encode(file_get_contents($imageurl)); // Bild Base64 codieren
        //$this->SendDebug("Image URL", $imageurl, 0);
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
            if (($notification['type'] === 'Alarm')
                && ($notification['status'] === 'ON')
                && ($notification['deviceSerialNumber'] === IPS_GetProperty($this->InstanceID, 'Devicenumber'))) {
                $alarmTime = strtotime($notification['originalDate'] . 'T' . $notification['originalTime']);

                if ($nextAlarm === 0) {
                    $nextAlarm = $alarmTime;
                } else {
                    $nextAlarm = min($nextAlarm, $alarmTime);
                }
            }
        }

        if ($alarmTime === 0) {
            $nextAlarm = 0;
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
<html lang="de">' . $this->GetHeader() . '
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

    private function PlayCloudplayer(bool $shuffle, array $postfields): bool
    {
        $getfields = [
            'deviceSerialNumber'   => $this->GetDevicenumber(),
            'deviceType'           => $this->GetDevicetype(),
            'mediaOwnerCustomerId' => $this->GetCustomerID(),
            'shuffle'              => $shuffle ? 'true' : 'false'];

        $return = (array) $this->SendData('CloudplayerQueueandplay', $getfields, $postfields);

        return $return['http_code'] === 200;
    }

    //<editor-fold desc="not used functions">
    //**************************************************************************************************************************
    //**************************************************************************************************************************

    // die folgenden Funktionen sind noch im Test:
    /*
        private function SearchMusicTuneIn(string $query)
        {
            //todo: anpassen und public machen
            $url = 'https://layla.amazon.de/api/tunein/search?query=' . $query . '&mediaOwnerCustomerId=' . $this->GetCustomerID();
            return $this->SendData('CustomCommand', null, null, $url);
        }

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
     */
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

    /**
     * return form configurations on configuration step.
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
                'suffix'  => 'seconds',
                'minimum' => 0],
            [
                'name'    => 'DND',
                'type'    => 'CheckBox',
                'caption' => 'setup variable for Do not disturb'],
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
                'type'    => 'ExpansionPanel',
                'caption' => 'Alexa Routines',
                'items'   => [
                    [
                        'name'    => 'routines_wf',
                        'type'    => 'CheckBox',
                        'caption' => 'setup variable for Alexa routines'],
                    [
                        'type'     => 'List',
                        'name'     => 'routines',
                        'caption'  => 'Alexa Routines',
                        'rowCount' => 20,
                        'add'      => false,
                        'delete'   => false,
                        'sort'     => [
                            'column'    => 'routine_name',
                            'direction' => 'ascending'],
                        'columns'  => [
                            [
                                'name'    => 'routine_id',
                                'caption' => 'ID',
                                'width'   => '100px',
                                'save'    => true,
                                'visible' => true],
                            [
                                'name'    => 'automationId',
                                'caption' => 'automationId',
                                'width'   => '100px',
                                'save'    => true,
                                'visible' => false],
                            [
                                'name'    => 'routine_name',
                                'caption' => 'routine name',
                                'width'   => '200px',
                                'save'    => true],
                            [
                                'name'    => 'routine_utterance',
                                'caption' => 'routine utterance',
                                'width'   => 'auto',
                                'save'    => true,
                                'visible' => true]],
                        'values'   => $this->GetAutomationsList()
                   ]]],
            [
                'type'    => 'ExpansionPanel',
                'caption' => 'Layout for extended info',
                'items'   => [
                    [
                        'name'    => 'Cover',
                        'type'    => 'CheckBox',
                        'caption' => 'setup separate variable for the cover as HTML image'],
                    [
                        'name'    => 'Title',
                        'type'    => 'CheckBox',
                        'caption' => 'setup separate variable for the title as HTML'],
                    [
                        'name'    => 'TitleColor',
                        'type'    => 'SelectColor',
                        'caption' => 'title color'],
                    [
                        'type'    => 'Select',
                        'name'    => 'TitleSize',
                        'caption' => 'size title',
                        'options' => $this->SelectionFontSize()],
                    [
                        'name'    => 'Subtitle1',
                        'type'    => 'CheckBox',
                        'caption' => 'setup separate variable for the subtitle 1 as HTML'],
                    [
                        'name'    => 'Subtitle1Color',
                        'type'    => 'SelectColor',
                        'caption' => 'subtitle 1 color'],
                    [
                        'type'    => 'Select',
                        'name'    => 'Subtitle1Size',
                        'caption' => 'size subtitle 1',
                        'options' => $this->SelectionFontSize()],
                    [
                        'name'    => 'Subtitle2',
                        'type'    => 'CheckBox',
                        'caption' => 'setup separate variable for the subtitle 2 as HTML'],
                    [
                        'name'    => 'Subtitle2Color',
                        'type'    => 'SelectColor',
                        'caption' => 'subtitle 2 color'],
                    [
                        'type'    => 'Select',
                        'name'    => 'Subtitle2Size',
                        'caption' => 'size subtitle 2',
                        'options' => $this->SelectionFontSize()]]],
            [
                'type'    => 'ExpansionPanel',
                'caption' => 'TuneIn stations',
                'items'   => [
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
                                'visible' => true]]]
                ]]];
    }

    private function SelectionFontSize()
    {
        $selection = [
            [
                'label' => 'Please select a font size',
                'value' => 0],
            [
                'label' => '1em',
                'value' => 1],
            [
                'label' => '2em',
                'value' => 2],
            [
                'label' => '3em',
                'value' => 3],
            [
                'label' => '4em',
                'value' => 4],
            [
                'label' => '5em',
                'value' => 5],
            [
                'label' => '6em',
                'value' => 6],
            [
                'label' => '7em',
                'value' => 7],
            [
                'label' => '8em',
                'value' => 8],
            [
                'label' => '9em',
                'value' => 9],
            [
                'label' => '10em',
                'value' => 10],
            [
                'label' => '11em',
                'value' => 11],
            [
                'label' => '12em',
                'value' => 12],
            [
                'label' => '13em',
                'value' => 13],
            [
                'label' => '14em',
                'value' => 14],
            [
                'label' => '15em',
                'value' => 15],
            [
                'label' => '16em',
                'value' => 16],
            [
                'label' => '17em',
                'value' => 17],
            [
                'label' => '18em',
                'value' => 18],
            [
                'label' => '19em',
                'value' => 19],
            [
                'label' => '20em',
                'value' => 20],
            [
                'label' => '21em',
                'value' => 21],
            [
                'label' => '22em',
                'value' => 22],
            [
                'label' => '23em',
                'value' => 23],
            [
                'label' => '24em',
                'value' => 24],
            [
                'label' => '25em',
                'value' => 25],
            [
                'label' => '26em',
                'value' => 26],
            [
                'label' => '27em',
                'value' => 27],
            [
                'label' => '28em',
                'value' => 28],
            [
                'label' => '29em',
                'value' => 29],
            [
                'label' => '30em',
                'value' => 30],
            [
                'label' => '31em',
                'value' => 31],
            [
                'label' => '32em',
                'value' => 32],
            [
                'label' => '33em',
                'value' => 33],
            [
                'label' => '34em',
                'value' => 34],
            [
                'label' => '35em',
                'value' => 35],
            [
                'label' => '36em',
                'value' => 36],
            [
                'label' => '37em',
                'value' => 37],
            [
                'label' => '38em',
                'value' => 38],
            [
                'label' => '39em',
                'value' => 39],
            [
                'label' => '40em',
                'value' => 40]];
        return $selection;
    }

    /**
     * return form actions by token.
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
     * return from status.
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
