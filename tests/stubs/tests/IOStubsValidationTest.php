<?php

declare(strict_types=1);

include_once __DIR__ . '/../Validator.php';

class IOStubsValidationTest extends TestCaseSymconValidation
{
    public function testValidateIOStubs(): void
    {
        $this->validateLibrary(__DIR__ . '/../IOStubs');
    }

    public function testValidateClientSocket(): void
    {
        $this->validateModule(__DIR__ . '/../IOStubs/ClientSocket');
    }

    public function testValidateMulticastSocket(): void
    {
        $this->validateModule(__DIR__ . '/../IOStubs/MulticastSocket');
    }

    public function testValidateSerialPort(): void
    {
        $this->validateModule(__DIR__ . '/../IOStubs/SerialPort');
    }

    public function testValidateServerSocket(): void
    {
        $this->validateModule(__DIR__ . '/../IOStubs/ServerSocket');
    }

    public function testValidateUDPSocket(): void
    {
        $this->validateModule(__DIR__ . '/../IOStubs/UDPSocket');
    }

    public function testValidateVirtualIO(): void
    {
        $this->validateModule(__DIR__ . '/../IOStubs/VirtualIO');
    }

    public function testValidateWWWReader(): void
    {
        $this->validateModule(__DIR__ . '/../IOStubs/WWWReader');
    }
}
