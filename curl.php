<?php

class Monitor {
    protected $url = null;
    protected $info = [];
    protected $errors = false;
    
    public function __construct($url) {
        $this->url = $url;
    }

    public function getInfo() {
        return $this->info;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function isError() {
        if ($this->errors) {
            return true;
        }

        return false;
    }
    
    public function exec () {
        // Create a cURL handle
        $ch = curl_init($this->url);

        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute
        curl_exec($ch);

        // Check if any error occurred
        if (curl_errno($ch)) {
            $this->errors = curl_errno($ch);
        }
        
        $this->info = curl_getinfo($ch);
        // Close handle
        curl_close($ch);

        return $this;
    }
}
?>
