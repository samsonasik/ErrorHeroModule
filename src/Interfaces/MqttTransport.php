<?php
namespace ErrorHeroModule\Interfaces;

/**
 * Mqtt Transport Interface
 *
 * @author Pierre Jochem
 */

interface MqttTransport {
    
    public function connect();
    public function publish($topic, $message);
    public function close();
    
}
