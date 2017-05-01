<?
// Modul für Amazon Echo Remote

class EchoRemote extends IPSModule
{
		
    public function Create()
    {
		//Never delete this line!
        parent::Create();
		
		//These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
		
        $this->RegisterPropertyString("Devicetype", "");
		$this->RegisterPropertyString("Devicenumber", "");
		$this->RegisterPropertyString("CustomerID", "");
		$this->RegisterPropertyString("TuneInCSRF", "");
		$this->RegisterPropertyString("TuneInCookie", "");
		$this->RegisterPropertyString("AmazonCSRF", "");
		$this->RegisterPropertyString("AmazonCookie", "");
	}
	
    public function ApplyChanges()
    {
		//Never delete this line!
        parent::ApplyChanges();
		
		$echoremoteass =  Array(
					Array(1, "Rewind 30s",  "HollowDoubleArrowLeft", -1),
					Array(2, "Previous",  "HollowLargeArrowLeft", -1),
					Array(3, "Pause/Stop",  "Sleep", -1),
					Array(4, "Play",  "Script", -1),
					Array(5, "Next",  "HollowLargeArrowRight", -1),
					Array(6, "Forward 30s",  "HollowDoubleArrowRight", -1)
				);
						
		$this->RegisterProfileIntegerAss("Echo.Remote", "Move", "", "", 1, 6, 0, 0, $echoremoteass);
		$this->RegisterVariableInteger("EchoRemote", "Echo Remote", "Echo.Remote", 1);
		$this->EnableAction("EchoRemote");
		$this->RegisterVariableBoolean("EchoShuffle", "Echo Shuffle", "~Switch", 2);
		IPS_SetIcon($this->GetIDForIdent("EchoShuffle"), "Shuffle");
		$this->EnableAction("EchoShuffle");
		$this->RegisterVariableBoolean("EchoRepeat", "Echo Repeat", "~Switch", 3);
		IPS_SetIcon($this->GetIDForIdent("EchoRepeat"), "Repeat");
		$this->EnableAction("EchoRepeat");
		$this->RegisterVariableFloat("EchoVolume", "Volume", "~Intensity.1", 4);
		$this->EnableAction("EchoVolume");
		/*
		$tuneinstationass =  Array(
					Array(1, "FFH",  "", -1),
					Array(2, "Previous",  "", -1),
					Array(3, "Pause/Stop",  "", -1),
					Array(4, "Play",  "", -1),
					Array(5, "Next",  "", -1),
					Array(6, "Forward 30s",  "", -1),
					Array(7, "Shuffle",  "", -1),
					Array(8, "Repeat",  "", -1)
				);
						
		$this->RegisterProfileIntegerAss("Echo.TuneInStation", "Music", "", "", 1, 8, 0, 0, $tuneinstationass);
		$this->RegisterVariableInteger("EchoTuneInRemote", "Echo TuneIn Radio", "Echo.TuneInStation", 5);
		$this->EnableAction("EchoTuneInRemote");
		*/
		$this->ValidateConfiguration();	
	
    }

