<?php
// var/Theme.php

class Kanban_Theme
{
    private string $name;
    private string $basePath;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->basePath = __KANBAN_ROOT__ . '/usr/themes/' . $name;
    }

    public function render(string $template, array $vars = []): void
    {
        $file = $this->basePath . '/' . $template;
        if (!file_exists($file)) {
            throw new RuntimeException("Template not found: {$file}");
        }

        extract($vars);
        include $file;
    }
}
