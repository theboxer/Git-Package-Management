<?php
namespace GPM\Config;

use Symfony\Component\Yaml\Yaml;

class UnsupportedFileException extends \Exception {}
class InvalidFileException extends \Exception {}
class InvalidContent extends \Exception {}

class FileParser {
    /**
     * @param  string  $configName
     *
     * @return array
     * @throws \Exception
     */
    public static function parseFile(string $absPathToFile): array
    {
        $type = explode('.', $absPathToFile);
        $type = strtolower(array_pop($type));

        switch ($type) {
            case 'json':
                return self::parseJSON($absPathToFile);
            case 'yaml':
            case 'yml':
                return self::parseYAML($absPathToFile);
        }

        throw new UnsupportedFileException();
    }

    /**
     * @param  string  $configName
     *
     * @return array
     * @throws \Exception
     */
    public static function writeFile(string $absPathToFile, array $content): void
    {
        $type = explode('.', $absPathToFile);
        $type = strtolower(array_pop($type));

        switch ($type) {
            case 'json':
                $content = self::encodeJSON($content);
                break;
            case 'yaml':
            case 'yml':
                $content = self::encodeYAML($content);
                break;
            default: {
                throw new UnsupportedFileException();
            }
        }

        file_put_contents($absPathToFile, $content);
    }

    /**
     * @param  string  $absPathToFile
     *
     * @return array
     * @throws \Exception
     */
    private static function parseJSON(string $absPathToFile): array
    {
        $content = file_get_contents($absPathToFile);

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new InvalidFileException();
        }

        return $decoded;
    }

    /**
     * @param  array  $content
     *
     * @return string
     * @throws InvalidContent
     */
    private static function encodeJSON(array $content): string
    {

        $output = json_encode($content, JSON_PRETTY_PRINT);
        if (!is_string($output)) {
            throw new InvalidContent();
        }

        return $output;
    }

    /**
     * @param  string  $absPathToFile
     *
     * @return array
     * @throws InvalidFileException
     */
    private static function parseYAML(string $absPathToFile): array
    {
        $content = file_get_contents($absPathToFile);

        try {
            return Yaml::parse($content);
        } catch (\Exception $e) {
            throw new InvalidFileException();
        }
    }

    /**
     * @param  array  $content
     *
     * @return string
     * @throws InvalidContent
     */
    private static function encodeYAML(array $content): string
    {

        try {
            $output = Yaml::dump($content, 8);
        } catch (\Exception) {
            throw new InvalidContent();
        }

        if (!is_string($output)) {
            throw new InvalidContent();
        }

        return $output;
    }
}