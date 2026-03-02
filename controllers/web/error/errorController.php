<?php
class errorController {
    private $pathError;

    public function getPathError(int $errorCode, $errorMessage) 
    {
        $paths = [
            __DIR__ . '/' . $errorCode . '.php',
            dirname(__DIR__) . '/' . $errorCode . '.php',
            dirname(__DIR__, 2) . '/' . $errorCode . '.php',
            dirname(__DIR__, 3) . '/' . $errorCode . '.php',
        ];
        
        $pathError = null;
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $pathError = $path;
                break;
            }
        }
        
        if (empty($pathError)) {
            throw new \Exception("File $errorCode didn't found!");
        } else {
            $pathToError = '/' . basename(dirname($pathError)) . '/' . basename($pathError);

            if ($errorMessage) {
                $_SESSION['error_message'] = $errorMessage;
            }

            $this->pathError = $pathToError;
        }

        return $this->pathError;
    }
}