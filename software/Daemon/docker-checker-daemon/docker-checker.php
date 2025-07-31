<?php

class DockerManager {
    private string $configFile;
    private array $config;

    public function __construct(string $configFile) {
        $this->configFile = $configFile;
        $this->loadConfig();
    }

    private function loadConfig(): void {
        if (!file_exists($this->configFile)) {
            throw new Exception("Configuration file not found: " . $this->configFile);
        }
        $this->config = json_decode(file_get_contents($this->configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON configuration: " . json_last_error_msg());
        }
    }

    private function saveConfig(): void {
        if (file_put_contents($this->configFile, json_encode($this->config, JSON_PRETTY_PRINT)) === false) {
            throw new Exception("Failed to save configuration file");
        }
    }

    public function manageContainers(): void {
        $changes = false;
        
        foreach ($this->config as &$container) {
            try {
                if ($this->manageContainer($container)) {
                    $changes = true;
                }
            } catch (Exception $e) {
                error_log("Error managing container {$container['Docker Name']}: " . $e->getMessage());
            }
        }
        unset($container); // Unset reference after foreach

        if ($changes) {
            $this->saveConfig();
        }
    }

    private function manageContainer(array &$container): bool {
        $containerName = $container['Docker Name'] ?? '';
        if (empty($containerName)) {
            throw new Exception("Container name is missing");
        }

        $desiredState = strtolower($container['Docker State'] ?? '');
        if (empty($desiredState)) {
            throw new Exception("Docker State is missing");
        }

        $currentState = $this->getContainerStatus($containerName);
        $changed = false;

        echo "Container: $containerName\n";
        echo "Current State: " . ($currentState ?: "not exists") . "\n";
        echo "Desired State: $desiredState\n";

        // Initialize Status Counter if it doesn't exist
        if (!isset($container['Status Counter'])) {
            $container['Status Counter'] = "";
        }

        if ($currentState === false) {
            // Container doesn't exist
            if ($desiredState === 'start') {
                $container['Status Counter'] = (string)((int)$container['Status Counter'] + 1);
                if ((int)$container['Status Counter'] >= 3) {
                    $this->createAndStartContainer($container);
                    $container['Status Counter'] = "";
                }
                $changed = true;
            }
        } else {
            // Container exists but state doesn't match
            if ($desiredState === 'start' && $currentState !== 'running') {
                $container['Status Counter'] = (string)((int)$container['Status Counter'] + 1);
                if ((int)$container['Status Counter'] >= 3) {
                    $this->createAndStartContainer($container);
                    $container['Status Counter'] = "";
                } else {
                    $this->startContainer($containerName);
                }
                $changed = true;
            } elseif ($desiredState === 'stop' && $currentState === 'running') {
                $container['Status Counter'] = (string)((int)$container['Status Counter'] + 1);
                if ((int)$container['Status Counter'] >= 3) {
                    $this->stopContainer($containerName);
                    $this->removeContainer($containerName);
                    $container['Status Counter'] = "";
                } else {
                    $this->stopContainer($containerName);
                }
                $changed = true;
            } elseif (($currentState === 'running' && $desiredState === 'start') ||
                     ($currentState === 'stopped' && $desiredState === 'stop')) {
                // State matches desired state, reset counter
                $container['Status Counter'] = "";
                $changed = true;
            }
        }

        return $changed;
    }

    private function getContainerStatus(string $containerName): string|false {
        $output = [];
        $returnVar = 0;
        exec("docker ps -a --format '{{.Names}},{{.Status}}' --filter name=^/{$containerName}$", $output, $returnVar);
        
        if ($returnVar !== 0 || empty($output)) {
            return false;
        }

        $containerInfo = explode(',', $output[0]);
        return strpos(strtolower($containerInfo[1]), 'up') !== false ? 'running' : 'stopped';
    }

    private function createAndStartContainer(array $container): void {
        if (empty($container['Docker Config'])) {
            throw new Exception("Docker Config is missing");
        }

        // Remove existing container if it exists
        $this->removeContainer($container['Docker Name']);

        // Execute the Docker run command
        $output = [];
        $returnVar = 0;
        exec($container['Docker Config'], $output, $returnVar);

        if ($returnVar !== 0) {
            throw new Exception("Failed to create and start container: " . implode("\n", $output));
        }
    }

    private function removeContainer(string $containerName): void {
        exec("docker rm -f " . escapeshellarg($containerName) . " 2>/dev/null");
    }

    private function startContainer(string $containerName): void {
        $output = [];
        $returnVar = 0;
        exec("docker start " . escapeshellarg($containerName), $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception("Failed to start container: " . implode("\n", $output));
        }
    }

    private function stopContainer(string $containerName): void {
        $output = [];
        $returnVar = 0;
        exec("docker stop " . escapeshellarg($containerName), $output, $returnVar);
        
        if ($returnVar !== 0) {
            throw new Exception("Failed to stop container: " . implode("\n", $output));
        }
    }

    public function checkDependencies(): void {
        $output = [];
        $returnVar = 0;
        exec("docker network ls --format '{{.Name}}' | grep '^doc1$'", $output, $returnVar);
        
        if ($returnVar !== 0) {
            // Create the network if it doesn't exist
            exec("docker network create doc1 --subnet=172.20.0.0/16", $output, $returnVar);
            if ($returnVar !== 0) {
                throw new Exception("Failed to create docker network doc1");
            }
        }
    }
}

// Usage
try {
    $manager = new DockerManager('/usr/bin/web-rated-docker.json');
    $manager->checkDependencies();
    $manager->manageContainers();
    echo "Docker containers managed successfully\n";
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo "Error occurred while managing Docker containers. Check error log for details.\n";
    exit(1);
}