#!/usr/bin/env php
<?php

class DockerStateUpdater {
    private string $jsonFile;
    private array $config;

    public function __construct(string $jsonFile) {
        $this->jsonFile = $jsonFile;
        $this->loadConfig();
    }

    private function loadConfig(): void {
        if (!file_exists($this->jsonFile)) {
            throw new Exception("Configuration file not found: " . $this->jsonFile);
        }
        $this->config = json_decode(file_get_contents($this->jsonFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON configuration: " . json_last_error_msg());
        }
    }

    private function saveConfig(): void {
        // Create backup
        copy($this->jsonFile, $this->jsonFile . '.bak');
        
        if (file_put_contents($this->jsonFile, json_encode($this->config, JSON_PRETTY_PRINT)) === false) {
            throw new Exception("Failed to save configuration file");
        }
    }

    public function updateState(string $dockerName, string $state): void {
        if (!in_array($state, ['start', 'stop'])) {
            throw new Exception("Invalid state. Must be 'start' or 'stop'");
        }

        // Find the entry with matching Docker Name
        $matchingName = null;
        foreach ($this->config as $entry) {
            if ($entry['Docker Name'] === $dockerName) {
                $matchingName = $entry['Name'];
                break;
            }
        }

        if ($matchingName === null) {
            throw new Exception("Docker container '$dockerName' not found in configuration");
        }

        // Update all entries with matching Name
        $updated = false;
        foreach ($this->config as &$entry) {
            if ($entry['Docker Name'] === $dockerName || $entry['Name'] === $matchingName) {
                $entry['Docker State'] = $state;
                $updated = true;
            }
        }
        unset($entry); // Clear reference

        if ($updated) {
            $this->saveConfig();
            echo "Successfully updated Docker state to '$state' for container '$dockerName' and related entries\n";
        }
    }
}

// Command line interface
if (PHP_SAPI === 'cli') {
    if ($argc !== 3) {
        echo "Usage: " . $argv[0] . " <docker_name> <state>\n";
        echo "  docker_name: The Docker container name (e.g., 'web-rated-g-e2gns')\n";
        echo "  state: Either 'start' or 'stop'\n";
        exit(1);
    }

    try {
        $scriptDir = dirname(__FILE__);
        $jsonPath = $scriptDir . '/web-rated-docker.json';
        
        $updater = new DockerStateUpdater($jsonPath);
        $updater->updateState($argv[1], $argv[2]);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
