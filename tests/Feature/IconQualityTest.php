<?php

namespace Tests\Feature;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class IconQualityTest extends TestCase
{
    public function test_blade_templates_only_use_supported_regular_font_awesome_icons(): void
    {
        $supported = ['fa-bell', 'fa-file-lines', 'fa-image', 'fa-user'];
        $failures = [];

        foreach ($this->bladeFiles(resource_path('views')) as $file) {
            preg_match_all('/fa-regular\s+(fa-[a-z0-9-]+)/', file_get_contents($file), $matches);
            foreach ($matches[1] as $icon) {
                if (! in_array($icon, $supported, true)) {
                    $failures[] = str_replace(base_path().'\\', '', $file).": {$icon}";
                }
            }
        }

        $this->assertSame([], $failures, "Unsupported Font Awesome Regular icons:\n".implode("\n", $failures));
    }

    public function test_admin_shell_and_course_management_do_not_use_emoji_as_icons(): void
    {
        $files = array_merge(
            [resource_path('views/layouts/admin.blade.php')],
            $this->bladeFiles(resource_path('views/admin/courses')),
        );
        $failures = [];

        foreach ($files as $file) {
            if (preg_match('/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u', file_get_contents($file))) {
                $failures[] = str_replace(base_path().'\\', '', $file);
            }
        }

        $this->assertSame([], $failures, "Emoji UI icons found in:\n".implode("\n", $failures));
    }

    /** @return list<string> */
    private function bladeFiles(string $directory): array
    {
        $files = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
