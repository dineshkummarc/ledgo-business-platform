<?php

namespace App\Support\Integrations;

use ImranDevBD\LaravelAdminPanel\Facades\AdminPanel;

class AdminPanelConsole implements PlatformConsole
{
    public function path(): string
    {
        return AdminPanel::getPanel('admin')->getPath();
    }
}