		/**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        *
        */
	protected $lockgetConfig = false;	

	
	private function ValidateConfiguration()
	{
		$change = false;
				
		$devicetype = $this->ReadPropertyString('Devicetype');
		$devicenumber = $this->ReadPropertyString('Devicenumber');
		$alexacustomerid = $this->ReadPropertyString('CustomerID');
		$tuneincsrf = $this->ReadPropertyString('TuneInCSRF');
		$tuneincookie = $this->ReadPropertyString('TuneInCookie');
		$amazonmusiccrsf = $this->ReadPropertyString('AmazonCSRF');
		$amazonmusiccookie = $this->ReadPropertyString('AmazonCookie');
		
		if ($devicetype == "")
		{
			$this->SetStatus(210); // Devicetype darf nicht leer sein
		}
		if ($devicenumber == "")
		{
			$this->SetStatus(211); // devicenumber darf nicht leer sein
		}
		if ($alexacustomerid == "")
		{
			$this->SetStatus(212); // alexacustomerid darf nicht leer sein
		}
		if ($tuneincsrf == "")
		{
			$this->SetStatus(213); // tuneincsrf darf nicht leer sein
		}
		if ($tuneincookie == "")
		{
			$this->SetStatus(214); // tuneincookie darf nicht leer sein
		}
		/*
		if ($amazonmusiccrsf == "")
		{
			$this->SetStatus(215); // amazonmusiccrsf darf nicht leer sein
		}
		if ($amazonmusiccookie == "")
		{
			$this->SetStatus(216); // amazonmusiccookie darf nicht leer sein
		}
		*/
		
		/*
		//IP prüfen
		if (!filter_var($ip, FILTER_VALIDATE_IP) === false)
			{
				$this->SetParentIP();
			}
		else
			{
			$this->SetStatus(203); //IP Adresse ist ungültig 
			}
		$change = false;	
		//Email und Passwort prüfen
		if ($email == "" || $password == "")
			{
				$this->SetStatus(205); //Felder dürfen nicht leer sein
			}
		elseif ($email !== "" && $password !== "" && (!filter_var($ip, FILTER_VALIDATE_IP) === false))
			{
				$userauthtokenid = @$this->GetIDForIdent("HarmonyUserAuthToken");
				if ($userauthtokenid === false)
					{
						//User Auth Token
						$userauthtokenid = $this->RegisterVariableString("HarmonyUserAuthToken", "User Auth Token", "~String", 1);
						IPS_SetHidden($userauthtokenid, true);
						$this->EnableAction("HarmonyUserAuthToken");
					
					}
				else
					{
						//Variable UserAuthToken existiert bereits
						
					}
				//Session Token
				$sessiontokenid = @$this->GetIDForIdent("HarmonySessionToken");
				if ($sessiontokenid === false)
					{
						$sessiontokenid = $this->RegisterVariableString("HarmonySessionToken", "SessionToken", "~String", 1);
						IPS_SetHidden($sessiontokenid, true);
						$this->EnableAction("HarmonySessionToken");
					}
				
				
				$userauthtoken = GetValue($userauthtokenid);	
				if($userauthtoken == "")
					{
						$this->RegisterUser($email, $password, $userauthtokenid);
					}
				$change = true;	
			}
		
		//Import Kategorie für HarmonyHub Geräte
		$ImportCategoryID = $this->ReadPropertyInteger('ImportCategoryID');
		if ( $ImportCategoryID === 0)
			{
				// Status Error Kategorie zum Import auswählen
				$this->SetStatus(206);
			}
		elseif ( $ImportCategoryID != 0)	
			{
				// Status Aktiv
				$this->SetStatus(102);
			}
		*/	
		$this->SetStatus(102);
	}
	
		
	/* Send to Echo API
	* 
	*/
	
	protected function SendEcho($postfields, $header, $urltype, $tuneinstation = null)
	{
		$devicetype = $this->ReadPropertyString('Devicetype');
		$devicenumber = $this->ReadPropertyString('Devicenumber');
		$alexacustomerid = $this->ReadPropertyString('CustomerID');
		$tuneincsrf = $this->ReadPropertyString('TuneInCSRF');
		$tuneincookie = $this->ReadPropertyString('TuneInCookie');
		$amazonmusiccsrf = $this->ReadPropertyString('AmazonCSRF');
		$amazonmusiccookie = $this->ReadPropertyString('AmazonCookie');
		
		$command_url = 'https://layla.amazon.de/api/np/command?deviceSerialNumber=' . $devicenumber . '&deviceType=' . $devicetype;
		$tunein_url = 'https://layla.amazon.de/api/tunein/queue-and-play?deviceSerialNumber=' . $devicenumber . '&deviceType=' . $devicetype . '&guideId='.$tuneinstation.'&contentType=station&callSign=&mediaOwnerCustomerId=' . $alexacustomerid;
		$amazon_music_url = 'https://layla.amazon.de/api/gotham/queue-and-play?deviceSerialNumber=' . $devicenumber . '&deviceType=' . $devicetype . '&mediaOwnerCustomerId=' . $alexacustomerid;
		
		if ($urltype == "command")
		{
			$url = $command_url;
		}
		elseif ($urltype == "tunein")
		{
			$url = $tunein_url;
		}
		elseif ($urltype == "amazonmusic")
		{
			$url = $amazon_music_url;
		}
		

		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_USERAGENT, "IPSymcon4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		$this->SendDebug("Echo:","Send to URL : ".print_r($url,true),0);
		$this->SendDebug("Echo:","Response : ".print_r($result,true),0);
		curl_close($ch);
		return $result;
	}
		
