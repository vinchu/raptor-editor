<?php
class ThemeTask extends Task {

    private $file;
    private $buildDir;

    public function mkdir($directory, $mode = 0777, $recursive = true) {
        // Check the directory is not blank
        if (!$directory) return false;

        // If the directory already exists we are already done
        if (is_dir($directory) || $directory === '/') {
            return true;
        }

        $create = function($directory, $mode) {
            if (!mkdir($directory, $mode)) {
                // <strict>
                throw new IOException("Failed to create {$directory} with permission {$mode}");
                // </strict>
            }
            if (!chmod($directory, $mode)) {
                // <strict>
                throw new IOException("Failed to set permission {$mode} for {$directory}");
                // </strict>
            }
        };

        if ($recursive) {
            if ($this->mkdir(dirname($directory), $mode, $recursive)) {
                $create($directory, $mode);
            }
        } else {
            $create($directory, $mode);
        }
        return is_dir($directory);
    }

    public function main() {
        $manifest = '';
        $frontEnd = '';
        $this->mkdir($this->buildDir . '/src/style/images/');
        foreach (file($this->file) as $file) {
            $file = trim($file);
            if (!$file) {
                continue;
            }
            if (preg_match('/\.(png|jpe?g|gif)$/', $file)) {
                if (!is_file($file)) {
                    die("Error processing file manifest: {$file}, does not exist.");
                    return;
                }
                copy($file, $this->buildDir . '/src/style/images/' . basename($file));
            }
            if (preg_match('/^\(function\(/', $file) ||
                    preg_match('/^}\)\(.*\);/', $file) ||
                    strpos($file, '//') === 0 ||
                    !preg_match('/\.scss$/', $file) ||
                    preg_match('@src/style/@', $file)) {
                continue;
            }

            if (preg_match('/-front-end\.scss$/', $file)) {
                $frontEnd .= "@import '.." . substr($file, 3) . "';" . PHP_EOL;
            } else {
                $manifest .= "@import '.." . substr($file, 3) . "';" . PHP_EOL;
            }
        }
        file_put_contents($this->buildDir . '/src/style/manifest.scss', $manifest);
        file_put_contents($this->buildDir . '/src/style/front-end.scss', $frontEnd);
        file_put_contents($this->buildDir . '/src/style/raptor.scss', "@import 'manifest';" . PHP_EOL, FILE_APPEND);
    }

    public function setFile($file) {
        $this->file = $file;
    }

    public function setBuildDir($buildDir) {
        $this->buildDir = $buildDir;
    }

    public function setWrapper($wrapper) {
        $this->wrapper = $wrapper;
    }

    public function setNoConflict($noConflict) {
        $this->noConflict = $noConflict;
    }

    public function setName($name) {
        $this->name = (string) $name;
    }

    public function getName() {
        return $this->name;
    }
}
