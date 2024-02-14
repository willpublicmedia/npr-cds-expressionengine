<?php
namespace IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

require_once __DIR__ . '/../../../../npr_story_api/ext.npr_story_api.php';

class Legacy_extension_installer
{
    private $legacy_story_api_extensions = array(
        'Npr_story_api_ext',
    );

    public function install()
    {
        foreach ($this->legacy_story_api_extensions as $name) {
            try {
                $class = '\\' . $name;
                $extension = new $class();
                $extension->activate_extension();
            } catch (\Exception $err) {
                print_r($err);
            }
        }
    }

    public function uninstall()
    {
        $extensions = $this->legacy_story_api_extensions;

        foreach ($extensions as $name) {
            try {
                $class = '\\' . $name;
                $extension = new $class();
                $extension->disable_extension();
            } catch (\Exception $err) {
                print_r($err);
            }
        }
    }
}
