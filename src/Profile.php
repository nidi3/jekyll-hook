<?php

class Profile
{
    public $name, $gitUrl, $gitUser, $gitPassword, $awsBucket,$awsInstanceName;

    function __construct($dir, $gitUrl)
    {
        $file = $dir . '/.aws/profiles';
        if (!$profiles = parse_ini_file($file, true)) {
            throw new Exception("Missing or wrong profiles in '$file'");
        }
        foreach ($profiles as $key => $value) {
            if ($value['gitUrl'] === $gitUrl) {
                $this->name = $key;
                $this->gitUrl = $gitUrl;
                $this->gitUser = $value['gitUser'];
                $this->gitPassword = $value['gitPassword'];
                $this->awsBucket = $value['awsBucket'];
                $this->awsInstanceName = $value['awsInstanceName'];
            }
        }
        if (!$this->name) {
            throw new Exception("No profile for git url '$gitUrl' in $file");
        }
        if (!$this->awsBucket) {
            throw new Exception("No aws bucket defined for profile '{$this->name}' in '$file'");
        }

    }

    function repoName()
    {
        $pathPos = strrpos($this->gitUrl, '/');
        return substr($this->gitUrl, $pathPos + 1);
    }

    function cloneUrl()
    {
        $pos = strpos($this->gitUrl, '://');
        $protocol = substr($this->gitUrl, 0, $pos + 3);
        $cred = $this->gitUser ? ($this->gitUser . ':' . $this->gitPassword . '@') : '';
        $url = substr($this->gitUrl, $pos + 3, strlen($this->gitUrl) - 3);
        return $protocol . $cred . $url;
    }
}
