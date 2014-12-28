<?php

require 'aws/aws-autoloader.php';

class Ec2
{
    private static $REPEAT = 1, $END = 2;
    private $client, $name;
    public $waitInterval = 10, $maxWait = 180;

    function __construct($config, $instanceName)
    {
        $this->$client = \Aws\Ec2\Ec2Client::factory(array(
            'key' => $config->key,
            'secret' => $config->secret,
            'region' => $config->region));
        $this->$name = $instanceName;
    }

    function waitToStart()
    {
        $this->waitingAction(function ($instance) {
            if ($this->stateOf($instance) === Ec2State::STOPPED) {
                $this->$client->startInstances(array('InstanceIds' => array($instance['InstanceId'])));
                $this->waitForState(Ec2State::RUNNING);
            }
        });
    }

    function waitToStop()
    {
        $this->waitingAction(function ($instance) {
            if ($this->stateOf($instance) === Ec2State::RUNNING) {
                $this->$client->stopInstances(array('InstanceIds' => array($instance['InstanceId'])));
                $this->waitForState(Ec2State::STOPPED);
            }
        });
    }

    function waitingAction($action)
    {
        $start = time();
        while (time() < $start + $this->$maxWait) {
            $instance = $this->instanceInfo();
            switch ($this->stateOf($instance)) {
                case Ec2State::PENDING:
                case Ec2State::STOPPING:
                    sleep($this->$waitInterval);
                    break;
                case Ec2State::SHUTTING_DOWN:
                    throw new Exception('Cannot execute action on a shutting-down instance');
                case Ec2State::TERMINATED:
                    throw new Exception('Cannot execute action on a terminated instance');
                default:
                    if ($action($instance) !== Ec2::$REPEAT) {
                        return;
                    }
            }
        }
        throw new Exception('Timeout executing action');
    }

    function waitForState($state)
    {
        $this->waitingAction(function ($instance) use ($state) {
            return ($this->stateOf($instance) === $state) ? Ec2::$END : Ec2::$REPEAT;
        });
    }

    function instanceInfo()
    {
        $result = $this->$client->describeInstances(array('Filters' => array(
            array('Name' => 'tag:Name', 'Values' => array($this->$name))
        )))->toArray();

        $reservation = $result['Reservations'][0];
        if (!$reservation) {
            throw new Exception("No reservations found with name {$this->$name}");
        }
        $instance = $reservation['Instances'][0];
        if (!$instance) {
            throw new Exception("No instances found with name {$this->$name}");
        }
        return $instance;
    }

    function stateOf($instanceInfo)
    {
        return $instanceInfo['State']['Code'];
    }

    function publicIp()
    {
        $info = $this->instanceInfo();
        if (!$ip = $info['PublicIpAddress']) {
            throw new Exception('No public ip found: ' . var_export($info, true));
        }
        return $ip;
    }
}

class Ec2State
{
    const PENDING = 0, RUNNING = 16, SHUTTING_DOWN = 32, TERMINATED = 48, STOPPING = 64, STOPPED = 80;
}
