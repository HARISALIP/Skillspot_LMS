<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Section;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class ScormPackageService
{
    private const MAX_FILES = 10000;

    private const MAX_EXPANDED_BYTES = 1073741824;

    private const MAX_COMPRESSION_RATIO = 100;

    public function install(UploadedFile $package, Course $course, Section $section): array
    {
        $relativeDirectory = "scorm/{$course->id}/{$section->id}";
        $finalDirectory = storage_path('app/private/'.$relativeDirectory);
        $temporaryDirectory = $finalDirectory.'.tmp-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($temporaryDirectory);

        $archive = new ZipArchive;
        if ($archive->open($package->getRealPath()) !== true) {
            File::deleteDirectory($temporaryDirectory);
            throw new RuntimeException('The selected file is not a readable ZIP package.');
        }

        try {
            $expandedBytes = 0;
            if ($archive->numFiles < 1 || $archive->numFiles > self::MAX_FILES) {
                throw new RuntimeException('The SCORM package contains too many files.');
            }

            for ($index = 0; $index < $archive->numFiles; $index++) {
                $stat = $archive->statIndex($index);
                if (! $stat) {
                    throw new RuntimeException('The SCORM package contains an unreadable entry.');
                }
                $entry = $this->normaliseRelativePath($stat['name']);
                $expandedBytes += (int) ($stat['size'] ?? 0);
                if ($expandedBytes > self::MAX_EXPANDED_BYTES) {
                    throw new RuntimeException('The expanded SCORM package is larger than 1 GB.');
                }
                $compressed = (int) ($stat['comp_size'] ?? 0);
                if (($stat['size'] ?? 0) > 1048576 && $compressed > 0 && ($stat['size'] / $compressed) > self::MAX_COMPRESSION_RATIO) {
                    throw new RuntimeException('The SCORM package has an unsafe compression ratio.');
                }
                if ($this->isSymlink($archive, $index)) {
                    throw new RuntimeException('SCORM packages cannot contain symbolic links.');
                }
                if (str_ends_with($stat['name'], '/') || str_ends_with($stat['name'], '\\')) {
                    File::ensureDirectoryExists($temporaryDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $entry));

                    continue;
                }
                $destination = $temporaryDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $entry);
                File::ensureDirectoryExists(dirname($destination));
                $input = $archive->getStream($stat['name']);
                $output = fopen($destination, 'wb');
                if (! $input || ! $output) {
                    throw new RuntimeException('A file in the SCORM package could not be extracted.');
                }
                stream_copy_to_stream($input, $output);
                fclose($input);
                fclose($output);
            }
        } catch (\Throwable $exception) {
            File::deleteDirectory($temporaryDirectory);
            throw $exception;
        } finally {
            $archive->close();
        }

        try {
            $manifest = $this->findManifest($temporaryDirectory);
            $manifestRelative = $this->relativeTo($temporaryDirectory, $manifest);
            $launchRelative = $this->findLaunchFile($temporaryDirectory, $manifest, $manifestRelative);
            if (File::isDirectory($finalDirectory)) {
                File::deleteDirectory($finalDirectory);
            }
            File::ensureDirectoryExists(dirname($finalDirectory));
            if (! File::moveDirectory($temporaryDirectory, $finalDirectory)) {
                throw new RuntimeException('The SCORM package could not be installed.');
            }

            return [
                'scorm_package_name' => $package->getClientOriginalName(),
                'scorm_package_path' => $relativeDirectory,
                'scorm_manifest_path' => $manifestRelative,
                'scorm_launch_path' => $launchRelative,
                'scorm_package_size' => $package->getSize(),
            ];
        } catch (\Throwable $exception) {
            File::deleteDirectory($temporaryDirectory);
            throw $exception;
        }
    }

    public function assetPath(Section $section, string $path): string
    {
        if (! $section->is_scorm || ! $section->scorm_package_path) {
            abort(404);
        }
        $relative = $this->normaliseRelativePath(rawurldecode(parse_url($path, PHP_URL_PATH) ?: $path));
        $root = realpath(storage_path('app/private/'.$section->scorm_package_path));
        $asset = realpath(storage_path('app/private/'.$section->scorm_package_path.'/'.$relative));
        if (! $root || ! $asset || ! is_file($asset) || ! str_starts_with($asset, $root.DIRECTORY_SEPARATOR)) {
            abort(404);
        }

        return $asset;
    }

    public function delete(Section $section): void
    {
        if (! $section->scorm_package_path) {
            return;
        }
        $expected = 'scorm/'.$section->course_id.'/'.$section->id;
        if ($section->scorm_package_path === $expected) {
            File::deleteDirectory(storage_path('app/private/'.$expected));
        }
    }

    private function findManifest(string $root): string
    {
        $matches = collect(File::allFiles($root))->filter(fn ($file) => strtolower($file->getFilename()) === 'imsmanifest.xml');
        if ($matches->isEmpty()) {
            throw new RuntimeException('No imsmanifest.xml file was found in this ZIP package.');
        }

        return $matches->sortBy(fn ($file) => strlen($file->getPathname()))->first()->getPathname();
    }

    private function findLaunchFile(string $root, string $manifest, string $manifestRelative): string
    {
        $xml = File::get($manifest);
        if (preg_match('/<!DOCTYPE|<!ENTITY/i', $xml)) {
            throw new RuntimeException('The SCORM manifest contains unsafe XML declarations.');
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            throw new RuntimeException('The SCORM manifest is not valid XML.');
        }
        $resources = (new DOMXPath($document))->query('//*[local-name()="resource"]');
        foreach ($resources as $resource) {
            $scormType = null;
            foreach ($resource->attributes ?? [] as $attribute) {
                if (strtolower($attribute->localName) === 'scormtype') {
                    $scormType = strtolower($attribute->value);
                }
            }
            if ($scormType !== 'sco') {
                continue;
            }
            $href = trim($resource->getAttribute('href'));
            if ($href === '') {
                continue;
            }
            $parts = parse_url($href);
            if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
                throw new RuntimeException('The SCORM launch file must be inside the ZIP package.');
            }
            $manifestDirectory = dirname($manifestRelative) === '.' ? '' : dirname($manifestRelative).'/';
            $launch = $this->normaliseRelativePath($manifestDirectory.rawurldecode($parts['path'] ?? ''));
            if (! is_file($root.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $launch))) {
                throw new RuntimeException('The launch file declared by imsmanifest.xml was not found.');
            }

            return $launch.(isset($parts['query']) ? '?'.$parts['query'] : '');
        }
        throw new RuntimeException('No launchable SCO resource was found in imsmanifest.xml.');
    }

    private function normaliseRelativePath(string $path): string
    {
        if (str_contains($path, "\0") || preg_match('#^(?:[a-zA-Z]:|/|\\\\)#', $path)) {
            throw new RuntimeException('The SCORM package contains an unsafe file path.');
        }
        $parts = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                throw new RuntimeException('The SCORM package contains an unsafe file path.');
            }
            $parts[] = $part;
        }
        if (! $parts) {
            throw new RuntimeException('The SCORM package contains an invalid file path.');
        }

        return implode('/', $parts);
    }

    private function relativeTo(string $root, string $path): string
    {
        return str_replace('\\', '/', substr($path, strlen(rtrim($root, '\\/')) + 1));
    }

    private function isSymlink(ZipArchive $archive, int $index): bool
    {
        if (! $archive->getExternalAttributesIndex($index, $operationsSystem, $attributes)) {
            return false;
        }

        return $operationsSystem === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000;
    }
}
