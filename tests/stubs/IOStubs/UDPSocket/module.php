<?php

declare(strict_types=1);
include_once __DIR__ . '/../ServerSocket/module.php';

class UDPSocket extends ServerSocketBase
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyInteger('Port', 0);
        $this->RegisterPropertyString('BindIP', '');
        $this->RegisterPropertyInteger('BindPort', 0);
    }
}
