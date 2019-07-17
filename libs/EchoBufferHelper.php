<?php

declare(strict_types=1);

trait EchoBufferHelper
{
    /**
     * Wert einer Eigenschaft aus den InstanceBuffer lesen.
     *
     * @param string $name Propertyname
     *
     * @return mixed Value of Name
     */
    public function __get($name)
    {
        /* @noinspection UnserializeExploitsInspection */
        return unserialize($this->GetBuffer($name));
    }

    /** @noinspection MagicMethodsValidityInspection */

    /**
     * Wert einer Eigenschaft in den InstanceBuffer schreiben.
     *
     * @param string $name Propertyname
     * @param mixed Value of Name
     */
    public function __set($name, $value)
    {
        $this->SetBuffer($name, serialize($value));
    }
}

/* @} */
