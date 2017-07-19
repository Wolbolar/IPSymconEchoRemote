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
		
		
		$this->RegisterPropertyString("TuneIn1", "Hit Radio FFH");
		$this->RegisterPropertyString("TuneIn1StationID", "s17490");
		$this->RegisterPropertyString("TuneIn2", "FFH Lounge");
		$this->RegisterPropertyString("TuneIn2StationID", "s84483");
		$this->RegisterPropertyString("TuneIn3", "FFH Rock");
		$this->RegisterPropertyString("TuneIn3StationID", "s84489");
		$this->RegisterPropertyString("TuneIn4", "FFH Die 80er");
		$this->RegisterPropertyString("TuneIn4StationID", "s84481");
		$this->RegisterPropertyString("TuneIn5", "FFH iTunes Top 40");
		$this->RegisterPropertyString("TuneIn5StationID", "s84486");
		$this->RegisterPropertyString("TuneIn6", "FFH Eurodance");
		$this->RegisterPropertyString("TuneIn6StationID", "s84487");
		$this->RegisterPropertyString("TuneIn7", "FFH Soundtrack");
		$this->RegisterPropertyString("TuneIn7StationID", "s97088");
		$this->RegisterPropertyString("TuneIn8", "FFH Die 90er");
		$this->RegisterPropertyString("TuneIn8StationID", "s97089");
		$this->RegisterPropertyString("TuneIn9", "FFH Schlagerkult");
		$this->RegisterPropertyString("TuneIn9StationID", "s84482");
		$this->RegisterPropertyString("TuneIn10", "FFH Leider Geil");
		$this->RegisterPropertyString("TuneIn10StationID", "s254526");
		$this->RegisterPropertyString("TuneIn11", "The Wave - relaxing radio");
		$this->RegisterPropertyString("TuneIn11StationID", "s140647");
		$this->RegisterPropertyString("TuneIn12", "hr3");
		$this->RegisterPropertyString("TuneIn12StationID", "s57109");
		$this->RegisterPropertyString("TuneIn13", "harmony.fm");
		$this->RegisterPropertyString("TuneIn13StationID", "s140555");
		$this->RegisterPropertyString("TuneIn14", "SWR3");
		$this->RegisterPropertyString("TuneIn14StationID", "s24896");
		$this->RegisterPropertyString("TuneIn15", "Deluxe Lounge Radio");
		$this->RegisterPropertyString("TuneIn15StationID", "s125250");
		$this->RegisterPropertyString("TuneIn16", "Lounge-Radio.com");
		$this->RegisterPropertyString("TuneIn16StationID", "s17364");
		$this->RegisterPropertyString("TuneIn17", "Bayern 3");
		$this->RegisterPropertyString("TuneIn17StationID", "s255334");
		$this->RegisterPropertyString("TuneIn18", "planet radio");
		$this->RegisterPropertyString("TuneIn18StationID", "s2726");
		$this->RegisterPropertyString("TuneIn19", "YOU FM");
		$this->RegisterPropertyString("TuneIn19StationID", "s24878");
		$this->RegisterPropertyString("TuneIn20", "1LIVE diggi");
		$this->RegisterPropertyString("TuneIn20StationID", "s45087");
		$this->RegisterPropertyString("TuneIn21", "Fritz vom rbb");
		$this->RegisterPropertyString("TuneIn21StationID", "s25005");
		$this->RegisterPropertyString("TuneIn22", "Hitradio Ö3");
		$this->RegisterPropertyString("TuneIn22StationID", "s8007");
		$this->RegisterPropertyString("TuneIn23", "radio ffn");
		$this->RegisterPropertyString("TuneIn23StationID", "s8954");
		$this->RegisterPropertyString("TuneIn24", "N-JOY");
		$this->RegisterPropertyString("TuneIn24StationID", "s25531");
		$this->RegisterPropertyString("TuneIn25", "bigFM");
		$this->RegisterPropertyString("TuneIn25StationID", "s84203");
		$this->RegisterPropertyString("TuneIn26", "Deutschlandfunk");
		$this->RegisterPropertyString("TuneIn26StationID", "s42828");
		$this->RegisterPropertyString("TuneIn27", "NDR 2");
		$this->RegisterPropertyString("TuneIn27StationID", "s17492");
		$this->RegisterPropertyString("TuneIn28", "DASDING");
		$this->RegisterPropertyString("TuneIn28StationID", "s20295");
		$this->RegisterPropertyString("TuneIn29", "sunshine live");
		$this->RegisterPropertyString("TuneIn29StationID", "s10637");
		$this->RegisterPropertyString("TuneIn30", "MDR JUMP");
		$this->RegisterPropertyString("TuneIn30StationID", "s6634");
		$this->RegisterPropertyString("TuneIn31", "Costa Del Mar");
		$this->RegisterPropertyString("TuneIn31StationID", "s187256");
		$this->RegisterPropertyString("TuneIn32", "Antenne Bayern");
		$this->RegisterPropertyString("TuneIn32StationID", "s139505");
		

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
		
		if ($devicetype != "" && $devicenumber != "" && $alexacustomerid != "" && $tuneincsrf != "" && $tuneincookie != "")
		{
			$tuneinstations = $this->GetTuneInStations();
				
			$tuneinstationass =  Array(
					Array(1, $tuneinstations[1]["name"],  "", -1),
					Array(2, $tuneinstations[2]["name"],  "", -1),
					Array(3, $tuneinstations[3]["name"],  "", -1),
					Array(4, $tuneinstations[4]["name"],  "", -1),
					Array(5, $tuneinstations[5]["name"],  "", -1),
					Array(6, $tuneinstations[6]["name"],  "", -1),
					Array(7, $tuneinstations[7]["name"],  "", -1),
					Array(8, $tuneinstations[8]["name"],  "", -1),
					Array(9, $tuneinstations[9]["name"],  "", -1),
					Array(10, $tuneinstations[10]["name"],  "", -1),
					Array(11, $tuneinstations[11]["name"],  "", -1),
					Array(12, $tuneinstations[12]["name"],  "", -1),
					Array(13, $tuneinstations[13]["name"],  "", -1),
					Array(14, $tuneinstations[14]["name"],  "", -1),
					Array(15, $tuneinstations[15]["name"],  "", -1),
					Array(16, $tuneinstations[16]["name"],  "", -1),
					Array(17, $tuneinstations[17]["name"],  "", -1),
					Array(18, $tuneinstations[18]["name"],  "", -1),
					Array(19, $tuneinstations[19]["name"],  "", -1),
					Array(20, $tuneinstations[20]["name"],  "", -1),
					Array(21, $tuneinstations[21]["name"],  "", -1),
					Array(22, $tuneinstations[22]["name"],  "", -1),
					Array(23, $tuneinstations[23]["name"],  "", -1),
					Array(24, $tuneinstations[24]["name"],  "", -1),
					Array(25, $tuneinstations[25]["name"],  "", -1),
					Array(26, $tuneinstations[26]["name"],  "", -1),
					Array(27, $tuneinstations[27]["name"],  "", -1),
					Array(28, $tuneinstations[28]["name"],  "", -1),
					Array(29, $tuneinstations[29]["name"],  "", -1),
					Array(30, $tuneinstations[30]["name"],  "", -1),
					Array(31, $tuneinstations[31]["name"],  "", -1),
					Array(32, $tuneinstations[32]["name"],  "", -1)
				);
						
			$this->RegisterProfileIntegerAss("Echo.TuneInStation.".$devicenumber, "Music", "", "", 1, 32, 0, 0, $tuneinstationass);
			$this->RegisterVariableInteger("EchoTuneInRemote_".$devicenumber, "Echo TuneIn Radio", "Echo.TuneInStation.".$devicenumber, 5);
			$this->EnableAction("EchoTuneInRemote_".$devicenumber);
			$this->SetStatus(102);
		}
		
		
	}
	
	protected function GetTuneInStations()
	{
		$tuneinstations = array ();
		for ($i=1; $i<=32; $i++)
		{
			${"tunein".$i} = $this->ReadPropertyString('TuneIn'.$i);
			${"tunein".$i."stationid"} = $this->ReadPropertyString('TuneIn'.$i.'StationID');
			$tuneinstations[$i]["name"] = ${"tunein".$i};
			$tuneinstations[$i]["stationid"] = ${"tunein".$i."stationid"};
		}
		return $tuneinstations;
	}
	
	protected function GetTuneInStationID($value)
	{
		$tuneinstations = array ();
		for ($i=1; $i<=32; $i++)
		{
			${"tunein".$i} = $this->ReadPropertyString('TuneIn'.$i);
			${"tunein".$i."stationid"} = $this->ReadPropertyString('TuneIn'.$i.'StationID');
			$tuneinstations[$i]["name"] = ${"tunein".$i};
			$tuneinstations[$i]["stationid"] = ${"tunein".$i."stationid"};
		}
		$stationid = $tuneinstations[$value]["stationid"];
		return $stationid;
	}
	
	protected function GetTuneInStationPreset($station)
	{
		$stationpreset = false;
		for ($i=1; $i<=32; $i++)
		{
			${"tunein".$i."stationid"} = $this->ReadPropertyString('TuneIn'.$i.'StationID');
			if (${"tunein".$i."stationid"} == $station)
				{
					$stationpreset = $i;
				}
		}
		return $stationpreset;
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
		$imported_music_url = 'https://layla.amazon.de/api/cloudplayer/queue-and-play?deviceSerialNumber=' . $devicenumber . '&deviceType=' . $devicetype . '&shuffle=false&mediaOwnerCustomerId=' . $alexacustomerid;
		
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
		elseif ($urltype == "importedmusic")
		{
			$url = $imported_music_url;
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
	
	protected function SendGETEcho($header, $url)
	{
		$devicetype = $this->ReadPropertyString('Devicetype');
		$devicenumber = $this->ReadPropertyString('Devicenumber');
		$alexacustomerid = $this->ReadPropertyString('CustomerID');
		$tuneincsrf = $this->ReadPropertyString('TuneInCSRF');
		$tuneincookie = $this->ReadPropertyString('TuneInCookie');
		$amazonmusiccsrf = $this->ReadPropertyString('AmazonCSRF');
		$amazonmusiccookie = $this->ReadPropertyString('AmazonCookie');
		
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_USERAGENT, "IPSymcon4");
		curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		$this->SendDebug("Echo:","Send to URL : ".print_r($url,true),0);
		$this->SendDebug("Echo:","Response : ".print_r($result,true),0);
		curl_close($ch);
		return $result;
	}
		
	protected function GetHeader($csrf, $cookie, $type)
	{
		if($type == "POST")
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
		}
		elseif($type == "GET")
		{
			$http_headers = array(
			'Host: layla.amazon.de',
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
		}
		return $http_headers;
	}
	
	public function Play()
	{
		$this->SendDebug("Echo Remote:","Request Action Play",0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"PlayCommand","contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoRemote";
		$this->EchoSetValue($Ident, 4);
	}
	
	public function Pause()
	{
		$this->SendDebug("Echo Remote:","Request Action Pause",0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"PauseCommand","contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoRemote";
		$this->EchoSetValue($Ident, 3);
	}
	
	public function Next()
	{
		$this->SendDebug("Echo Remote:","Request Action Next",0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"NextCommand","contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoRemote";
		$this->EchoSetValue($Ident, 5);
	}
	
	public function Previous()
	{
		$this->SendDebug("Echo Remote:","Request Action Previous",0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"PreviousCommand","contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoRemote";
		$this->EchoSetValue($Ident, 2);
	}
	
	public function SetVolume(float $volume) // float 0 bis 1 100% = 1
	{
		$volumelevel = $volume*100;
		$this->SendDebug("Echo Remote:","Set Volume to ".$volumelevel,0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"VolumeLevelCommand","volumeLevel":'.$volumelevel.', "contentFocusClientId":null}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoVolume";
		$this->EchoSetValue($Ident, $volume);
	}
		
	public function Rewind30s()
	{
		$this->SendDebug("Echo Remote:","Request Action Rewind 30s",0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"RewindCommand"}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoRemote";
		$this->EchoSetValue($Ident, 1);
	}
	
	public function Forward30s()
	{
		$this->SendDebug("Echo Remote:","Request Action Forward 30s",0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"ForwardCommand"}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoRemote";
		$this->EchoSetValue($Ident, 6);
	}
	
	public function Shuffle(bool $value)
	{
		$this->SendDebug("Echo Remote:","Request Action Shuffle",0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"ShuffleCommand","shuffle":' . ($value ? 'true' : 'false') . '}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoShuffle";
		$this->EchoSetValue($Ident, $value);
	}
	
	public function Repeat(bool $value)
	{
		$this->SendDebug("Echo Remote:","Request Action Repeat",0);
		$urltype = "command";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '{"type":"RepeatCommand","repeat":' . ($value ? 'true' : 'false') . '}';
		$this->SendEcho($postfields, $header, $urltype);
		$Ident = "EchoRepeat";
		$this->EchoSetValue($Ident, $value);
	}
	
	public function TuneIn(string $station)
	{
		$this->SendDebug("Echo Remote:","Set Station to ".$station,0);
		$urltype = "tunein";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "POST";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$postfields = '';
		$this->SendEcho($postfields, $header, $urltype, $station);
		$devicenumber = $this->ReadPropertyString('Devicenumber');
		$Ident = "EchoTuneInRemote_".$devicenumber;
		$stationvalue = $this->GetTuneInStationPreset($station);
		if($stationvalue > 0)
		{
			$this->EchoSetValue($Ident, $stationvalue);
		}
	}
	
	/*
	public function Audible(string $book)
	{
		$this->SendDebug("Echo Remote:","Set Station to ".$station,0);
		$urltype = "tunein";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '';
		$this->SendEcho($postfields, $header, $urltype, $station);
		$devicenumber = $this->ReadPropertyString('Devicenumber');
		$Ident = "EchoTuneInRemote_".$devicenumber;
		$stationvalue = $this->GetTuneInStationPreset($station);
		if($stationvalue > 0)
		{
			$this->EchoSetValue($Ident, $stationvalue);
		}
	}
	
	public function Kindle(string $book)
	{
		$this->SendDebug("Echo Remote:","Set Station to ".$station,0);
		$urltype = "tunein";
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$header = $this->GetHeader($csrf, $cookie);
		$postfields = '';
		$this->SendEcho($postfields, $header, $urltype, $station);
		$devicenumber = $this->ReadPropertyString('Devicenumber');
		$Ident = "EchoTuneInRemote_".$devicenumber;
		$stationvalue = $this->GetTuneInStationPreset($station);
		if($stationvalue > 0)
		{
			$this->EchoSetValue($Ident, $stationvalue);
		}
	}
	*/
	
	public function TuneInPreset(int $preset)
	{
		$station = $this->GetTuneInStationID($preset);
		$this->TuneIn($station);
	}
		
	public function AmazonMusic(string $seedid, string $stationname)
	{
		$urltype = "amazonmusic";
		$amazonmusiccsrf = $this->ReadPropertyString('AmazonCSRF');
		$amazonmusiccookie = $this->ReadPropertyString('AmazonCookie');
		$type = "POST";
		$header = $this->GetHeader($amazonmusiccsrf, $amazonmusiccookie, $type);
		$postfields = '{"seed":"{\"type\":\"KEY\",\"seedId\":\"'.$seedid.'\"}","stationName":"'.$stationname.'","seedType":"KEY"}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	public function SearchMusicTuneIn(string $query)
	{
		$alexacustomerid = $this->ReadPropertyString('CustomerID');
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "GET";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$search_url = 'https://layla.amazon.de/api/tunein/search?query='.$query.'&mediaOwnerCustomerId='.$alexacustomerid;
		$search = $this->SendGETEcho($header, $search_url);
		return $search;
	}
	
	public function GetStateTuneIn()
	{
		$devicetype = $this->ReadPropertyString('Devicetype');
		$devicenumber = $this->ReadPropertyString('Devicenumber');
		$csrf = $this->ReadPropertyString('TuneInCSRF');
		$cookie = $this->ReadPropertyString('TuneInCookie');
		$type = "GET";
		$header = $this->GetHeader($csrf, $cookie, $type);
		$state_url = 'https://layla.amazon.de/api/media/state?deviceSerialNumber='.$devicenumber.'&deviceType='.$devicetype.'&queueId=0e7d86f5-d5a4-4a3a-933e-5910c15d9d4f&shuffling=false&firstIndex=1&lastIndex=1&screenWidth=1920&_=1495289082979';
		$statejson = $this->SendGETEcho($header, $state_url);
		$state = json_decode($statejson);
		return $state;
	}

    public function GetStateOwnMusic()
    {
        $devicetype = $this->ReadPropertyString('Devicetype');
        $devicenumber = $this->ReadPropertyString('Devicenumber');
        $csrf = $this->ReadPropertyString('TuneInCSRF');
        $cookie = $this->ReadPropertyString('TuneInCookie');
        $type = "GET";
        $header = $this->GetHeader($csrf, $cookie, $type);
        $state_url = 'https://layla.amazon.de/api/media/state?deviceSerialNumber='.$devicenumber.'&deviceType='.$devicetype.'&queueId=0e7d86f5-d5a4-4a3a-933e-5910c15d9d4f&shuffling=false&firstIndex=1&lastIndex=1&screenWidth=1920&_=1495289082979';
        $statejson = $this->SendGETEcho($header, $state_url);
        $state = json_decode($statejson);
        return $state;
    }
	
	public function ImportedMusic(string $trackid)
	{
		$urltype = "importedmusic";
		$amazonmusiccsrf = $this->ReadPropertyString('AmazonCSRF');
		$amazonmusiccookie = $this->ReadPropertyString('AmazonCookie');
		$type = "POST";
		$header = $this->GetHeader($amazonmusiccsrf, $amazonmusiccookie, $type);
		$postfields = '{"trackId":"'.$trackid.'", "playQueuePrime":true}';
		$this->SendEcho($postfields, $header, $urltype);
	}
	
	protected function EchoSetValue($Ident, $Value)
	{
		$objid = $this->GetIDForIdent($Ident);
		SetValue($objid, $Value);
		$this->SendDebug("Echo Remote:","Set value of variable with object ID ".$objid." and ident ".$Ident." to ".$Value,0);
	}
	
	private function SetParentIP()
	{
		$change = false;
		//$this->SetStatus(102); //IP Adresse ist g�ltig -> aktiv
				
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
						
			// Keine Verbindung erzwingen wenn IP Harmony Hub leer ist, sonst folgt sp�ter Exception.
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
					// Ping senden statt Socket neu Aufbau, Funktioniert zur Zeit noch nicht zuverl�ssig
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
        $devicenumber = $this->ReadPropertyString('Devicenumber');
		$this->SendDebug("Echo Remote:","Request Action trigger device ".$devicenumber." by Ident ".$Ident,0);
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
		if($Ident == "EchoTuneInRemote_".$devicenumber)
		{
			$stationid = $this->GetTuneInStationID($Value);
			$this->TuneIn($stationid);
		}
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
				{"type": "ValidationTextBox", "name": "TuneInCookie", "caption": "TuneIn Cookie" },
				{ "type": "Label", "label": "__________________________________________________________________________________________________" },
				{ "type": "Label", "label": "TuneIn Sender" },
				{ "type": "Label", "label": "Position 1" },
				{"type": "ValidationTextBox", "name": "TuneIn1", "caption": "TuneIn Sender 1" },
				{"type": "ValidationTextBox", "name": "TuneIn1StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 2" },
				{"type": "ValidationTextBox", "name": "TuneIn2", "caption": "TuneIn Sender 2" },
				{"type": "ValidationTextBox", "name": "TuneIn2StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 3" },
				{"type": "ValidationTextBox", "name": "TuneIn3", "caption": "TuneIn Sender 3" },
				{"type": "ValidationTextBox", "name": "TuneIn3StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 4" },
				{"type": "ValidationTextBox", "name": "TuneIn4", "caption": "TuneIn Sender 4" },
				{"type": "ValidationTextBox", "name": "TuneIn4StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 5" },
				{"type": "ValidationTextBox", "name": "TuneIn5", "caption": "TuneIn Sender 5" },
				{"type": "ValidationTextBox", "name": "TuneIn5StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 6" },
				{"type": "ValidationTextBox", "name": "TuneIn6", "caption": "TuneIn Sender 6" },
				{"type": "ValidationTextBox", "name": "TuneIn6StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 7" },
				{"type": "ValidationTextBox", "name": "TuneIn7", "caption": "TuneIn Sender 7" },
				{"type": "ValidationTextBox", "name": "TuneIn7StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 8" },
				{"type": "ValidationTextBox", "name": "TuneIn8", "caption": "TuneIn Sender 8" },
				{"type": "ValidationTextBox", "name": "TuneIn8StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 9" },
				{"type": "ValidationTextBox", "name": "TuneIn9", "caption": "TuneIn Sender 9" },
				{"type": "ValidationTextBox", "name": "TuneIn9StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 10" },
				{"type": "ValidationTextBox", "name": "TuneIn10", "caption": "TuneIn Sender 10" },
				{"type": "ValidationTextBox", "name": "TuneIn10StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 11" },
				{"type": "ValidationTextBox", "name": "TuneIn11", "caption": "TuneIn Sender 11" },
				{"type": "ValidationTextBox", "name": "TuneIn11StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 12" },
				{"type": "ValidationTextBox", "name": "TuneIn12", "caption": "TuneIn Sender 12" },
				{"type": "ValidationTextBox", "name": "TuneIn12StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 13" },
				{"type": "ValidationTextBox", "name": "TuneIn13", "caption": "TuneIn Sender 13" },
				{"type": "ValidationTextBox", "name": "TuneIn13StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 14" },
				{"type": "ValidationTextBox", "name": "TuneIn14", "caption": "TuneIn Sender 14" },
				{"type": "ValidationTextBox", "name": "TuneIn14StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 15" },
				{"type": "ValidationTextBox", "name": "TuneIn15", "caption": "TuneIn Sender 15" },
				{"type": "ValidationTextBox", "name": "TuneIn15StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 16" },
				{"type": "ValidationTextBox", "name": "TuneIn16", "caption": "TuneIn Sender 16" },
				{"type": "ValidationTextBox", "name": "TuneIn16StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 17" },
				{"type": "ValidationTextBox", "name": "TuneIn17", "caption": "TuneIn Sender 17" },
				{"type": "ValidationTextBox", "name": "TuneIn17StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 18" },
				{"type": "ValidationTextBox", "name": "TuneIn18", "caption": "TuneIn Sender 18" },
				{"type": "ValidationTextBox", "name": "TuneIn18StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 19" },
				{"type": "ValidationTextBox", "name": "TuneIn19", "caption": "TuneIn Sender 19" },
				{"type": "ValidationTextBox", "name": "TuneIn19StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 20" },
				{"type": "ValidationTextBox", "name": "TuneIn20", "caption": "TuneIn Sender 20" },
				{"type": "ValidationTextBox", "name": "TuneIn20StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 21" },
				{"type": "ValidationTextBox", "name": "TuneIn21", "caption": "TuneIn Sender 21" },
				{"type": "ValidationTextBox", "name": "TuneIn21StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 22" },
				{"type": "ValidationTextBox", "name": "TuneIn22", "caption": "TuneIn Sender 22" },
				{"type": "ValidationTextBox", "name": "TuneIn22StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 23" },
				{"type": "ValidationTextBox", "name": "TuneIn23", "caption": "TuneIn Sender 23" },
				{"type": "ValidationTextBox", "name": "TuneIn23StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 24" },
				{"type": "ValidationTextBox", "name": "TuneIn24", "caption": "TuneIn Sender 24" },
				{"type": "ValidationTextBox", "name": "TuneIn24StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 25" },
				{"type": "ValidationTextBox", "name": "TuneIn25", "caption": "TuneIn Sender 25" },
				{"type": "ValidationTextBox", "name": "TuneIn25StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 26" },
				{"type": "ValidationTextBox", "name": "TuneIn26", "caption": "TuneIn Sender 26" },
				{"type": "ValidationTextBox", "name": "TuneIn26StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 27" },
				{"type": "ValidationTextBox", "name": "TuneIn27", "caption": "TuneIn Sender 27" },
				{"type": "ValidationTextBox", "name": "TuneIn27StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 28" },
				{"type": "ValidationTextBox", "name": "TuneIn28", "caption": "TuneIn Sender 28" },
				{"type": "ValidationTextBox", "name": "TuneIn28StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 29" },
				{"type": "ValidationTextBox", "name": "TuneIn29", "caption": "TuneIn Sender 29" },
				{"type": "ValidationTextBox", "name": "TuneIn29StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 30" },
				{"type": "ValidationTextBox", "name": "TuneIn30", "caption": "TuneIn Sender 30" },
				{"type": "ValidationTextBox", "name": "TuneIn30StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 31" },
				{"type": "ValidationTextBox", "name": "TuneIn31", "caption": "TuneIn Sender 31" },
				{"type": "ValidationTextBox", "name": "TuneIn31StationID", "caption": "TuneIn Station ID" },
				{ "type": "Label", "label": "Position 32" },
				{"type": "ValidationTextBox", "name": "TuneIn32", "caption": "TuneIn Sender 32" },
				{"type": "ValidationTextBox", "name": "TuneIn32StationID", "caption": "TuneIn Station ID" },';
			
		return $form;
	}
		
	protected function FormActions()
	{
		$form = '"actions":
			[
				{ "type": "Label", "label": "Play:" },
				{ "type": "Button", "label": "Play", "onClick": "EchoRemote_Play($id);" },
				{ "type": "Label", "label": "Pause:" },
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