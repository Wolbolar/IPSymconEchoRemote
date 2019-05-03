<?php

declare(strict_types=1);

/**
 * DebugHelper ergänzt SendDebug um die Möglichkeit Array und Objekte auszugeben.
 */
trait EchoDebugHelper
{

    /**
     * Ergänzt SendDebug um Möglichkeit Objekte und Array auszugeben.
     *
     * @param string $Message Nachricht für Data.
     * @param mixed  $Data    Daten für die Ausgabe.
     * @param        $Format
     */
    protected function SendDebug($Message, $Data, $Format): void
    {
        if (is_object($Data)) {
            foreach ($Data as $Key => $DebugData) {
                $this->SendDebug($Message . ':' . $Key, $DebugData, 0);
            }
        } elseif (is_array($Data)) {
            foreach ($Data as $Key => $DebugData) {
                $this->SendDebug($Message . ':' . $Key, $DebugData, 0);
            }
        } elseif (is_bool($Data)) {
            $this->SendDebug($Message, ($Data ? 'TRUE' : 'FALSE'), 0);
        } elseif (IPS_GetKernelRunlevel() === KR_READY) {
            parent::SendDebug($Message, (string) $Data, $Format);
        } else {
            IPS_LogMessage('PRTG:' . $Message, (string) $Data);
        }
    }
}

/* @} */
