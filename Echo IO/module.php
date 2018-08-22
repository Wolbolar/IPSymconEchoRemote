<?
declare(strict_types=1);

// Modul für Amazon Echo Remote

class AmazonEchoIO extends IPSModule
{

    const STATUS_INST_USER_NAME_IS_EMPTY = 210; // user name must not be empty.
    const STATUS_INST_PASSWORD_IS_EMPTY = 211; // password must not be empty.
    const STATUS_INST_NOT_AUTHENTICATED = 212; // authentication must be performed.

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.

        //the following Properties can be set in the configuration form
        $this->RegisterPropertyString("username", "");
        $this->RegisterPropertyString("password", "");
        $this->RegisterPropertyInteger("language", 0);
        $this->RegisterPropertyBoolean("UseCustomCSRFandCookie", false);
        $this->RegisterPropertyString("CSRF", "");
        $this->RegisterPropertyString("alexa_cookie", "");

        //the following Properties are only used internally
        $this->RegisterPropertyString(
            "browser", "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0"
        );
        $this->RegisterPropertyString('CookiesFileName', IPS_GetKernelDir() . 'alexa_cookie.txt');
        $this->RegisterPropertyString('LoginFileName', IPS_GetKernelDir() . 'alexa_login.html');

    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->ValidateConfiguration();

    }

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt
     * wurden. Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung
     * gestellt:
     *
     *
     */

    private function ValidateConfiguration()
    {
        $username = $this->ReadPropertyString('username');
        $password = $this->ReadPropertyString('password');

        if ($username == "") {
            $this->SetStatus(self::STATUS_INST_PASSWORD_IS_EMPTY);
        } elseif ($password == "") {
            $this->SetStatus(self::STATUS_INST_USER_NAME_IS_EMPTY);
        } elseif (!$this->LogIn()) {
            $this->SetStatus(self::STATUS_INST_NOT_AUTHENTICATED);
        } else {
            $this->SetStatus(IS_ACTIVE);
        }

    }

    private function GetAmazonURL()
    {
        $language = $this->ReadPropertyInteger("language");
        switch ($language) {
            case 0: // de
                $amazon_url = "amazon.de";
                break;

            case 1:
                $amazon_url = "amazon.com";
                break;

            default:
                trigger_error('Unexpected language: ' . $language);
                $amazon_url = '';
        }

        return $amazon_url;
    }

    private function GetAlexaURL()
    {
        $language = $this->ReadPropertyInteger("language");
        switch ($language) {
            case 0: // de
                $alexa_url = "alexa.amazon.de";
                break;

            case 1:
                $alexa_url = "pitangui.amazon.com";
                break;

            default:
                trigger_error('Unexpected language: ' . $language);
                $alexa_url = '';
        }

        return $alexa_url;
    }

    private function GetLanguage()
    {
        $language = $this->ReadPropertyInteger("language");
        switch ($language) {
            case 0: // de
                $language_string = "de-DE";
                break;

            case 1:
                $language_string = "en-us";
                break;

            default:
                trigger_error('Unexpected language: ' . $language);
                $language_string = '';
        }

        return $language_string;
    }

    private function deleteFile($FileName): bool
    {

        if (file_exists($FileName)) {
            $Success = unlink($FileName);

            if ($Success) { //the cookie file was deleted successfully
                $this->SendDebug(__FUNCTION__, 'File \'' . $FileName . '\' was deleted', 0);
                return true;
            } else {
                $this->SendDebug(__FUNCTION__, 'File \'' . $FileName . '\' was not deleted', 0);
                return false;
            }
        }

        $this->SendDebug(__FUNCTION__, 'File \'' . $FileName . '\' does not exist', 0);
        return true;

    }

    public function LogOff()
    {
        $this->SendDebug(__FUNCTION__, '== started ==', 0);
        $url = $this->GetAlexaURL() . '/logout';

        $headers = [
            'DNT: 1',
            'Connection: keep-alive',]; //the header must not contain any cookie

        $return = $this->SendEchoData($url, $headers);

        if ($return['http_code'] == 200) { //OK
            $this->SetStatus(self::STATUS_INST_NOT_AUTHENTICATED);
            return $this->deleteFile($this->ReadPropertyString('CookiesFileName'));
        }

        return false;
    }

    #################################################################
    # Amazon Login
    #
    public function LogIn()
    {
        $this->SendDebug(__FUNCTION__, '== started ==', 0);

        if ($this->ReadPropertyBoolean('UseCustomCSRFandCookie')) {
            return $this->CheckLoginStatus();
        }

        // see https://loetzimmer.de/patches/alexa_remote_control_plain.sh
        // Vorgehensweise: https://www.alefo.de/forum/alexa-automatisiert-fernsteuern-739-120#p31690
        // Anmeldung: https://www.alefo.de/forum/alexa-automatisiert-fernsteuern-739-80#p30159

        // get first cookie and write redirection target into referer

        $first_login = $this->GetFirstCookie();
        $referer     = $first_login["referer"];

        // login empty to generate session
        $hiddenfields = $this->StartSession(
            $referer, $first_login['hidden fields']
        );

        // login with filled out form
        // referer now contains session in URL
        $session_data = $this->GetSession($hiddenfields);

        // check whether the login has been successful
        $loginOK = $this->CheckSuccessOfLogin($session_data);

        if (!$loginOK) {
            return false;
        }


        // get CSRF
        $return_data = $this->GetCSRF();

        if ($return_data['http_code'] != 200){
            return false;
        };

        return $this->CheckLoginStatus();
    }

    /**
     * Headers for Login procedure
     *
     * @return array
     */
    private function GetLoginHeader()
    {
        return [
            'Accept-Language: ' . $this->GetLanguage(),
            'DNT: 1',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'];
    }

    /**
     * Step 1: get first cookie and write redirection target into referer
     *
     * @return array
     */
    private function GetFirstCookie()
    {
        ############################################################
        # get first cookie and write redirection target into referer
        #
        # $CURL $OPTS -s -D "${TMP}/.alexa.header" -c ${COOKIE} -b ${COOKIE} -A "${BROWSER}" -H "Accept-Language: ${LANGUAGE}" -H "DNT: 1" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1" -L\
        #  https://alexa.${AMAZON} | grep "hidden" | sed 's/hidden/\n/g' | grep "value=\"" | sed -r 's/^.*name="([^"]+)".*value="([^"]+)".*/\1=\2\&/g' > "${TMP}/.alexa.postdata"

        /*
         Aufruf von alexa.amazon.de (Accept-Language Header erforderlich für die Umleitung auf deutsch/englische Login Seite):
         => erstes Cookie wird geschrieben, Amazon Session hat alexa.amazon.de als Ziel nach erfolgreicher Authentifizierung hinterlegt,
         Weiterleitung auf Amazon Login Seite (das Umleitungsziel ist der Referer der nächsten Anfrage), die Hidden Felder werden
         zum Abschicken der Login-Form im nächsten Schritt benötigt.
        */

        //delete old cookie
        $this->deleteFile($this->ReadPropertyString('CookiesFileName'));

        $url       = 'https://alexa.' . $this->GetAmazonURL();
        $echo_data = $this->SendEchoData($url, $this->GetLoginHeader());
        $header    = $echo_data["header"];
        $body      = $echo_data["body"];

        //get location from header and build referer
        $location = implode(preg_grep('/Location: /', $header));
        $referer  = str_replace('Location: ', 'Referer: ', $location);
        $this->SendDebug(__FUNCTION__, $referer, 0);

        //get hidden fields from body and build post data
        $hiddenFields = $this->GetHiddenFields($body);

        $this->SendDebug(__FUNCTION__, "hidden fields: " . json_encode($hiddenFields), 0);

        return ["referer" => $referer, 'hidden fields' => $hiddenFields];
    }

    /**
     * Step 2: login empty to generate session
     *
     * @param $referer
     * @param $hiddenfields
     *
     * @return array|mixed|null|string|string[]
     */
    private function StartSession($referer, $hiddenfields)
    {
        ##########################################
        # login empty to generate session
        #
        # ${CURL} ${OPTS} -s -c ${COOKIE} -b ${COOKIE} -A "${BROWSER}" -H "Accept-Language: ${LANGUAGE}" -H "DNT: 1" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1" -L\
        # -H "$(grep 'Location: ' ${TMP}/.alexa.header | sed 's/Location: /Referer: /')" -d "@${TMP}/.alexa.postdata" https://www.${AMAZON}/ap/signin | grep "hidden" | sed 's/hidden/\n/g' | grep "value=\"" | sed -r 's/^.*name="([^"]+)".*value="([^"]+)".*/\1=\2\&/g' > "${TMP}/.alexa.postdata2"

        /*
        Aufruf von www.amazon.de/ap/signin (mit Referrer aus dem Location Header der vorigen Anfrage und den Hidden Feldern als POST-Data)
        => Login Vorgang unter /ap/signin schlägt OHNE JAVASCRIPT erwartungsgemäß fehl, Session-Id wird generiert und im Cookie abgelegt,
        die Hidden Felder werden zum Abschicken der Login-Form im nächsten Schritt benötigt.
        */

        //$hiddenfields = $hiddenfields['arr hidden fields'];

        $url     = 'https://www.' . $this->GetAmazonURL() . '/ap/signin';
        $headers = array_merge($this->GetLoginHeader(), [$referer]);
        $this->SendDebug(__FUNCTION__, "Send (Header): " . json_encode($headers), 0);
        $echo_data = $this->SendEchoData($url, $headers, $hiddenfields);
        $body      = $echo_data["body"];


        //get hidden fields from body and build post data
        $hiddenFields = $this->GetHiddenFields($body);
        $this->SendDebug(__FUNCTION__, "hidden fields: " . json_encode($hiddenFields), 0);

        return $hiddenFields;
    }

    /**
     * Step 3: get hidden fields from body and return as array
     *
     * @param $body
     *
     * @return array|mixed|null|string|string[]
     */
    private function GetHiddenFields($body)
    {
        //get hidden fields from body and return as array
        $result = explode("\n", $body);
        //$this->SendDebug(__FUNCTION__, "result: " . json_encode($result), 0);

        $hiddenFields = implode("\n", preg_grep('/type=\"hidden\"/', $result));
        //$this->SendDebug(__FUNCTION__.'temp', "hidden fields: " . json_encode($hiddenFields), 0);

        $hiddenFields = str_replace('hidden', "\n", $hiddenFields);

        $hiddenFields = explode("\n", $hiddenFields);
        $hiddenFields = preg_grep('/value=\"/', $hiddenFields);

        $pattern     = '^.*name="([^"]+)".*value="([^"]+)".*';
        $replacement = '\1=\2';

        $hiddenFields = preg_replace("/" . $pattern . "/", $replacement, $hiddenFields);

        $arrhiddenFields = [];
        foreach ($hiddenFields as $hiddenfield) {
            $field                      = explode('=', $hiddenfield, 2);
            $arrhiddenFields[$field[0]] = $field[1];
        }
        return $arrhiddenFields;
    }

    /**
     * Step 4: login with filled out form
     *
     * @param $str_hiddenFields
     *
     * @return array
     */
    private function GetSession($hiddenFields)
    {
        ############################################
        # login with filled out form
        #  !!! referer now contains session in URL
        #
        #${CURL} ${OPTS} -s -D "${TMP}/.alexa.header2" -c ${COOKIE} -b ${COOKIE} -A "${BROWSER}" -H "Accept-Language: ${LANGUAGE}" -H "DNT: 1" -H "Connection: keep-alive" -H "Upgrade-Insecure-Requests: 1" -L\
        #-H "Referer: https://www.${AMAZON}/ap/signin/$(awk "\$0 ~/.${AMAZON}.*session-id[ \\s\\t]+/ {print \$7}" ${COOKIE})" --data-urlencode "email=${EMAIL}" --data-urlencode "password=${PASSWORD}" -d "@${TMP}/.alexa.postdata2" https://www.${AMAZON}/ap/signin > "${TMP}/.alexa.login"

        /*
         Aufruf von www.amazon.de/ap/signin (Referrer ist jetzt /ap/signin/<session-id>; Post Data enthält jetzt Login Informationen und Hidden Felder der vorigen Anfrage):
         => Cookie wird um gültige Login Session ergänzt und ist jetzt für den Zugriff ohne Anmeldung gültig, Weiterleitung zur ursprünglichen URL in Schritt 1. Damit ist das Cookie auch bei alexa.amazon.de gültig.
         */

        $url = 'https://www.' . $this->GetAmazonURL() . '/ap/signin';

        //get session-id from cookie file
        $session_line = array_values(preg_grep('/\tsession-id\t/', file($this->ReadPropertyString('CookiesFileName'))));
        $session_id   = preg_split('/\s+/', $session_line[0])[6];
        $this->SendDebug(__FUNCTION__, "Session ID: " . $session_id, 0);

        // build referer
        $referer = "Referer: https://www." . $this->GetAmazonURL() . "/ap/signin/" . $session_id;
        $this->SendDebug(__FUNCTION__, "referer: " . $referer, 0);

        $headers   = $this->GetLoginHeader();
        $headers[] = $referer;

        $this->SendDebug(__FUNCTION__, "Send (Header): " . json_encode($headers), 0);

        $postfields = array_merge(
            $hiddenFields, [
                             'email'    => $this->ReadPropertyString("username"),
                             'password' => $this->ReadPropertyString("password")]
        );

        return $this->SendEchoData($url, $headers, $postfields);

    }

    /**
     * Step 5: Check if return of Step 4 is correct
     *
     * @param $session_data
     *
     * @return bool
     */
    private function CheckSuccessOfLogin($session_data)
    {
        /*
         * if [ -z "$(grep 'Location: https://alexa.*html' ${TMP}/.alexa.header2)" ] ; then
        echo "ERROR: Amazon Login was unsuccessful. Possibly you get a captcha login screen."
        echo " Try logging in to https://alexa.${AMAZON} with your browser. In your browser"
        echo " make sure to have all Amazon related cookies deleted and Javascript disabled!"
        echo
        echo " (For more information have a look at ${TMP}/.alexa.login)"

        */

        if (count(preg_grep('/Location: https:\/\/alexa.*html/', $session_data['header'])) == 0) {
            $LoginFile = $this->ReadPropertyString('LoginFileName');
            file_put_contents($LoginFile, $session_data['body']);
            $this->SendDebug(__FUNCTION__, " >> login with filled out form failed. See " . $LoginFile . " <<", 0);
            $check = false;
        } else {
            //delete an existing html error file
            $this->deleteFile($this->ReadPropertyString('LoginFileName'));

            $this->SendDebug(__FUNCTION__, "login with filled out form: OK", 0);
            $check = true;
        }
        return $check;
    }

    #
    # get CSRF
    #
    private function GetCSRF()
    {
        #######################################################
        # get CSRF
        #
        # ${CURL} ${OPTS} -s -c ${COOKIE} -b ${COOKIE} -A "${BROWSER}" -H "DNT: 1" -H "Connection: keep-alive" -L\
        # -H "Referer: https://alexa.${AMAZON}/spa/index.html" -H "Origin: https://alexa.${AMAZON}"\
        # https://${ALEXA}/api/language > /dev/null

        /*
        Damit die XHR-Aufrufe gegen cross-site Attacken gesichert werden, muss für das Cookie noch ein CSRF Token erstellt werden.
        Dies erfolgt beim ersten Aufruf von einer API auf layla.amazon.de. z.B. /api/language unter Angabe des oben gespeicherten Cookies
        => CSRF wird ins Cookie geschrieben
        */

        $url = 'https://' . $this->GetAlexaURL() . '/api/language';

        // build referer
        $referer = "Referer: https://alexa." . $this->GetAmazonURL() . "/spa/index.html";
        $this->SendDebug(__FUNCTION__, "referer: " . $referer, 0);

        //build origin
        $origin = "Origin: https://alexa." . $this->GetAmazonURL();
        $this->SendDebug(__FUNCTION__, "Origin: " . $origin, 0);
        $headers = array_merge($this->GetLoginHeader(), [$referer, $origin]);

        return $this->SendEchoData($url, $headers);
    }

    /**
     * checks if the user is authenticated and saves the custonmerId in a buffer
     *
     * @return bool
     */
    public function CheckLoginStatus()
    {
        $this->SendDebug(__FUNCTION__, '== started ==', 0);
        #######################################################
        #
        # bootstrap with GUI-Version writes GUI version to cookie
        #  returns among other the current authentication state
        #
        # AUTHSTATUS=$(${CURL} ${OPTS} -s -b ${COOKIE} -A "${BROWSER}" -H "DNT: 1" -H "Connection: keep-alive" -L https://${ALEXA}/api/bootstrap?version=${GUIVERSION}
        #   | sed -r 's/^.*"authenticated":([^,]+),.*$/\1/g')

        $guiversion = 0;

        $getfields = ['version' => $guiversion];

        $url         = 'https://' . $this->GetAlexaURL() . '/api/bootstrap?' . http_build_query($getfields);
        $return_data = $this->SendEcho($url, $this->GetHeader());

        if ($return_data['http_code'] != 200) {
            return false;
        }

        $return = json_decode($return_data['body']);

        if (is_null($return)) {
            $this->SendDebug(__FUNCTION__, 'Not authenticated (return is null)! ', 0);

            $authenticated = false;
        } elseif (!property_exists($return, 'authentication')) {
            $this->SendDebug(
                __FUNCTION__, 'Not authenticated (property authentication not found)! ' . $return_data['body'], 0
            );

            $authenticated = false;
        } elseif ($return->authentication->authenticated == false) {
            $this->SendDebug(
                __FUNCTION__, 'Not authenticated (property authenticated is false)! ' . $return_data['body'], 0
            );

            $authenticated = false;
        } else {
            $this->SetBuffer('customerID',  $return->authentication->customerId);
            $this->SendDebug(__FUNCTION__, 'CustomerID: ' . $return->authentication->customerId, 0);
            $authenticated = true;
        }

        if (!$authenticated) {
            $this->SetBuffer('customerID',  '');
            $this->SetStatus(self::STATUS_INST_NOT_AUTHENTICATED);
        }

        return $authenticated;

    }

    private function getReturnValues(array $info, string $result)
    {
        $HeaderSize = $info['header_size'];

        $http_code = $info['http_code'];
        $this->SendDebug(__FUNCTION__, "Response (http_code): " . $http_code, 0);

        $header = explode("\n", substr($result, 0, $HeaderSize));
        $this->SendDebug(__FUNCTION__, "Response (header): " . json_encode($header), 0);

        $body = substr($result, $HeaderSize);
        $this->SendDebug(__FUNCTION__, "Response (body): " . $body, 0);


        return ['http_code' => $http_code, 'header' => $header, 'body' => $body];
    }

    private function NpCommand(array $getfields, array $postfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/np/command?' . http_build_query($getfields);

        $header = $this->GetHeader();

        $postfields = json_encode($postfields);

        return $this->SendEcho($url, $header, $postfields);
    }

    private function NpPlayer(array $getfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/np/player?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, null);
    }

    private function BehaviorsPreview(array $postfields)
    {

        $url = 'https://alexa.' . $this->GetAmazonURL() . '/api/behaviors/preview';

        $operationPayload = [
            'deviceType'         => $postfields['deviceType'],
            'deviceSerialNumber' => $postfields['deviceSerialNumber'],
            'locale'             => $this->GetLanguage(),
            'customerId'         => $postfields['customerId']];

        if (isset($postfields['textToSpeak'])) {
            $operationPayload['textToSpeak'] = $postfields['textToSpeak'];
        }

        $startNode = [
            '@type'            => 'com.amazon.alexa.behaviors.model.OpaquePayloadOperationNode',
            'type'             => $postfields['type'],
            'operationPayload' => $operationPayload];

        $sequence = [
            '@type'     => 'com.amazon.alexa.behaviors.model.Sequence',
            'startNode' => $startNode,];


        $postfields = [
            'behaviorId'   => 'PREVIEW',
            'sequenceJson' => json_encode($sequence),
            'status'       => 'ENABLED'];

        $header = $this->GetHeader();

        $postfields = json_encode($postfields);

        return $this->SendEcho($url, $header, $postfields);
    }

    private function BehaviorsPreviewAutomation(array $deviceinfos, array $automation)
    {

        $url = 'https://alexa.' . $this->GetAmazonURL() . '/api/behaviors/preview';

        $header = $this->GetHeader();

        $postfields = json_encode(
            [
                'behaviorId'   => $automation['automationId'],
                'sequenceJson' => json_encode($automation['sequence']),
                'status'       => 'ENABLED']
        );

        $postfields = str_replace('ALEXA_CURRENT_DEVICE_TYPE', $deviceinfos['deviceType'], $postfields);
        $postfields = str_replace('ALEXA_CURRENT_DSN', $deviceinfos['deviceSerialNumber'], $postfields);

        return $this->SendEcho($url, $header, $postfields);
    }

    private function TuneinQueueandplay(array $getfields, array $postfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/tunein/queue-and-play?' . http_build_query($getfields);

        $header = $this->GetHeader();

        $postfields = json_encode($postfields);

        return $this->SendEcho($url, $header, $postfields);
    }

    private function CloudplayerQueueandplay(array $getfields, array $postfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/cloudplayer/queue-and-play?' . http_build_query($getfields);

        $header = $this->GetHeader();

        $postfields = json_encode($postfields);

        return $this->SendEcho($url, $header, $postfields);
    }

    private function MediaState($getfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/media/state?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, null);
    }

    private function Activities($getfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/activities?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, null); //it seems as if postfields are not supported within this command
    }

    private function BluetoothDisconnect($getfields)
    {
        $url =
            'https://' . $this->GetAlexaURL() . '/api/bluetooth/disconnect-sink/' . $getfields['deviceType'] . '/' . $getfields['deviceSerialNumber'];

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, null, true);
    }

    private function BluetoothConnect($getfields, $postfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/bluetooth/pair-sink/' . $getfields['deviceType'] . '/' . $getfields['deviceSerialNumber'];

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, json_encode($postfields));
    }

    private function GetBluetoothDevices()
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/bluetooth?cached=false';

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header);
    }

    private function CustomCommand(string $url, array $postfields = null, bool $optpost = null)
    {
        $url = str_replace('{AlexaURL}', $this->GetAlexaURL(), $url);
        $url = str_replace('{AmazonURL}', $this->GetAmazonURL(), $url);

        $header = $this->GetHeader();

        if (is_null($postfields)) {
            return $this->SendEcho($url, $header, null, $optpost);
        } else {
            return $this->SendEcho($url, $header, json_encode($postfields), $optpost);
        }
    }

    /** get JSON device list
     *
     * @return mixed
     */
    private function GetDevices(string $deviceType = null, string $serialNumber = null, bool $cached = false)
    {
        $header = $this->GetHeader();

        $getfields = [
            'cached' => $cached ? 'true' : 'false'];

        $url = 'https://' . $this->GetAlexaURL() . '/api/devices-v2/device?' . http_build_query($getfields);

        $result = $this->SendEcho($url, $header, null);

        if ($result['http_code'] != 200) {
            return $result;
        }
        //print_r($result);
        //if the info is needed for a single device
        if (!is_null($deviceType) && !is_null($serialNumber)) {
            $devices_arr = json_decode($result['body'], true);
            $myDevice    = null;
            foreach ($devices_arr['devices'] as $key => $device) {
                if (($device['deviceType'] == $deviceType) && ($device['serialNumber'] == $serialNumber)) {
                    $myDevice = $device;
                    //                    print_r($myDevice);

                    break;
                }
            }
            $devices_arr['devices'] = [$myDevice];
            $result['body']         = json_encode($devices_arr);
        }

        return $result;
    }

    private function BehaviorsAutomations()
    {
        $header = $this->GetHeader();

        $url = 'https://' . $this->GetAlexaURL() . '/api/behaviors/automations';

        return $this->SendEcho($url, $header);

    }

    private function SendEchoData(string $url, array $header, array $postfields = null)
    {
        $this->SendDebug(__FUNCTION__, 'url: ' . $url, 0);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->ReadPropertyString('CookiesFileName')); //this file is read
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->ReadPropertyString('CookiesFileName'));  //this file is written
        curl_setopt($ch, CURLOPT_USERAGENT, $this->ReadPropertyString('browser'));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (!is_null($postfields)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            trigger_error('Error:' . curl_error($ch));
        }
        $info = curl_getinfo($ch);
        curl_close($ch);

        return $this->getReturnValues($info, $result);
    }

    private function GetHeader()
    {

        $csrf = '';

        if ($this->ReadPropertyBoolean('UseCustomCSRFandCookie')) {
            $csrf = $this->ReadPropertyString("CSRF");
        } else {
            $CookiesFileName = $this->ReadPropertyString('CookiesFileName');

            if (file_exists($CookiesFileName)) {
                //get CSRF from cookie file
                $cookie_line = array_values(preg_grep('/\tcsrf\t/', file($CookiesFileName)));
                if (isset($cookie_line[0])) {
                    $csrf = preg_split('/\s+/', $cookie_line[0])[6];
                }
            }
        }

        $header = [
            'User-Agent: ' . $this->ReadPropertyString('browser'),
            'Accept-Encoding: gzip, deflate, br',
            'Accept-Language:  de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7',
            'Accept: application/json, text/javascript, */*; q=0.01',
            'Referer: http://alexa.' . $this->GetAmazonURL() . '/spa/index.html',
            'Connection: keep-alive',
            'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'];

        if ($csrf) {
            $header[] = 'csrf: ' . $csrf;
        }

        return $header;
    }


    /**  Send to Echo API
     *
     * @param string $url
     *
     * @param array  $header
     * @param string $postfields as json string
     *
     * @return mixed
     */
    private function SendEcho(string $url, array $header, string $postfields = null, bool $optpost = null)
    {
        $this->SendDebug(__FUNCTION__, "Header: " . json_encode($header), 0);

        $ch = curl_init();

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_TIMEOUT        => 20, //timeout after 20 seconds
            CURLOPT_HEADER         => true,
            CURLINFO_HEADER_OUT    => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1];

        if ($this->ReadPropertyBoolean('UseCustomCSRFandCookie')) {
            $options[CURLOPT_COOKIE] = $this->ReadPropertyString("alexa_cookie"); //this content is read
        } else {
            $options [CURLOPT_COOKIEFILE] = $this->ReadPropertyString("CookiesFileName"); //this file is read
        }

        if (isset($postfields)) {
            $this->SendDebug(__FUNCTION__, "Postfields: " . $postfields, 0);
            $options [CURLOPT_POSTFIELDS] = $postfields;
        }

        if (isset($optpost)) {
            $options[CURLOPT_POST] = $optpost;
        }

        $this->SendDebug(__FUNCTION__, "Options: " . json_encode($options), 0);
        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->LogMessage('Error: (' . curl_errno($ch) . ') ' . curl_error($ch), KL_ERROR);
            return false;
        }

        $info      = curl_getinfo($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->SendDebug(__FUNCTION__, "Send to URL: " . print_r($url, true), 0);
        $this->SendDebug(__FUNCTION__, "Curl Info: " . $http_code . ' ' . print_r($info, true), 0);
        curl_close($ch);
        //eine Fehlerbehandlung macht hier leider keinen Sinn, da 400 auch kommt, wenn z.b. der Bildschirm (Show) ausgeschaltet ist
        if (in_array(
            $http_code, [// 400  //bad request
                         //, 500 //internal server error
                      ]
        )) {
            trigger_error('Unexpected HTTP Code: ' . $http_code);
        }

        return $this->getReturnValues($info, $result);
    }

    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, 'Incoming: ' . $JSONString, 0);
        // Empfangene Daten von der Device Instanz
        $data = json_decode($JSONString)->Buffer;

        if (!property_exists($data, 'method')) {
            trigger_error('Property \'method\' is missing');
            return false;
        }

        $this->SendDebug(__FUNCTION__, '== started == (Method \'' . $data->method . '\')', 0);
        //$this->SendDebug(__FUNCTION__, 'Method: ' . $data->method, 0);

        $buffer = json_decode($JSONString, true)['Buffer'];

        switch ($data->method) {
            case 'NpCommand':
                $getfields  = $buffer['getfields'];
                $postfields = $buffer['postfields'];

                $result = $this->NpCommand($getfields, $postfields);
                break;

            case 'NpPlayer':
                $getfields = $buffer['getfields'];

                $result = $this->NpPlayer($getfields);
                break;

            case 'BehaviorsPreview':
                $postfields = $buffer['postfields'];

                $result = $this->BehaviorsPreview($postfields);
                break;

            case 'BehaviorsAutomations':

                $result = $this->BehaviorsAutomations();
                break;

            case 'BehaviorsPreviewAutomation':
                $deviceinfos = $buffer['postfields']; //the postfields contain the device infos
                $automation  = $buffer['automation'];
                $result      = $this->BehaviorsPreviewAutomation($deviceinfos, $automation);
                break;

            case 'CloudplayerQueueandplay':
                $getfields  = $buffer['getfields'];
                $postfields = $buffer['postfields'];

                $result = $this->CloudplayerQueueandplay($getfields, $postfields);
                break;

            case 'TuneinQueueandplay':
                $getfields  = $buffer['getfields'];
                $postfields = $buffer['postfields'];

                $result = $this->TuneinQueueandplay($getfields, $postfields);
                break;

            case 'MediaState':
                $getfields = $buffer['getfields'];

                $result = $this->MediaState($getfields);
                break;

            case 'Activities':
                $getfields = $buffer['getfields'];

                $result = $this->Activities($getfields);
                break;

            case 'BluetoothDisconnectSink':
                $getfields = $buffer['getfields'];

                $result = $this->BluetoothDisconnect($getfields);
                break;

            case 'BluetoothPairSink':
                $getfields  = $buffer['getfields'];
                $postfields = $buffer['postfields'];

                $result = $this->BluetoothConnect($getfields, $postfields);
                break;

            case 'Bluetooth':

                $result = $this->GetBluetoothDevices();
                break;

            case 'CustomCommand':
                if (isset($buffer['postfields'])) {
                    $postfields = $buffer['postfields'];
                } else {
                    $postfields = null;
                }
                if (isset($buffer['optpost'])) {
                    $optpost = $buffer['optpost'];
                } else {
                    $optpost = null;
                }
                $url = $buffer['url'];

                $result = $this->CustomCommand($url, $postfields, $optpost);
                break;

            case 'GetDevices':
                $result = $this->GetDevices();
                break;

            case 'GetCustomerID':
                $result = ['http_code' => 200, 'header' => '', 'body' => $this->GetBuffer('customerID')];
                $this->SendDebug(__FUNCTION__, 'Return: '. $this->GetBuffer('customerID'), 0);

                break;

            default:
                trigger_error('Method \'' . $data->method . '\' not yet supported');
                return false;
        }

        return json_encode($result);

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
    private function FormHead()
    {
        $form = [
            [
                'name'    => 'username',
                'type'    => 'ValidationTextBox',
                'caption' => 'Amazon User Name'],
            [
                'name'    => 'password',
                'type'    => 'PasswordTextBox',
                'caption' => 'Amazon Password'],
            [
                'name'    => 'language',
                'type'    => 'Select',
                'caption' => 'Echo language',
                'options' => $this->GetEchoLanguageList()],
            [
                'name'    => 'UseCustomCSRFandCookie',
                'type'    => 'CheckBox',
                'caption' => 'Use Custom CSRF and Cookie'],
            [
                'name'    => 'CSRF',
                'type'    => 'ValidationTextBox',
                'caption' => 'CSRF'],
            [
                'name'    => 'alexa_cookie',
                'type'    => 'ValidationTextBox',
                'caption' => 'Cookie']

        ];
        return $form;
    }

    /**
     * return form actions by token
     *
     * @return array
     */
    private function FormActions()
    {
        $form = [
            [
                'type'  => 'Label',
                'caption' => 'Test the Registration:'],
            [
                'type'    => 'Button',
                'caption'   => 'login',
                'onClick' => "if (EchoIO_LogIn(\$id)){echo 'Die Anmeldung war erfolgreich.';} else {echo 'Bei der Anmeldung ist ein Fehler aufgetreten.';}"],
            [
                'type'    => 'Button',
                'caption'   => 'logoff',
                'onClick' => "if (EchoIO_LogOff(\$id)){echo 'Die Abmeldung war erfolgreich.';} else {echo 'Bei der Abmeldung ist ein Fehler aufgetreten.';}"],
            [
                'type'    => 'Button',
                'caption'   => 'Login Status',
                'onClick' => "if (EchoIO_CheckLoginStatus(\$id)){echo 'Sie sind angemeldet.';} else {echo 'Sie sind nicht angemeldet.';}"],];


        return $form;
    }


    /**
     * return from status
     *
     * @return array
     */
    private function FormStatus()
    {
        $form = [
            [
                'code'    => 210,
                'icon'    => 'error',
                'caption' => 'user name must not be empty.'],
            [
                'code'    => 211,
                'icon'    => 'error',
                'caption' => 'password must not be empty.'],
            [
                'code'    => 212,
                'icon'    => 'error',
                'caption' => 'not authenticated.']];

        return $form;
    }

    private function GetEchoLanguageList()
    {
        $options = [
            [
                'caption' => 'Please choose',
                'value' => -1],
            [
                'caption' => 'german',
                'value' => 0],
            [
                'caption' => 'english',
                'value' => 1]];
        return $options;
    }

}