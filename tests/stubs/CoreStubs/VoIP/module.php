<?php

declare(strict_types=1);

class VoIP extends IPSModule
{
    private $connections = [];
    private $newID = 0;

    public function StubsGetConnections()
    {
        return $this->connections;
    }

    public function StubsAnswerOutgoingCall(int $ConnectionID)
    {
        $this->setConnected($ConnectionID, true);
    }

    public function StubsRejectOutgoingCall(int $ConnectionID)
    {
        $this->setConnected($ConnectionID, false);
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyInteger('ProcessingScript', 0);
    }

    public function AcceptCall(int $ConnectionID)
    {
        throw new Exception('Not implemented');
    }

    public function Connect(string $Number)
    {
        return $this->createCall($Number);
    }

    public function Disconnect(int $ConnectionID)
    {
        unset($this->connections[$ConnectionID]);
    }

    public function GetConnection(int $ConnectionID)
    {
        return $this->connections[$ConnectionID];
    }

    public function GetData(int $ConnectionID)
    {
        throw new Exception('Not implemented');
    }

    public function PlayWave(int $ConnectionID, int $FileName)
    {
        throw new Exception('Not implemented');
    }

    public function RejectCall(int $ConnectionID)
    {
        throw new Exception('Not implemented');
    }

    public function SendDTMF(int $ConnectionID, string $DTMF)
    {
        throw new Exception('Not implemented');
    }

    public function SetData(int $ConnectionID, string $Data)
    {
        throw new Exception('Not implemented');
    }

    private function createCall(string $number)
    {
        $id = $this->newID;
        $this->connections[$id] = [
            'ID'           => $id,
            'TimeStamp'    => time(),
            'Number'       => $number,
            'Direction'    => 1,
            'Connected'    => false,
            'Disconnected' => true
        ];

        //increment id
        $this->newID++;
        return $id;
    }

    private function setConnected(int $connectionID, bool $connected)
    {
        $this->connections[$connectionID]['Connected'] = $connected;
        $this->connections[$connectionID]['Disconnected'] = !$connected;
    }
}