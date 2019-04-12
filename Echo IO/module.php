<?php
declare(strict_types=1);

// Modul für Amazon Echo Remote

class AmazonEchoIO extends IPSModule
{

    private const STATUS_INST_USER_NAME_IS_EMPTY = 210; // user name must not be empty.
    private const STATUS_INST_PASSWORD_IS_EMPTY = 211; // password must not be empty.
    private const STATUS_INST_COOKIE_IS_EMPTY = 212; // cookie must not be empty.
    private const STATUS_INST_COOKIE_WITHOUT_CSRF = 213; // cookie must include csrf.
    private const STATUS_INST_NOT_AUTHENTICATED = 214; // authentication must be performed.

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.

        //the following Properties can be set in the configuration form
        $this->RegisterPropertyBoolean('active', false);
        $this->RegisterPropertyString('username', '');
        $this->RegisterPropertyString('password', '');
        $this->RegisterPropertyString('amazon2fa', '');
        $this->RegisterPropertyInteger('language', 0);
        $this->RegisterPropertyBoolean('UseCustomCSRFandCookie', false);
        $this->RegisterPropertyString('alexa_cookie', '');

        //the following Properties are only used internally
        $this->RegisterPropertyString(
            'browser', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0'
        );
        $this->RegisterPropertyString('CookiesFileName', IPS_GetKernelDir() . 'alexa_cookie.txt');
        $this->RegisterPropertyString('LoginFileName', IPS_GetKernelDir() . 'alexa_login.html');

    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $username  = $this->ReadPropertyString('username');
        $password  = $this->ReadPropertyString('password');
        $cookie    = $this->ReadPropertyString('alexa_cookie');
        $useCookie = $this->ReadPropertyBoolean('UseCustomCSRFandCookie');
        $active    = $this->ReadPropertyBoolean('active');

        $currentProperties = json_encode(
            [
                'username'     => $username,
                'password'     => $password,
                'alexa_cookie' => $cookie,
                'active'       => $active]
        );

        $bufferedProperties = $this->GetBuffer($this->InstanceID . '-SavedProperties');
        if ($bufferedProperties === '' || $bufferedProperties !== $currentProperties) {
            $this->SetBuffer($this->InstanceID . '-SavedProperties', $currentProperties);
            $this->SetBuffer($this->InstanceID . '-failedLogins', json_encode(0));
        }

        if (json_decode($this->GetBuffer($this->InstanceID . '-failedLogins'), false) >= 3) {
            $this->SendDebug(__FUNCTION__, 'count of failed logins exceeded', 0);
            $this->SetStatus(self::STATUS_INST_NOT_AUTHENTICATED);

            return;
        }


