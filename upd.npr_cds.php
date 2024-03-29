<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/libraries/installation/dependency_manager.php';
require_once __DIR__ . '/database/migrations/pre_install/story_api_settings_migrator.php';
require_once __DIR__ . '/database/installation/fields/field_installer.php';
// require_once __DIR__ . '/libraries/installation/channel_installer.php';
require_once __DIR__ . '/database/installation/status_installer.php';
require_once __DIR__ . '/database/migrations/pre_install/legacy_extension_installer.php';
require_once __DIR__ . '/database/installation/tables/table_loader.php';
require_once __DIR__ . '/database/installation/tables/itable.php';
require_once __DIR__ . '/libraries/installation/table_installer.php';

use ExpressionEngine\Service\Addon\Installer;
use IllinoisPublicMedia\NprCds\Database\Installation\Fields\Field_installer;
use IllinoisPublicMedia\NprCds\Database\Installation\Status_installer;
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\ITable;
use IllinoisPublicMedia\NprCds\Database\Installation\Tables\Table_loader;
use IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall\Legacy_extension_installer;
// use IllinoisPublicMedia\NprStoryApi\Libraries\Installation\Channel_installer;
use IllinoisPublicMedia\NprCds\Database\Migrations\PreInstall\Story_api_settings_migrator;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Dependency_manager;
use IllinoisPublicMedia\NprCds\Libraries\Installation\Table_installer;

/**
 * NPR CDS updater.
 */
class Npr_cds_upd extends Installer
{
    public $has_cp_backend = 'y';

    public $has_publish_fields = 'n';

    private $channels = array(
        'npr_stories',
    );

    private $publish_layout = 'NPR CDS';

    private $tables = array(
        // table order matters for column relationships
        'config' => array(
            'config_settings',
            // 'config_field_mappings',
        ),
        'story' => array(
            //     'pushed_stories',
        ),
    );

    /**
     * NPR CDS updater constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        ee()->load->dbforge();
    }

    /**
     * Install NPR CDS module.
     *
     * @return bool
     */
    public function install()
    {
        if ($this->check_dependencies() === false) {
            return false;
        }

        // create and/or migrate settings
        $this->create_tables($this->tables['config']);

        if ($this->npr_story_api_installed() === true) {
            $this->migrate_story_api_settings();

            // to do: migrate story api fields

            $this->delete_legacy_extensions();
        }

        // $this->create_required_fields();
        $this->create_required_statuses();
        // $this->create_required_channels();

        parent::install();

        return true;
    }

    /**
     * Uninstall NPR CDS module.
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->delete_tables($this->tables['config']);

        parent::uninstall();

        return true;
    }

    /**
     * Update NPR CDS module.
     *
     * @param  mixed $current Current module version.
     *
     * @return bool
     */
    public function update($current = '')
    {
        if ($this->check_dependencies() === false) {
            return false;
        }

        parent::update($current);

        return true;
    }

    private function check_dependencies(): bool
    {
        $manager = new Dependency_manager();
        $has_dependencies = $manager->check_dependencies();

        return $has_dependencies;
    }

    private function migrate_story_api_settings(): void
    {
        $migrator = new Story_api_settings_migrator();
        $migrator->migrate();
    }

    private function npr_story_api_installed(): bool
    {
        $info = ee('Addon')->get('npr_story_api');
        $installed = $info->isInstalled();
        return $installed;
    }

    // private function create_required_channels()
    // {
    //     $installer = new Channel_installer();
    //     $installer->install($this->channels, $this->publish_layout);
    // }

    private function create_required_fields()
    {
        $installer = new Field_installer();
        $installer->install();
    }

    private function create_required_statuses()
    {
        $statuses = array(
            'draft',
        );

        $installer = new Status_installer();
        $installer->install($statuses);
    }

    private function create_tables(array $table_names)
    {
        $tables = array();
        foreach ($table_names as $name) {
            $data = $this->load_table_config($name);
            array_push($tables, $data);
        }

        $installer = new Table_installer();
        $installer->install($tables);
    }

    private function delete_legacy_extensions()
    {
        $uninstaller = new Legacy_extension_installer();
        $uninstaller->uninstall();
    }

    // private function delete_fields()
    // {
    //     $uninstaller = new Field_installer();
    //     $uninstaller->uninstall();
    // }

    private function delete_tables(array $table_names)
    {
        $tables = array();
        foreach ($table_names as $name) {
            $data = $this->load_table_config($name);
            $table_name = $data->table_name();
            array_push($tables, $table_name);
        }

        $uninstaller = new Table_installer();
        $uninstaller->uninstall($tables);
    }

    private function load_table_config(string $table_name): ITable
    {
        $loader = new Table_loader();
        $data = $loader->load($table_name);

        return $data;
    }
}
