<?php

namespace Graphiter\Data;

/**
 * Class Units
 *
 * @package Graphiter\Data
 */
class Units
{
    protected $toRaw = ['M' => 'megabytesToBytes',];
    protected $toUnit = ['BtoM' => 'bytesToMegabytes'];

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function toRaw($value)
    {
        if (preg_match('/([0-9]+)(.+)/', $value, $match)) {

            if (isset($this->toRaw[$match[2]])) {
                $method = $this->toRaw[$match[2]];

                return $this->$method($value, $match[1]);
            }

        }

        return $value;
    }

    /**
     * @param string $unit
     * @param string $value
     *
     * @return mixed
     */
    public function toUnit($unit, $value)
    {
        if (isset($this->toUnit[$unit])) {
            $method = $this->toUnit[$unit];

            return $this->$method($value);
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    public function megabytesToBytes($value)
    {
        return $value * 1024 * 1024;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function bytesToMegabytes($value)
    {
        return number_format(round($value / 1024 / 1024, 2)) . "M";
    }
}
