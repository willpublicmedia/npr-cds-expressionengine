<?php
namespace IllinoisPublicMedia\NprCds\Libraries\Installation;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

// require_once(__DIR__ . '/../../ext.npr_story_api.php');
require_once __DIR__ . '/../../../npr_story_api/ext.npr_story_api.php';

class Extension_installer
{
    private $legacy_story_api_extensions = array(
        'Npr_story_api_ext',
    );

    private $required_extensions = array(
        'Npr_story_api_ext',
    );

    public function install()
    {
        foreach ($this->required_extensions as $name) {
            try {
                $class = '\\' . $name;
                $extension = new $class();
                $extension->activate_extension();
            } catch (\Exception $err) {
                print_r($err);
            }
        }
    }

    public function uninstall($legacy = false)
    {
        $extensions = $legacy === false ? $this->required_extensions : $this->legacy_story_api_extensions;
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