	protected function GetHeader($csrf, $cookie)
	{
		$http_headers = array(
		'Access-Control-Request-Method: POST',
		'Origin: http://alexa.amazon.de',
		'Accept-Encoding: gzip, deflate, sdch, br',
		'Accept-Language: de-DE,de;q=0.8,en-US;q=0.6,en;q=0.4',
		'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
		'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
		'Accept: application/json, text/javascript, */*; q=0.01',
		'Referer: http://alexa.amazon.de/spa/index.html',
		'csrf: '.$csrf,
		'Cookie: '.$cookie,
		'Connection: keep-alive'
		);
		return $http_headers;
	}
	
	public function Play()
	{
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"PlayCommand","contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function Pause()
	{
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"PauseCommand","contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function Next()
	{
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"NextCommand","contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function Previous()
	{
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"PreviousCommand","contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function SetVolume(float $volume) // float 0 bis 1 100% = 1
	{
		$volume = $volume*100;
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"VolumeLevelCommand","volumeLevel":'.$volume.', "contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function Rewind30s()
	{
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"RewindCommand"}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function Forward30s()
	{
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"ForwardCommand"}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function Shuffle(bool $value)
	{
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"ShuffleCommand","shuffle":' . ($value ? 'true' : 'false') . '}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function Repeat(bool $value)
	{
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '{"type":"RepeatCommand","repeat":' . ($value ? 'true' : 'false') . '}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function TuneIn(string $station)
	{
		$urltype = "tunein";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$post_fields = '';
		$this->SendEcho($postfields, $header, $urltype, $station);
	}
	
	public function AmazonMusic()
	{
		$seedid = "xx";
		$stationname = "blub";
		$urltype = "amazonmusic";
		$amazonmusiccsrf = $this->ReadPropertyString('AmazonCSRF');
		$amazonmusiccookie = $this->ReadPropertyString('AmazonCookie');
		$header = $this->GetHeader($amazonmusiccsrf, $amazonmusiccookie);
		$postfields = '{"seed":"{\"type\":\"KEY\",\"seedId\":\"'.$seedid.'\"}","stationName":"'.$stationname.'","seedType":"KEY"}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	private function SetParentIP()
	{
		$change = false;
		//$this->SetStatus(102); //IP Adresse ist gültig -> aktiv
				
		// Zwangskonfiguration des ClientSocket
		$ParentID = $this->GetParent();
		if (!($ParentID === false))
			{
				if (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('Host'))
					{
						IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('Host'));
						$change = true;
					}
				if (IPS_GetProperty($ParentID, 'Port') <> $this->ReadPropertyInteger('Port'))
					{
						IPS_SetProperty($ParentID, 'Port', $this->ReadPropertyInteger('Port'));
						$change = true;
					}
					$ParentOpen = $this->ReadPropertyBoolean('Open');
						
			// Keine Verbindung erzwingen wenn IP Harmony Hub leer ist, sonst folgt später Exception.
				if (!$ParentOpen)
						$this->SetStatus(104);

				if ($this->ReadPropertyString('Host') == '')
					{
						if ($ParentOpen)
								$this->SetStatus(202);
						$ParentOpen = false;
					}
				if (IPS_GetProperty($ParentID, 'Open') <> $ParentOpen)
					{
						IPS_SetProperty($ParentID, 'Open', $ParentOpen);
						$change = true;	
					}
				if ($change)
				{
					@IPS_ApplyChanges($ParentID);
					// Socket vor Trennung durch Hub wieder neu aufbauen
					$this->RegisterTimer('Update', 55, 'HarmonyHub_UpdateSocket($id)');
					// Ping senden statt Socket neu Aufbau, Funktioniert zur Zeit noch nicht zuverlässig
					//$this->RegisterTimer('Update', 55, 'HarmonyHub_Ping($id)');
				}
					
			}
		return $change;		
	}
	
	
	
	
	
################## DUMMYS / WOARKAROUNDS - protected

    protected function GetParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        return ($instance['ConnectionID'] > 0) ? $instance['ConnectionID'] : false;
    }

    protected function HasActiveParent()
    {
        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance['ConnectionID'] > 0)
        {
            $parent = IPS_GetInstance($instance['ConnectionID']);
            if ($parent['InstanceStatus'] == 102)
                return true;
        }
        return false;
    }

    protected function RequireParent($ModuleID, $Name = '')
    {

        $instance = IPS_GetInstance($this->InstanceID);
        if ($instance['ConnectionID'] == 0)
        {

            $parentID = IPS_CreateInstance($ModuleID);
            $instance = IPS_GetInstance($parentID);
            if ($Name == '')
                IPS_SetName($parentID, $instance['ModuleInfo']['ModuleName']);
            else
                IPS_SetName($parentID, $Name);
            IPS_ConnectInstance($this->InstanceID, $parentID);
        }
    }
	
	public function RequestAction($Ident, $Value)
    {
        if($Ident == "EchoRemote")
		{
			switch($Value) 
			{
                    case 1: // Rewind30s
						$this->Rewind30s(); 
                        break;
                    case 2: // Previous
                        $this->Previous();
                        break;
                    case 3: // Pause / Stop
                        $this->Pause();
                        break;
                    case 4: // Play
                        $this->Play();
                        break;
					case 5: // Next
                        $this->Next();
                        break;
					case 6: // Forward30s
                        $this->Forward30s();
                        break;		
			}
		}
		if($Ident == "EchoShuffle")
		{
			$this->Shuffle($Value);
		}
		if($Ident == "EchoRepeat")
		{
			$this->Repeat($Value);
		}
		if($Ident == "EchoVolume")
		{
			$this->SetVolume($Value);
		}
		SetValue($this->GetIDForIdent($Ident), $Value);
    }
		
	//Profile
	protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
        
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
		IPS_SetVariableProfileDigits($Name, $Digits); //  Nachkommastellen
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize); // string $ProfilName, float $Minimalwert, float $Maximalwert, float $Schrittweite
        
    }
	
	protected function RegisterProfileIntegerAss($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits, $Associations)
	{
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } 
		/*
		else {
            //undefiened offset
			$MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        */
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $Stepsize, $Digits);
        
		//boolean IPS_SetVariableProfileAssociation ( string $ProfilName, float $Wert, string $Name, string $Icon, integer $Farbe )
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }	
	//-- Harmony API
	