        if ($active) {
            $this->ValidateConfiguration($username, $password, $cookie, $useCookie);
        } else {
            $this->SetStatus(IS_INACTIVE);
        }
    }

    /**
     * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt
     * wurden. Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung
     * gestellt:
     *
     * @param $username
     * @param $password
     * @param $cookie
     * @param $useCookie
     */

    private function ValidateConfiguration($username, $password, $cookie, $useCookie): void
    {


        if ($useCookie) {
            if ($cookie === '') {
                $this->SetStatus(self::STATUS_INST_COOKIE_IS_EMPTY);
            } elseif ($this->getCsrfFromCookie() === false) {
                $this->SetStatus(self::STATUS_INST_COOKIE_WITHOUT_CSRF);
            } /** @noinspection NotOptimalIfConditionsInspection */ elseif ($this->LogIn()) {
                $this->SetStatus(IS_ACTIVE);
            } else {
                $this->SetStatus(self::STATUS_INST_NOT_AUTHENTICATED);
            }
        } elseif ($username === '') {
            $this->SetStatus(self::STATUS_INST_USER_NAME_IS_EMPTY);
        } elseif ($password === '') {
            $this->SetStatus(self::STATUS_INST_PASSWORD_IS_EMPTY);
        } elseif ($this->LogIn()) {
            $this->SetStatus(IS_ACTIVE);
            $this->SetBuffer($this->InstanceID . '-failedLogins', json_encode(0));
        } else {
            $this->SetStatus(self::STATUS_INST_NOT_AUTHENTICATED);
            $failedLogins = json_decode($this->GetBuffer($this->InstanceID . '-failedLogins'), false) + 1;
            $this->SetBuffer($this->InstanceID . '-failedLogins', json_encode($failedLogins));

            $errorTxt = sprintf('Number of failed LogIns: %d', json_decode($this->GetBuffer($this->InstanceID . '-failedLogins'), false));
            $this->SendDebug(__FUNCTION__, $errorTxt, 0);
            IPS_LogMessage(__CLASS__, $errorTxt);
        }
    }

    private function getCsrfFromCookie()
    {
        $cookie     = $this->ReadPropertyString('alexa_cookie');
        $cookie_arr = explode('; ', $cookie);
        if (count($cookie_arr) === 0) {
            return false;
        }
        foreach ($cookie_arr as $item) {
            if (strpos($item, 'csrf=') === 0) {
                $csrf_arr = explode('=', $item);
                if (count($csrf_arr) === 2) {
                    return $csrf_arr[1];
                }
            }
        }

        return false;
    }

    private function GetAmazonURL(): string
    {
        $language = $this->ReadPropertyInteger('language');
        switch ($language) {
            case 0: // de
                $amazon_url = 'amazon.de';
                break;

            case 1:
                $amazon_url = 'amazon.com';
                break;

            default:
                trigger_error('Unexpected language: ' . $language);
                $amazon_url = '';
        }

        return $amazon_url;
    }

    private function GetAlexaURL(): string
    {
        $language = $this->ReadPropertyInteger('language');
        switch ($language) {
            case 0: // de
                $alexa_url = 'alexa.amazon.de';
                break;

            case 1:
                $alexa_url = 'pitangui.amazon.com';
                break;

            default:
                trigger_error('Unexpected language: ' . $language);
                $alexa_url = '';
        }

        return $alexa_url;
    }

    private function GetLanguage(): string
    {
        $language = $this->ReadPropertyInteger('language');
        switch ($language) {
            case 0: // de
                $language_string = 'de-DE';
                break;

            case 1:
                $language_string = 'en-us';
                break;

            default:
                trigger_error('Unexpected language: ' . $language);
                $language_string = '';
        }

        return $language_string;
    }

    private function deleteFile(string $FileName): bool
    {

        if (file_exists($FileName)) {
            $Success = unlink($FileName);

            if ($Success) { //the cookie file was deleted successfully
                $this->SendDebug(__FUNCTION__, 'File \'' . $FileName . '\' was deleted', 0);
                return true;
            }
            $this->SendDebug(__FUNCTION__, 'File \'' . $FileName . '\' was not deleted', 0);
            return false;
        }

        $this->SendDebug(__FUNCTION__, 'File \'' . $FileName . '\' does not exist', 0);
        return true;

    }

    public function LogOff(): bool
    {
        $this->SendDebug(__FUNCTION__, '== started ==', 0);
        $url = $this->GetAlexaURL() . '/logout';

        $headers = [
            'DNT: 1',
            'Connection: keep-alive']; //the header must not contain any cookie

        $return = $this->SendEchoData($url, $headers);

        if ($return['http_code'] === 200) { //OK
            $this->SetStatus(self::STATUS_INST_NOT_AUTHENTICATED);
            return $this->deleteFile($this->ReadPropertyString('CookiesFileName'));
        }

        return false;
    }

    #################################################################
    # Amazon Login
    #
    public function LogIn(): bool
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
        if (count($first_login['hidden fields']) === 0) {
            $this->SendDebug(__FUNCTION__, 'no hidden fields found!', 0);
            $failedLogins = json_decode($this->GetBuffer($this->InstanceID . '-failedLogins'), false);
            $this->SetBuffer($this->InstanceID . '-failedLogins', json_encode($failedLogins + 1));
            return false;
        }
        $referer = $first_login['referer'];

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

        if ($return_data['http_code'] !== 200) {
            return false;
        }

        return $this->CheckLoginStatus();
    }

    public function GetOTP()
    {
        // returns Amzon 2FA OTP Code if Seed is set, otherwise an empty String
        $Seed = str_replace(' ', '', $this->ReadPropertyString('amazon2fa'));
        if( $Seed !== '' )
        {
            $res = $this->GetAmazon2FACode($Seed, 6, 30);
            if($res['TTL'] < 3 )
            {
                sleep(4); // Wait till a fresh code is generated
                $res = $this->GetAmazon2FACode($Seed, 6, 30);
            }
            return $res['OTP'];
        }
        return '';
    }

    private function GetAmazon2FACode($Seed, $OtpLength, $OtpKeyRegen)
    {
        /**
         * Based on the 2FA Example found on: https://www.idontplaydarts.com/2011/07/google-totp-two-factor-authentication-for-php/
         **/
        // Current Timestamp
        $timestamp = floor(microtime(true)/$OtpKeyRegen);
        // Lookuptable for Base32
        $lut = array(
            'A' =>0, 'B' =>1, 'C' =>2, 'D' =>3, 'E' =>4, 'F' =>5, 'G' =>6, 'H' =>7,
            'I' =>8, 'J' =>9, 'K' =>10, 'L' =>11, 'M' =>12, 'N' =>13, 'O' =>14, 'P' =>15,
            'Q' =>16, 'R' =>17, 'S' =>18, 'T' =>19, 'U' =>20, 'V' =>21, 'W' =>22, 'X' =>23,
            'Y' =>24, 'Z' =>25, '2' =>26, '3' =>27, '4' =>28, '5' =>29, '6' =>30, '7' =>31);
        // Decode Base32 Seed
        $b32 = strtoupper($Seed);
        $n = $j = 0;
        $key = '';
        if (!preg_match('/^[ABCDEFGHIJKLMNOPQRSTUVWXYZ234567]+$/', $b32, $match)) {
            trigger_error('Invalid characters in the base32 string.');
            return null;
        }
        for ($i = 0, $iMax = strlen($b32); $i < $iMax; $i++)
        {
            $n <<= 5; 			  // Move buffer left by 5 to make room
            $n += $lut[$b32[$i]]; // Add value into buffer
            $j += 5;			  // Keep track of number of bits in buffer
            if ($j >= 8) { $j -= 8; $key .= chr(($n & (0xFF << $j)) >> $j); }
        }
        // Check Binary Key
        if (strlen($key) < 8) {
            trigger_error('Secret key is too short. Must be at least 16 base 32 characters');
            return null;
        }
        // Generate OTA Code based on Seed and Current Timestamp
        $h = hash_hmac ('sha1', pack('N*', 0) . pack('N*', $timestamp), $key, true);  // NOTE: Counter must be 64-bit int
        $o = ord($h[19]) & 0xf;
        $ota_code = ( ((ord($h[$o+0])&0x7f)<<24) | ((ord($h[$o+1])&0xff)<<16) | ((ord($h[$o+2])&0xff)<<8) | (ord($h[$o+3])&0xff) ) % (10 ** $OtpLength);
        // Output Debug Info
        //echo("Code Valid for: " . ($OtpKeyRegen - round(microtime(true) - ($timestamp*$OtpKeyRegen), 0)) );

        // Return OTP Code
        return ['OTP' => str_pad((string) $ota_code, $OtpLength, '0', STR_PAD_LEFT), 'TTL' => $OtpKeyRegen - round(microtime(true) - ($timestamp * $OtpKeyRegen))];
    }

    /**
     * Headers for Login procedure
     *
     * @return array
     */
    private function GetLoginHeader(): array
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
    private function GetFirstCookie(): array
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

        $echo_data = $this->SendEchoData('https://alexa.' . $this->GetAmazonURL(), $this->GetLoginHeader());

        //get location from header and build referer
        $location = implode(preg_grep('/Location: /', $echo_data['header']));
        $referer  = str_replace('Location: ', 'Referer: ', $location);
        $this->SendDebug(__FUNCTION__, $referer, 0);

        //get hidden fields from body and build post data
        $hiddenFields = $this->GetHiddenFields($echo_data['body']);

        $this->SendDebug(__FUNCTION__, 'hidden fields: ' . json_encode($hiddenFields), 0);

        return ['referer' => $referer, 'hidden fields' => $hiddenFields];
    }

    /**
     * Step 2: login empty to generate session
     *
     * @param $referer
     * @param $hiddenfields
     *
     * @return array|mixed|null|string|string[]
     */
    private function StartSession(string $referer, array $hiddenfields): array
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

        $headers = array_merge($this->GetLoginHeader(), [$referer]);
        $this->SendDebug(__FUNCTION__, 'Send (Header): ' . json_encode($headers), 0);
        $echo_data = $this->SendEchoData('https://www.' . $this->GetAmazonURL() . '/ap/signin', $headers, $hiddenfields);

        //get hidden fields from body and build post data
        $hiddenFields = $this->GetHiddenFields($echo_data['body']);
        $this->SendDebug(__FUNCTION__, 'hidden fields: ' . json_encode($hiddenFields), 0);

        return $hiddenFields;
    }

    /**
     * Step 3: get hidden fields from body and return as array
     *
     * @param $body
     *
     * @return array|mixed|null|string|string[]
     */
    private function GetHiddenFields(string $body): array
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

        $hiddenFields = preg_replace('/' . $pattern . '/', $replacement, $hiddenFields);

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
     * @param $hiddenFields
     *
     * @return array
     */
    private function GetSession(array $hiddenFields): array
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

        //get session-id from cookie file
        $session_line = array_values(preg_grep('/\tsession-id\t/', file($this->ReadPropertyString('CookiesFileName'))));
        $session_id   = preg_split('/\s+/', $session_line[0])[6];
        $this->SendDebug(__FUNCTION__, 'Session ID: ' . $session_id, 0);

        // build referer
        $referer = 'Referer: https://www.' . $this->GetAmazonURL() . '/ap/signin/' . $session_id;
        $this->SendDebug(__FUNCTION__, 'referer: ' . $referer, 0);

        $headers   = $this->GetLoginHeader();
        $headers[] = $referer;

        $this->SendDebug(__FUNCTION__, 'Send (Header): ' . json_encode($headers), 0);

        $postfields = array_merge(
            $hiddenFields, [
                             'email'    => $this->ReadPropertyString('username'),
                             'password' => $this->ReadPropertyString('password') . $this->GetOTP()]
        );

        return $this->SendEchoData('https://www.' . $this->GetAmazonURL() . '/ap/signin', $headers, $postfields);

    }

    /**
     * Step 5: Check if return of Step 4 is correct
     *
     * @param $session_data
     *
     * @return bool
     */
    private function CheckSuccessOfLogin(array $session_data): bool
    {
        /*
         * if [ -z "$(grep 'Location: https://alexa.*html' ${TMP}/.alexa.header2)" ] ; then
        echo "ERROR: Amazon Login was unsuccessful. Possibly you get a captcha login screen."
        echo " Try logging in to https://alexa.${AMAZON} with your browser. In your browser"
        echo " make sure to have all Amazon related cookies deleted and Javascript disabled!"
        echo
        echo " (For more information have a look at ${TMP}/.alexa.login)"

        */

        $LoginFile = $this->ReadPropertyString('LoginFileName');

        if (count(preg_grep('/Location: https:\/\/alexa.*html/', $session_data['header'])) === 0) {
            file_put_contents($LoginFile, $session_data['body']);
            $this->SendDebug(__FUNCTION__, ' >> login with filled out form failed. See ' . $LoginFile . ' <<', 0);
            $check = false;
        } else {
            //delete an existing html error file
            $this->deleteFile($LoginFile);

            $this->SendDebug(__FUNCTION__, 'login with filled out form: OK', 0);
            $check = true;
        }
        return $check;
    }

    #
    # get CSRF
    #
    private function GetCSRF(): array
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
        $referer = 'Referer: https://alexa.' . $this->GetAmazonURL() . '/spa/index.html';
        $this->SendDebug(__FUNCTION__, 'referer: ' . $referer, 0);

        //build origin
        $origin = 'Origin: https://alexa.' . $this->GetAmazonURL();
        $this->SendDebug(__FUNCTION__, 'Origin: ' . $origin, 0);
        $headers = array_merge($this->GetLoginHeader(), [$referer, $origin]);

        return $this->SendEchoData($url, $headers);
    }

    /**
     * checks if the user is authenticated and saves the custonmerId in a buffer
     *
     * @return bool
     */
    public function CheckLoginStatus(): bool
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

        if ($return_data['body'] === null) {
            $return = null;
        } else {
            $return = json_decode($return_data['body'], false);
        }

        if ($return === null) {
            $this->SendDebug(__FUNCTION__, 'Not authenticated (return is null)! ', 0);

            $authenticated = false;
        } elseif (!property_exists($return, 'authentication')) {
            $this->SendDebug(
                __FUNCTION__, 'Not authenticated (property authentication not found)! ' . $return_data['body'], 0
            );

            $authenticated = false;
        } elseif ($return->authentication->authenticated) {
            $this->SetBuffer('customerID', $return->authentication->customerId);
            $this->SendDebug(__FUNCTION__, 'CustomerID: ' . $return->authentication->customerId, 0);
            $authenticated = true;
        } else {
            $this->SendDebug(
                __FUNCTION__, 'Not authenticated (property authenticated is false)! ' . $return_data['body'], 0
            );

            $authenticated = false;
        }

        if (!$authenticated) {
            $this->SetBuffer('customerID', '');
            $this->SetStatus(self::STATUS_INST_NOT_AUTHENTICATED);
        }

        return $authenticated;

    }

    private function getReturnValues(array $info, string $result): array
    {
        $HeaderSize = $info['header_size'];

        $http_code = $info['http_code'];
        $this->SendDebug(__FUNCTION__, 'Response (http_code): ' . $http_code, 0);

        $header = explode("\n", substr($result, 0, $HeaderSize));
        $this->SendDebug(__FUNCTION__, 'Response (header): ' . json_encode($header), 0);

        $body = substr($result, $HeaderSize);
        $this->SendDebug(__FUNCTION__, 'Response (body): ' . $body, 0);


        return ['http_code' => $http_code, 'header' => $header, 'body' => $body];
    }

    private function NpCommand(array $getfields, array $postfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/np/command?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, json_encode($postfields));
    }

    private function NpPlayer(array $getfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/np/player?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header);
    }

    private function NpQueue(array $getfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/np/queue?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header);
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
            'startNode' => $startNode];


        $postfields = [
            'behaviorId'   => 'PREVIEW',
            'sequenceJson' => json_encode($sequence),
            'status'       => 'ENABLED'];

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, json_encode($postfields));
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

        $postfields = str_replace(
            ['ALEXA_CURRENT_DEVICE_TYPE', 'ALEXA_CURRENT_DSN'], [$deviceinfos['deviceType'], $deviceinfos['deviceSerialNumber']], $postfields
        );

        return $this->SendEcho($url, $header, $postfields);
    }

    private function TuneinQueueandplay(array $getfields, array $postfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/tunein/queue-and-play?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, json_encode($postfields));
    }

    private function CloudplayerQueueandplay(array $getfields, array $postfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/cloudplayer/queue-and-play?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, json_encode($postfields));
    }

    private function MediaState($getfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/media/state?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header);
    }

    private function Notifications()
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/notifications?';

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header);
    }

    private function ToDos($getfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/todos?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header);
    }

    private function Activities($getfields)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/activities?' . http_build_query($getfields);

        $header = $this->GetHeader();

        return $this->SendEcho($url, $header); //it seems as if postfields are not supported within this command
    }

    private function PrimeSections($getfields, $filterSections, $filterCategories, $stationItems)
    {
        $url = 'https://' . $this->GetAlexaURL() . '/api/prime/prime-sections?' . http_build_query($getfields);

        $header = $this->GetHeader();

        $result = $this->SendEcho($url, $header);

        if ($result['http_code'] === 200) {

            //$arr     = json_decode($result['body'], true)['primeStationSectionList'][0]['categories'][0]['stations'];
            $arr     = json_decode($result['body'], true)['primeStationSectionList'];
            $arr_neu = [];
            foreach ($arr as $sectionKey => $section) {
                if (!count($filterSections) || in_array($section['sectionId'], $filterSections, true)) {
                    $arr_neu[$sectionKey]['sectionId']   = $section['sectionId'];
                    $arr_neu[$sectionKey]['sectionName'] = $section['sectionName'];
                    foreach ($section['categories'] as $categoryKey => $category) {
                        if (!count($filterCategories) || in_array($category['categoryId'], $filterCategories, true)) {
                            $arr_neu[$sectionKey]['categories'][$categoryKey]['categoryId']   = $category['categoryId'];
                            $arr_neu[$sectionKey]['categories'][$categoryKey]['categoryName'] = $category['categoryName'];
                            foreach ($category['stations'] as $stationKey => $station) {
                                foreach ($station as $itemName => $item) {
                                    if (in_array($itemName, $stationItems, true)) {
                                        $arr_neu[$sectionKey]['categories'][$categoryKey]['stations'][$stationKey][$itemName] = $item;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            //echo substr(print_r($arr['primeStationSectionList'], true), 0, 100000);
            //$result['body'] = json_encode(strlen(json_encode($arr_neu)));
            $result['body'] = json_encode($arr_neu);

        }

        return $result;
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
        $url = str_replace(['{AlexaURL}', '{AmazonURL}'], [$this->GetAlexaURL(), $this->GetAmazonURL()], $url);

        $header = $this->GetHeader();

        if ($postfields === null) {
            return $this->SendEcho($url, $header, null, $optpost);
        }

        return $this->SendEcho($url, $header, json_encode($postfields), $optpost);
    }

    private function SendDelete(string $url)
    {
        $header = $this->GetHeader();

        return $this->SendEcho($url, $header, 'DELETE', false);
    }

    /** get JSON device list
     *
     * @param string|null $deviceType
     * @param string|null $serialNumber
     * @param bool        $cached
     *
     * @return mixed
     */
    private function GetDevices(string $deviceType = null, string $serialNumber = null, bool $cached = null)
    {

        if (!isset($cached)){
            $cached = false;
        }

        $header = $this->GetHeader();

        $getfields = [
            'cached' => $cached ? 'true' : 'false'];

        $url = 'https://' . $this->GetAlexaURL() . '/api/devices-v2/device?' . http_build_query($getfields);

        $result = $this->SendEcho($url, $header);

        if ($result['http_code'] !== 200) {
            return $result;
        }
        //print_r($result);
        //if the info is needed for a single device
        if (($deviceType !== null) && ($serialNumber !== null)) {
            $devices_arr = json_decode($result['body'], true);
            $myDevice    = null;
            foreach ($devices_arr['devices'] as $key => $device) {
                if (($device['deviceType'] === $deviceType) && ($device['serialNumber'] === $serialNumber)) {
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

    private function SendEchoData(string $url, array $header, array $postfields = null): array
    {
        $this->SendDebug(__FUNCTION__, 'url: ' . $url, 0);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->ReadPropertyString('CookiesFileName')); //this file is read
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->ReadPropertyString('CookiesFileName'));  //this file is written
        curl_setopt($ch, CURLOPT_USERAGENT, $this->ReadPropertyString('browser'));
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($postfields !== null) {
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

    private function GetHeader(): array
    {

        $csrf = '';

        if ($this->ReadPropertyBoolean('UseCustomCSRFandCookie')) {
            $csrf = $this->getCsrfFromCookie();
            if ($csrf === false) {
                trigger_error('no valid CSRF in cookie: ' . $this->ReadPropertyString('alexa_cookie'));
            }
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
            'Connection: keep-alive'];
        //'Content-Type: application/x-www-form-urlencoded; charset=UTF-8']; //todo: experimentell auskommentiert, damit Capabilities abgefragt werden können

        if ($csrf) {
            $header[] = 'csrf: ' . $csrf;
        }

        return $header;
    }


    /**  Send to Echo API
     *
     * @param string    $url
     *
     * @param array     $header
     * @param string    $postfields as json string
     *
     * @param bool|null $optpost
     *
     * @return mixed
     */
    private function SendEcho(string $url, array $header, string $postfields = null, bool $optpost = null)
    {
        $this->SendDebug(__FUNCTION__, 'Header: ' . json_encode($header), 0);

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
            $options[CURLOPT_COOKIE] = $this->ReadPropertyString('alexa_cookie'); //this content is read
        } else {
            $options [CURLOPT_COOKIEFILE] = $this->ReadPropertyString('CookiesFileName'); //this file is read
        }

        if ($postfields !== null) {
            if($postfields === 'DELETE')
            {
                $this->SendDebug(__FUNCTION__, 'Type: DELETE', 0);
                $options [CURLOPT_CUSTOMREQUEST] = 'DELETE';
            }
            else
            {
                $this->SendDebug(__FUNCTION__, 'Postfields: ' . $postfields, 0);
                $options [CURLOPT_POSTFIELDS] = $postfields;
            }
        }

        if ($optpost !== null) {
            $options[CURLOPT_POST] = $optpost;
        }

        $this->SendDebug(__FUNCTION__, 'Options: ' . json_encode($options), 0);
        curl_setopt_array($ch, $options);

        $result = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->SendDebug(__FUNCTION__, 'Error: (' . curl_errno($ch) . ') ' . curl_error($ch), 0);
            $this->LogMessage('Error: (' . curl_errno($ch) . ') ' . curl_error($ch), KL_ERROR);
            return false;
        }

        $info      = curl_getinfo($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $this->SendDebug(__FUNCTION__, 'Send to URL: ' . print_r($url, true), 0);
        $this->SendDebug(__FUNCTION__, 'Curl Info: ' . $http_code . ' ' . print_r($info, true), 0);
        curl_close($ch);
        //eine Fehlerbehandlung macht hier leider keinen Sinn, da 400 auch kommt, wenn z.b. der Bildschirm (Show) ausgeschaltet ist

        return $this->getReturnValues($info, $result);
    }/** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * @param $JSONString
     *
     * @return bool|false|string
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, 'Incoming: ' . $JSONString, 0);
        // Empfangene Daten von der Device Instanz
        $data = json_decode($JSONString, false)->Buffer;

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

            case 'NpQueue':
                $getfields = $buffer['getfields'];

                $result = $this->NpQueue($getfields);
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

            case 'Notifications':
                $result = $this->Notifications();
                break;

            case 'ToDos':
                $getfields = $buffer['getfields'];

                $result = $this->ToDos($getfields);
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
                $postfields = $buffer['postfields'] ?? null;
                $optpost    = $buffer['optpost'] ?? null;
                if (isset($buffer['getfields'])) {
                    $url = $buffer['url'] . http_build_query($buffer['getfields']);
                } else {
                    $url = $buffer['url'];
                }

                $result = $this->CustomCommand($url, $postfields, $optpost);
                break;

            case 'SendDelete':
                $url = $buffer['url'];
                $result = $this->SendDelete($url);
                break;

            case 'GetDevices':
                $result = $this->GetDevices();
                break;

            case 'PrimeSections':
                $getfields = $buffer['getfields'];

                //$result = $this->PrimeSections($getfields, [], [], ['stationTitle', 'seedId']);
                $result = $this->PrimeSections(
                    $getfields, $buffer['additionalData']['filterSections'], $buffer['additionalData']['filterCategories'],
                    $buffer['additionalData']['stationItems']
                );
                break;

            case 'GetCustomerID':
                $result = ['http_code' => 200, 'header' => '', 'body' => $this->GetBuffer('customerID')];
                $this->SendDebug(__FUNCTION__, 'Return: ' . $this->GetBuffer('customerID'), 0);

                break;

            default:
                trigger_error('Method \'' . $data->method . '\' not yet supported');
                return false;
        }

        $ret = json_encode($result);
        $this->SendDebug(__FUNCTION__, 'Return: ' . strlen($ret) . ' Zeichen', 0);
        return $ret;

    }


    /***********************************************************
     * Configuration Form
     ***********************************************************/
    /** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * build configuration form
     *
     * @return string
     * @noinspection PhpMissingParentCallCommonInspection
     */
    public function GetConfigurationForm(): string
    {
        // return current form
        return json_encode(
            [
                'elements' => $this->FormElements(),
                'actions'  => $this->FormActions(),
                'status'   => $this->FormStatus()]
        );
    }

    /**
     * return form configurations on configuration step
     *
     * @return array
     */
    private function FormElements(): array
    {
        return [
            [
                'name'    => 'active',
                'type'    => 'CheckBox',
                'caption' => 'active'],
            [
                'name'    => 'username',
                'type'    => 'ValidationTextBox',
                'caption' => 'Amazon User Name'],
            [
                'name'    => 'password',
                'type'    => 'PasswordTextBox',
                'caption' => 'Amazon Password'],
            [
                'name'    => 'amazon2fa',
                'type'    => 'PasswordTextBox',
                'caption' => 'Amazon 2FA'],
            [
                'name'    => 'language',
                'type'    => 'Select',
                'caption' => 'Echo language',
                'options' => $this->GetEchoLanguageList()],
            [
                'name'    => 'UseCustomCSRFandCookie',
                'type'    => 'CheckBox',
                'caption' => 'Use Custom Cookie'],
            [
                'name'    => 'alexa_cookie',
                'type'    => 'ValidationTextBox',
                'caption' => 'Cookie']

        ];
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
                'caption' => 'Test the Registration:'],
            [
                'type'    => 'Button',
                'caption' => 'login',
                'onClick' => "if (EchoIO_LogIn(\$id)){echo 'Die Anmeldung war erfolgreich.';} else {echo 'Bei der Anmeldung ist ein Fehler aufgetreten.';}"],
            [
                'type'    => 'Button',
                'caption' => 'logoff',
                'onClick' => "if (EchoIO_LogOff(\$id)){echo 'Die Abmeldung war erfolgreich.';} else {echo 'Bei der Abmeldung ist ein Fehler aufgetreten.';}"],
            [
                'type'    => 'Button',
                'caption' => 'Login Status',
                'onClick' => "if (EchoIO_CheckLoginStatus(\$id)){echo 'Sie sind angemeldet.';} else {echo 'Sie sind nicht angemeldet.';}"],
            [
                'type'    => 'Button',
                'caption' => 'Get OTP',
                'onClick' => "echo 'Amazon OTP: ' . EchoIO_GetOTP(\$id);"]];


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
                'caption' => 'user name must not be empty.'],
            [
                'code'    => 211,
                'icon'    => 'error',
                'caption' => 'password must not be empty.'],
            [
                'code'    => 212,
                'icon'    => 'error',
                'caption' => 'cookie must not be empty.'],
            [
                'code'    => 213,
                'icon'    => 'error',
                'caption' => 'cookie without csrf.'],
            [
                'code'    => 214,
                'icon'    => 'error',
                'caption' => 'not authenticated.']];

        return $form;
    }

    private function GetEchoLanguageList(): array
    {
        $options = [
            [
                'caption' => 'Please choose',
                'value'   => -1],
            [
                'caption' => 'german',
                'value'   => 0],
            [
                'caption' => 'english',
                'value'   => 1]];
        return $options;
    }

}