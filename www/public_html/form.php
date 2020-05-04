<?php

namespace Example\Form;

// Подключение автозагрузки через composer
function parse($filePath)
{
    $fileContent = file_get_contents($filePath);
    $filePathParts = pathinfo($filePath);
    if ($fileContent === false) {
        throw new \Exception("Can't read file by: {$filePath}");
    }
    $result = json_decode($fileContent);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \Exception("json error: " . json_last_error());
    }
    return $result;
}
function write($data, $filePath)
{
    $content = json_encode($data);
    $result = file_put_contents($filePath, $content);
    if ($result === false) {
        throw new \Exception("Can't write file by: {$filePath}");
    }
}
