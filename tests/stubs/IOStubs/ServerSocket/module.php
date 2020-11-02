<?php

declare(strict_types=1);
include_once __DIR__ . '/../VirtualIO/module.php';

class ServerSocketBase extends VirtualIO
{
    private $packetQueue = [];

    public function ForwardData($JSONString)
    {
        $data = json_decode($JSONString, true);

        if (!isset($data['DataID'])) {
            throw new Exception('Invalid Data packet received');
        }

        if ($data['DataID'] == '{C8792760-65CF-4C53-B5C7-A30FCC84FEFE}') {
            $this->packetQueue[] = [
                'Type'       => isset($data['Type']) ? $data['Type'] : 0 /* Data */,
                'Buffer'     => utf8_decode($data['Buffer']),
                'ClientIP'   => $data['ClientIP'],
                'ClientPort' => $data['ClientPort']
            ];
            return;
        }

        parent::ForwardData($JSONString);
    }

    public function HasPacket()
    {
        return count($this->packetQueue) > 0;
    }

    public function PeekPacket()
    {
        if (!$this->HasPacket()) {
            throw new Exception('There is not data available');
        }

        return $this->packetQueue[0];
    }

    public function PopPacket()
    {
        $result = $this->PeekPacket();
        array_shift($this->packetQueue);

        return $result;
    }

    public function PushConnect($ClientIP, $ClientPort)
    {
        $this->SendDataToChildren(json_encode([
            'DataID'     => '{7A1272A4-CBDB-46EF-BFC6-DCF4A53D2FC7}',
            'Type'       => 1 /* Connect */,
            'Buffer'     => '',
            'ClientIP'   => $ClientIP,
            'ClientPort' => $ClientPort
        ]));
    }

    public function PushDisconnect($ClientIP, $ClientPort)
    {
        $this->SendDataToChildren(json_encode([
            'DataID'     => '{7A1272A4-CBDB-46EF-BFC6-DCF4A53D2FC7}',
            'Type'       => 2 /* Disconnect */,
            'Buffer'     => '',
            'ClientIP'   => $ClientIP,
            'ClientPort' => $ClientPort
        ]));
    }

    public function PushPacket($Text, $ClientIP, $ClientPort)
    {
        $this->SendDataToChildren(json_encode([
            'DataID'     => '{7A1272A4-CBDB-46EF-BFC6-DCF4A53D2FC7}',
            'Type'       => 0 /* Data */,
            'Buffer'     => utf8_encode($Text),
            'ClientIP'   => $ClientIP,
            'ClientPort' => $ClientPort
        ]));
    }
}

class ServerSocket extends ServerSocketBase
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('Port', '');
        $this->RegisterPropertyInteger('Limit', 0);
    }
}