	//Configuration Form
	public function GetConfigurationForm()
	{
		$alexashsobjid = $this->GetAlexaSmartHomeSkill();
		$formhead = $this->FormHead();
		$formselection = $this->FormSelection($alexashsobjid);
		$formactions = $this->FormActions();
		$formelementsend = '{ "type": "Label", "label": "__________________________________________________________________________________________________" }';
		$formstatus = $this->FormStatus();
			
		if($alexashsobjid > 0)
		{
			return	'{ '.$formhead.$formselection.$formelementsend.'],'.$formactions.$formstatus.' }';
		}
		else
		{
			return	'{ '.$formhead.$formelementsend.'],'.$formactions.$formstatus.' }';
		}	
	}
				
	protected function FormSelection()
	{			 
		$form = '';
		/*
		if ($alexashsobjid > 0)
		{
			$form = '{ "type": "Label", "label": "Alexa Smart Home Skill is available in IP-Symcon"},
				{ "type": "Label", "label": "Would you like to create Scripts for Alexa for Harmony actions and links in the SmartHomeSkill instace?" },
				{ "type": "CheckBox", "name": "Alexa", "caption": "Create Links and Scripts for Amazon Echo / Dot" },';
		}
		*/
		return $form;
	}
		
	protected function FormHead()
	{
		$form = '"elements":
            [
                { "type": "Label", "label": "Devicetype" },
                {"type": "ValidationTextBox", "name": "Devicetype", "caption": "Devicetype" },
				{ "type": "Label", "label": "Devicenumber" },
                {"type": "ValidationTextBox", "name": "Devicenumber", "caption": "Devicenumber" },
				{ "type": "Label", "label": "CustomerID" },
                {"type": "ValidationTextBox", "name": "CustomerID", "caption": "CustomerID" },
				{ "type": "Label", "label": "Amazon Music CSRF" },
                {"type": "ValidationTextBox", "name": "AmazonCSRF", "caption": "Amazon Music CSRF" },
				{ "type": "Label", "label": "Amazon Music Cookie" },
				{"type": "ValidationTextBox", "name": "AmazonCookie", "caption": "Amazon Music Cookie" },
				{ "type": "Label", "label": "TuneIn CSRF" },
                {"type": "ValidationTextBox", "name": "TuneInCSRF", "caption": "TuneIn CSRF" },
				{ "type": "Label", "label": "TuneIn Cookie" },
				{"type": "ValidationTextBox", "name": "TuneInCookie", "caption": "TuneIn Cookie" },';
			
		return $form;
	}
		
	protected function FormActions()
	{
		$form = '"actions":
			[
				{ "type": "Label", "label": "Start Play:" },
				{ "type": "Button", "label": "Play", "onClick": "EchoRemote_Play($id);" },
				{ "type": "Label", "label": "Start Pause:" },
				{ "type": "Button", "label": "Pause", "onClick": "EchoRemote_Pause($id);" }
			],';
		return  $form;
	}	
		
	protected function FormStatus()
	{
		$form = '"status":
            [
                {
                    "code": 101,
                    "icon": "inactive",
                    "caption": "Creating instance."
                },
				{
                    "code": 102,
                    "icon": "active",
                    "caption": "Echo Remote is activ."
                },
                {
                    "code": 104,
                    "icon": "inactive",
                    "caption": "interface closed."
                },
                {
                    "code": 202,
                    "icon": "error",
                    "caption": "Echo IP adress must not empty."
                },
				{
                    "code": 203,
                    "icon": "error",
                    "caption": "No valid IP adress."
                },
                {
                    "code": 204,
                    "icon": "error",
                    "caption": "connection to the Echo lost."
                },
				{
                    "code": 205,
                    "icon": "error",
                    "caption": "field must not be empty."
                },
				{
                    "code": 206,
                    "icon": "error",
                    "caption": "select category for import."
                },
				{
                    "code": 210,
                    "icon": "error",
                    "caption": "devicetype field must not be empty."
                },
				{
                    "code": 211,
                    "icon": "error",
                    "caption": "devicenumber field must not be empty."
                },
				{
                    "code": 212,
                    "icon": "error",
                    "caption": "alexacustomerid field must not be empty."
                },
				{
                    "code": 213,
                    "icon": "error",
                    "caption": "tuneincsrf field must not be empty."
                },
				{
                    "code": 214,
                    "icon": "error",
                    "caption": "tuneincookie field must not be empty."
                },
				{
                    "code": 215,
                    "icon": "error",
                    "caption": "amazonmusiccrsf field must not be empty."
                },
				{
                    "code": 216,
                    "icon": "error",
                    "caption": "amazonmusiccookie field must not be empty."
                }
            ]';
		return $form;
	}
		
	protected function GetAlexaSmartHomeSkill()
	{
		$InstanzenListe = IPS_GetInstanceListByModuleID("{3F0154A4-AC42-464A-9E9A-6818D775EFC4}"); // IQL4SmartHome
		$IQL4SmartHomeID = @$InstanzenListe[0];
		if(!$IQL4SmartHomeID > 0)
		{
			$IQL4SmartHomeID = false;
		}
		return $IQL4SmartHomeID;
	}
	
	
	
}

?>